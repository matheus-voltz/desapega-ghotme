<x-guest-layout>
    <div class="auth-kicker">Cadastro com Google</div>
    <h1>Quase pronto.</h1>
    <p class="auth-subtitle">Você entrou como <strong>{{ $identity['email'] }}</strong>. Só falta escolher como quer usar o Desapega.ghotme.</p>
    <form method="POST" action="{{ route('google.complete.store') }}" class="mt-4">
        @csrf
        <div class="mb-4"><label class="form-label">Quero criar uma conta para</label><div class="row g-2"><div class="col-6"><input class="btn-check" type="radio" name="account_type" id="account-buyer" value="buyer" @checked(old('account_type', 'buyer') === 'buyer')><label class="btn btn-outline-violet w-100 py-3" for="account-buyer">Comprar itens</label></div><div class="col-6"><input class="btn-check" type="radio" name="account_type" id="account-seller" value="seller" @checked(old('account_type') === 'seller')><label class="btn btn-outline-violet w-100 py-3" for="account-seller">Vender itens</label></div></div>@error('account_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <div class="form-check mb-4"><input class="form-check-input @error('accept_legal') is-invalid @enderror" type="checkbox" name="accept_legal" value="1" id="accept_legal" @checked(old('accept_legal')) required><label class="form-check-label small" for="accept_legal">Li e aceito os <a href="{{ route('legal.terms') }}" target="_blank">Termos de Uso</a>, a <a href="{{ route('legal.privacy') }}" target="_blank">Política de Privacidade</a> e as regras de <a href="{{ route('legal.prohibited-items') }}" target="_blank">itens proibidos</a>.</label>@error('accept_legal')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
        <button class="btn btn-violet w-100 py-2">Concluir cadastro</button>
    </form>
</x-guest-layout>
