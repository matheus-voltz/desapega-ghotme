<x-guest-layout>
    <div class="auth-kicker">Primeiro acesso</div>
    <h1>Crie sua conta.</h1>
    <p class="auth-subtitle">Escolha como você vai usar o Desapega.ghotme.</p>
    @if(session('error'))<div class="alert alert-danger small">{{ session('error') }}</div>@endif
    @if(filled(config('services.google.client_id')))
        <a class="btn btn-outline-secondary w-100 py-2 mt-3" href="{{ route('google.redirect') }}">Continuar com Google</a>
        <div class="d-flex align-items-center gap-2 my-4 text-secondary small"><span class="flex-grow-1 border-top"></span><span>ou cadastre com e-mail</span><span class="flex-grow-1 border-top"></span></div>
    @endif
    <form method="POST" action="{{ route('register') }}" class="mt-4">
        @csrf
        <div class="mb-3"><label for="name" class="form-label">Seu nome</label><input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">@error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="email" class="form-label">E-mail</label><input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">@error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <div class="mb-4"><label class="form-label">Quero criar uma conta para</label><div class="row g-2"><div class="col-6"><input class="btn-check" type="radio" name="account_type" id="account-buyer" value="buyer" @checked(old('account_type', 'buyer') === 'buyer')><label class="btn btn-outline-violet w-100 py-3" for="account-buyer">Comprar itens</label></div><div class="col-6"><input class="btn-check" type="radio" name="account_type" id="account-seller" value="seller" @checked(old('account_type') === 'seller')><label class="btn btn-outline-violet w-100 py-3" for="account-seller">Vender itens</label></div></div>@error('account_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="password" class="form-label">Crie uma senha</label><input id="password" class="form-control" type="password" name="password" required autocomplete="new-password">@error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <div class="mb-4"><label for="password_confirmation" class="form-label">Confirme a senha</label><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password"></div>
        <div class="form-check mb-4"><input class="form-check-input @error('accept_legal') is-invalid @enderror" type="checkbox" name="accept_legal" value="1" id="accept_legal" @checked(old('accept_legal')) required><label class="form-check-label small" for="accept_legal">Li e aceito os <a href="{{ route('legal.terms') }}" target="_blank">Termos de Uso</a>, a <a href="{{ route('legal.privacy') }}" target="_blank">Política de Privacidade</a> e as regras de <a href="{{ route('legal.prohibited-items') }}" target="_blank">itens proibidos</a>.</label>@error('accept_legal')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <button class="btn btn-violet w-100 py-2">Criar conta <span aria-hidden="true">→</span></button>
    </form>
    <p class="text-center small mt-4 mb-0 text-secondary">Já tem acesso? <a href="{{ route('login') }}">Entrar</a></p>
</x-guest-layout>
