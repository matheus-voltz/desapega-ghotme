<x-guest-layout>
    <div class="auth-kicker">Sua conta</div>
    <h1>Boas-vindas de volta.</h1>
    <p class="auth-subtitle">Entre para montar sua sacola e acompanhar suas compras.</p>

    @if(session('status'))<div class="alert alert-success small">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger small">{{ session('error') }}</div>@endif
    @if(filled(config('services.google.client_id')))
        <a class="btn btn-outline-secondary w-100 py-2" href="{{ route('google.redirect') }}">Continuar com Google</a>
        <div class="d-flex align-items-center gap-2 my-4 text-secondary small"><span class="flex-grow-1 border-top"></span><span>ou use seu e-mail</span><span class="flex-grow-1 border-top"></span></div>
    @endif
    <form method="POST" action="{{ route('login') }}" class="mt-4">
        @csrf
        <div class="mb-3"><label for="email" class="form-label">E-mail</label><input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">@error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><div class="d-flex justify-content-between"><label for="password" class="form-label">Senha</label>@if(Route::has('password.request'))<a class="small" href="{{ route('password.request') }}">Esqueci minha senha</a>@endif</div><input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">@error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <label class="form-check mb-4"><input class="form-check-input" type="checkbox" name="remember"><span class="form-check-label">Manter conectado</span></label>
        <button class="btn btn-violet w-100 py-2">Entrar <span aria-hidden="true">→</span></button>
    </form>
    @if(Route::has('register'))<p class="text-center small mt-4 mb-0 text-secondary">Ainda não tem acesso? <a href="{{ route('register') }}">Criar uma conta</a></p>@endif
</x-guest-layout>
