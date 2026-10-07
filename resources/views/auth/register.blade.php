<x-guest-layout>
    <div class="auth-kicker">Primeiro acesso</div>
    <h1>Crie sua conta.</h1>
    <p class="auth-subtitle">Salve seus itens na sacola e feche uma compra com um único Pix.</p>
    <form method="POST" action="{{ route('register') }}" class="mt-4">
        @csrf
        <div class="mb-3"><label for="name" class="form-label">Seu nome</label><input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">@error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="email" class="form-label">E-mail</label><input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">@error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <div class="mb-3"><label for="password" class="form-label">Crie uma senha</label><input id="password" class="form-control" type="password" name="password" required autocomplete="new-password">@error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <div class="mb-4"><label for="password_confirmation" class="form-label">Confirme a senha</label><input id="password_confirmation" class="form-control" type="password" name="password_confirmation" required autocomplete="new-password"></div>
        <button class="btn btn-violet w-100 py-2">Criar conta <span aria-hidden="true">→</span></button>
    </form>
    <p class="text-center small mt-4 mb-0 text-secondary">Já tem acesso? <a href="{{ route('login') }}">Entrar</a></p>
</x-guest-layout>
