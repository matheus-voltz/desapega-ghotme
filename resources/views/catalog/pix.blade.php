@extends('layouts.app')

@section('title', 'Pagamento via Pix')

@section('content')
@php
    $isCartPayment = $isCartPayment ?? false;
    $purchaseLabel = $isCartPayment ? 'pedido' : 'item';
    $stage = match($payment?->status) {
        'RECEIVED' => 4,
        'CONFIRMED' => 2,
        default => 1,
    };
    $manualPixKey = $manualPixKey ?? null;
@endphp

<div class="container py-5" style="max-width: 760px">
    <a href="{{ $backUrl }}" class="back-link">← Voltar</a>

    <div class="card border-0 shadow-sm rounded-4 mt-3">
        <div class="card-body p-4 p-md-5 text-center">
            <div class="eyebrow mb-2">Pagamento via Pix</div>
            <h1 class="h3 fw-bold">{{ $payment ? 'Escaneie para pagar' : 'Quase lá!' }}</h1>
            <p class="text-secondary mb-1">{{ $title }}</p>
            <div class="display-6 fw-bold my-3">R$ {{ number_format($price, 2, ',', '.') }}</div>

            @if(session('error'))
                <div class="alert alert-danger text-start">{{ session('error') }}</div>
            @endif

            @if($manualPixKey)
                <div class="alert alert-warning text-start">Este é um Pix direto para o vendedor. A confirmação não é automática: guarde seu comprovante até combinar a entrega.</div>
                <p class="text-secondary mb-3">Copie a chave abaixo no aplicativo do seu banco e pague exatamente o valor informado.</p>
                <div class="mt-3 text-start"><label for="pix-copy-paste" class="form-label fw-semibold">Chave Pix do vendedor</label><div class="input-group"><input id="pix-copy-paste" type="text" class="form-control" value="{{ $manualPixKey }}" readonly><button type="button" class="btn btn-outline-violet" id="copy-pix">Copiar</button></div></div>
            @elseif($payment)
                <img src="data:image/png;base64,{{ $payment->pix_encoded_image }}" alt="QR Code Pix" class="img-fluid border rounded-4 p-2 bg-white my-3" style="max-width: 340px">
                <p class="text-secondary mb-2">Escaneie o QR Code pelo aplicativo do seu banco e confirme o valor.</p>

                <div class="mt-4 text-start">
                    <label for="pix-copy-paste" class="form-label fw-semibold">Pix copia e cola</label>
                    <div class="input-group">
                        <input id="pix-copy-paste" type="text" class="form-control" value="{{ $payment->pix_payload }}" readonly>
                        <button type="button" class="btn btn-outline-violet" id="copy-pix">Copiar</button>
                    </div>
                </div>

                @if($payment->pix_expires_at)
                    <p class="small text-secondary mt-3 mb-0">Este QR Code vence em {{ $payment->pix_expires_at->format('d/m/Y às H:i') }}.</p>
                @endif
            @else
                <p class="text-secondary mx-auto mb-4" style="max-width: 530px">Informe os dados abaixo para gerar um QR Code Pix exclusivo, no valor exato deste {{ $purchaseLabel }}.</p>

                <form method="POST" action="{{ $createPaymentUrl }}" class="text-start mx-auto" style="max-width: 480px">
                    @csrf
                    <div class="mb-3">
                        <label for="customer_name" class="form-label">Seu nome</label>
                        <input id="customer_name" name="customer_name" type="text" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name') }}" required autocomplete="name">
                        @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="customer_email" class="form-label">E-mail</label>
                        <input id="customer_email" name="customer_email" type="email" class="form-control @error('customer_email') is-invalid @enderror" value="{{ old('customer_email') }}" required autocomplete="email">
                        @error('customer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label for="customer_cpf_cnpj" class="form-label">CPF ou CNPJ</label>
                        <input id="customer_cpf_cnpj" name="customer_cpf_cnpj" type="text" inputmode="numeric" class="form-control @error('customer_cpf_cnpj') is-invalid @enderror" value="{{ old('customer_cpf_cnpj') }}" required autocomplete="off">
                        @error('customer_cpf_cnpj') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="customer_phone" class="form-label">Telefone</label>
                        <input id="customer_phone" name="customer_phone" type="tel" inputmode="numeric" class="form-control @error('customer_phone') is-invalid @enderror" value="{{ old('customer_phone') }}" required autocomplete="tel" data-digits>
                        @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="postal_code" class="form-label">CEP</label>
                        <input id="postal_code" name="postal_code" type="text" inputmode="numeric" class="form-control @error('postal_code') is-invalid @enderror" value="{{ old('postal_code') }}" required autocomplete="postal-code" data-digits>
                        @error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-5">
                            <label for="address_number" class="form-label">Número</label>
                            <input id="address_number" name="address_number" type="text" class="form-control @error('address_number') is-invalid @enderror" value="{{ old('address_number') }}" required autocomplete="address-line2">
                            @error('address_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-7">
                            <label for="address_complement" class="form-label">Complemento <span class="text-secondary fw-normal">(opcional)</span></label>
                            <input id="address_complement" name="address_complement" type="text" class="form-control @error('address_complement') is-invalid @enderror" value="{{ old('address_complement') }}" autocomplete="address-line2">
                            @error('address_complement') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <button type="submit" class="btn btn-violet w-100">Gerar Pix de R$ {{ number_format($price, 2, ',', '.') }}</button>
                </form>
            @endif

            @if($products->count() > 1)
                <div class="text-start mt-4 pt-3 border-top">
                    <div class="small text-secondary mb-2">Itens deste pedido</div>
                    @foreach($products as $product)
                        <div>{{ $product->name }}</div>
                    @endforeach
                </div>
            @endif

            <div class="small text-secondary mt-4">{{ $manualPixKey ? 'O vendedor confirma este pagamento manualmente.' : 'Os itens só são marcados como vendidos depois que o Asaas confirma o pagamento.' }}</div>
        </div>
    </div>

    @if(! $manualPixKey)<section class="order-journey mt-5" @if($payment) data-payment-status-url="{{ route('purchase.payment.status', $payment) }}" @endif>
        <div class="text-center mb-4">
            <div class="eyebrow mb-2">Depois do pagamento</div>
            <h2 class="h3 fw-bold mb-2">Acompanhe cada etapa do seu pedido</h2>
            <p class="text-secondary mb-0">Assim que o Pix for confirmado, o andamento é atualizado automaticamente.</p>
        </div>
        <div class="row g-3">
            <div class="col-6 col-lg-3"><article class="order-stage {{ $stage > 1 ? 'is-complete' : '' }} {{ $stage === 1 ? 'is-current' : '' }}" data-stage="1"><div class="order-stage-art art-payment"></div><div class="order-step">01</div><h3>Pagamento</h3><p>Aguardando a confirmação do Pix.</p></article></div>
            <div class="col-6 col-lg-3"><article class="order-stage {{ $stage > 2 ? 'is-complete' : '' }} {{ $stage === 2 ? 'is-current' : '' }}" data-stage="2"><div class="order-stage-art art-separate"></div><div class="order-step">02</div><h3>Separação</h3><p>O pagamento foi confirmado.</p></article></div>
            <div class="col-6 col-lg-3"><article class="order-stage {{ $stage > 3 ? 'is-complete' : '' }} {{ $stage === 3 ? 'is-current' : '' }}" data-stage="3"><div class="order-stage-art art-pack"></div><div class="order-step">03</div><h3>Embalagem</h3><p>Seu item segue para o preparo.</p></article></div>
            <div class="col-6 col-lg-3"><article class="order-stage {{ $stage === 4 ? 'is-current' : '' }}" data-stage="4"><div class="order-stage-art art-done"></div><div class="order-step">04</div><h3>Concluído</h3><p>Pagamento recebido e pedido concluído.</p></article></div>
        </div>
        <div class="order-info mt-3" data-order-info>{{ $stage === 4 ? '✦ Pagamento recebido. Pedido concluído!' : '✦ Aguardando a confirmação do Pix.' }}</div>
    </section>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('copy-pix')?.addEventListener('click', async (event) => {
        await navigator.clipboard.writeText(document.getElementById('pix-copy-paste').value);
        event.currentTarget.textContent = 'Copiado';
    });

    const journey = document.querySelector('[data-payment-status-url]');
    if (journey) {
        const updateJourney = (status) => {
            const stage = status === 'RECEIVED' ? 4 : (status === 'CONFIRMED' ? 2 : 1);
            journey.querySelectorAll('[data-stage]').forEach((element) => {
                const value = Number(element.dataset.stage);
                element.classList.toggle('is-current', value === stage);
                element.classList.toggle('is-complete', value < stage);
            });
            journey.querySelector('[data-order-info]').textContent = stage === 4
                ? '✦ Pagamento recebido. Pedido concluído!'
                : (stage === 2 ? '✦ Pagamento confirmado. Seu item foi reservado.' : '✦ Aguardando a confirmação do Pix.');

            return stage === 4;
        };

        const refreshStatus = async () => {
            const response = await fetch(journey.dataset.paymentStatusUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) return false;
            return updateJourney((await response.json()).status);
        };

        const timer = window.setInterval(async () => {
            try {
                if (await refreshStatus()) window.clearInterval(timer);
            } catch (_) {
                // A próxima consulta tenta novamente sem interromper a página de pagamento.
            }
        }, 5000);
    }
</script>
@endpush
