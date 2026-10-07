@extends('layouts.app')

@section('title', 'Minha conta')

@section('content')
<div class="container py-5">
    <div class="mb-4"><div class="eyebrow mb-2">Configurações da conta</div><h1 class="h2 fw-bold mb-1">Minha conta</h1><p class="text-secondary mb-0">Atualize seus dados, acesso e preferências da sua conta.</p></div>

    <div class="row g-4">
        <div class="col-lg-7">
            <section class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4"><div><h2 class="h5 fw-bold mb-1">Dados cadastrais</h2><p class="text-secondary small mb-0">Estas informações identificam você na plataforma.</p></div><span class="status status-available">{{ $user->isSeller() ? 'Vendedor' : 'Comprador' }}</span></div>
                @if(session('status') === 'profile-updated')<div class="alert alert-success">Dados atualizados.</div>@endif
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3"><label class="form-label" for="name">Nome</label><input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autocomplete="name">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="mb-4"><label class="form-label" for="email">E-mail</label><input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <button class="btn btn-violet">Salvar dados</button>
                </form>
            </section>

            <section class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h2 class="h5 fw-bold mb-1">Senha</h2><p class="text-secondary small mb-4">Use uma senha longa e exclusiva para proteger sua conta.</p>
                @if(session('status') === 'password-updated')<div class="alert alert-success">Senha alterada.</div>@endif
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3"><label class="form-label" for="current_password">Senha atual</label><input id="current_password" name="current_password" type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">@error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="row g-3"><div class="col-md-6"><label class="form-label" for="password">Nova senha</label><input id="password" name="password" type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">@error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-6"><label class="form-label" for="password_confirmation">Confirme a nova senha</label><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password"></div></div>
                    <button class="btn btn-outline-violet mt-4">Alterar senha</button>
                </form>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="card border-0 shadow-sm rounded-4 p-4 mb-4"><h2 class="h5 fw-bold mb-2">Tipo de conta</h2><p class="text-secondary small mb-3">Sua conta está configurada para <strong>{{ $user->isSeller() ? 'vender e gerenciar seus itens' : 'comprar e montar sua sacola' }}</strong>.</p>@if($user->isSeller())<a class="btn btn-outline-violet w-100" href="{{ route('seller.settings.edit') }}">Configurações de vendedor</a>@else<a class="btn btn-outline-violet w-100" href="{{ route('catalog.index') }}">Explorar itens</a>@endif</section>
            <section class="card border-0 shadow-sm rounded-4 p-4 mb-4"><h2 class="h5 fw-bold mb-2">Forma de acesso</h2>@if($user->google_id)<p class="text-secondary small mb-0">Sua conta está vinculada ao Google. Você também pode entrar usando seu e-mail e senha.</p>@else<p class="text-secondary small mb-0">Você entra usando seu e-mail e senha.</p>@endif</section>
            <section class="card border border-danger-subtle rounded-4 p-4"><h2 class="h5 fw-bold text-danger mb-2">Excluir conta</h2><p class="text-secondary small">Esta ação é permanente e remove seus dados de acesso. Para confirmar, informe sua senha atual.</p><details><summary class="btn btn-outline-danger">Quero excluir minha conta</summary><form method="POST" action="{{ route('profile.destroy') }}" class="mt-3">@csrf @method('DELETE')<label class="form-label" for="delete_password">Senha atual</label><input id="delete_password" name="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" autocomplete="current-password">@error('password', 'userDeletion')<div class="invalid-feedback">{{ $message }}</div>@enderror<button class="btn btn-danger mt-3">Excluir conta permanentemente</button></form></details></section>
        </div>
    </div>
</div>
@endsection
