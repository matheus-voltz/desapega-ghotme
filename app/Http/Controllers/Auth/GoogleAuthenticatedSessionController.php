<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class GoogleAuthenticatedSessionController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return to_route('login')->with('error', 'O login com Google ainda não está configurado.');
        }

        $state = Str::random(64);
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = $request->session()->pull('google_oauth_state');
        $state = $request->string('state')->toString();

        if (! is_string($expectedState) || $state === '' || ! hash_equals($expectedState, $state)) {
            return to_route('login')->with('error', 'Não foi possível validar o login com Google. Tente novamente.');
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            return to_route('login')->with('error', 'O login com Google foi cancelado ou não pôde ser concluído.');
        }

        try {
            $identity = $this->identityFromAuthorizationCode($request->string('code')->toString());
        } catch (Throwable $exception) {
            report($exception);

            return to_route('login')->with('error', 'Não foi possível concluir o login com Google. Tente novamente.');
        }

        $user = User::query()
            ->where('google_id', $identity['google_id'])
            ->orWhere('email', $identity['email'])
            ->first();

        if ($user) {
            if ($user->google_id !== null && ! hash_equals($user->google_id, $identity['google_id'])) {
                return to_route('login')->with('error', 'Este e-mail já está vinculado a outra conta Google.');
            }

            if ($user->google_id === null) {
                $user->update(['google_id' => $identity['google_id']]);
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return $this->redirectFor($user);
        }

        $request->session()->put('google_registration', $identity);

        return to_route('google.complete.create');
    }

    public function createRegistration(Request $request): View|RedirectResponse
    {
        $identity = $request->session()->get('google_registration');

        if (! is_array($identity) || ! isset($identity['email'], $identity['google_id'])) {
            return to_route('register')->with('error', 'Comece o cadastro pelo botão do Google.');
        }

        return view('auth.google-register', compact('identity'));
    }

    public function storeRegistration(Request $request): RedirectResponse
    {
        $identity = $request->session()->pull('google_registration');

        if (! is_array($identity) || ! isset($identity['email'], $identity['google_id'], $identity['name'])) {
            return to_route('register')->with('error', 'Sua sessão de cadastro expirou. Tente entrar com Google novamente.');
        }

        $validated = $request->validate([
            'account_type' => ['required', 'in:buyer,seller'],
            'accept_legal' => ['accepted'],
        ]);

        $user = User::firstOrCreate(
            ['google_id' => $identity['google_id']],
            [
                'name' => $identity['name'],
                'email' => $identity['email'],
                'account_type' => $validated['account_type'],
                'public_slug' => Str::slug($identity['name']).'-'.Str::lower(Str::random(5)),
                'terms_accepted_at' => now(),
                'privacy_accepted_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(64)),
            ],
        );

        if (! $user->wasRecentlyCreated) {
            return to_route('login')->with('error', 'Essa conta Google já possui um acesso cadastrado.');
        }

        event(new Registered($user));
        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->redirectFor($user);
    }

    /**
     * @return array{google_id: string, email: string, name: string}
     */
    private function identityFromAuthorizationCode(string $code): array
    {
        $token = Http::asForm()
            ->connectTimeout(3)
            ->timeout(10)
            ->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
            ])
            ->throw()
            ->json();

        $accessToken = $token['access_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            throw ValidationException::withMessages(['google' => 'O Google não retornou um token de acesso.']);
        }

        $profile = Http::withToken($accessToken)
            ->connectTimeout(3)
            ->timeout(10)
            ->get('https://openidconnect.googleapis.com/v1/userinfo')
            ->throw()
            ->json();

        $googleId = $profile['sub'] ?? null;
        $email = $profile['email'] ?? null;
        $name = $profile['name'] ?? null;
        $emailVerified = filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! is_string($googleId) || ! is_string($email) || ! is_string($name) || ! $emailVerified) {
            throw ValidationException::withMessages(['google' => 'O Google não confirmou os dados desta conta.']);
        }

        return [
            'google_id' => $googleId,
            'email' => Str::lower($email),
            'name' => $name,
        ];
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    private function redirectUri(): string
    {
        return (string) (config('services.google.redirect') ?: route('google.callback'));
    }

    private function redirectFor(User $user): RedirectResponse
    {
        return to_route($user->isSeller() ? 'seller.settings.edit' : 'dashboard');
    }
}
