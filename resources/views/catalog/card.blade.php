@extends('layouts.app')

@section('title', 'Pagamento por cartão')

@section('content')
@php
    $stage = match($payment?->status) {
        'RECEIVED' => 4,
        'CONFIRMED' => 2,
        default => 1,
    };
    $paymentConfirmed = $payment?->isConfirmed() ?? false;
    $paymentReceived = $payment?->isPaid() ?? false;
    $maximumInstallments = count($installmentOptions);
@endphp

<div class="container py-5 card-checkout-shell">
    <a href="{{ $backUrl }}" class="back-link">← Voltar</a>
    <div class="row g-4 mt-1 align-items-start">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden"><div class="card-body p-4 p-md-5">
                <div class="eyebrow mb-2">Pagamento seguro</div>
                <h1 class="h3 fw-bold mb-2">Pague com cartão</h1>
                <p class="text-secondary mb-4">Escolha em quantas vezes quer pagar. Seus dados seguem criptografados para o Asaas e não ficam salvos no site.</p>

                @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

                @if($payment)
                    <div class="payment-result payment-result-{{ $paymentConfirmed ? 'success' : 'pending' }}" data-card-payment-result>
                        <div class="payment-result-icon" data-card-payment-icon>{{ $paymentConfirmed ? '✓' : '◷' }}</div>
                        <div>
                            <h2 class="h5 fw-bold mb-1" data-card-payment-title>{{ $paymentConfirmed ? 'Pagamento aprovado!' : 'Pagamento em análise' }}</h2>
                            <p class="mb-0 text-secondary" data-card-payment-copy>{{ $paymentReceived ? 'Recebemos seu pagamento. O pedido já está concluído.' : ($paymentConfirmed ? 'O cartão foi aprovado. Seu pedido segue para preparação.' : 'Estamos aguardando a confirmação final do cartão.') }}</p>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ $createPaymentUrl }}" data-card-checkout>
                        @csrf
                        <div class="checkout-section-title"><span>1</span> Dados do comprador</div>
                        <div class="row g-3 mb-4">
                            <div class="col-12"><label for="customer_name" class="form-label">Nome completo</label><input id="customer_name" name="customer_name" type="text" class="form-control @error('customer_name') is-invalid @enderror" value="{{ old('customer_name') }}" required autocomplete="name">@error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                            <div class="col-md-7"><label for="customer_email" class="form-label">E-mail</label><input id="customer_email" name="customer_email" type="email" class="form-control @error('customer_email') is-invalid @enderror" value="{{ old('customer_email') }}" required autocomplete="email">@error('customer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                            <div class="col-md-5"><label for="customer_cpf_cnpj" class="form-label">CPF ou CNPJ</label><input id="customer_cpf_cnpj" name="customer_cpf_cnpj" type="text" inputmode="numeric" class="form-control @error('customer_cpf_cnpj') is-invalid @enderror" value="{{ old('customer_cpf_cnpj') }}" required autocomplete="off" data-digits>@error('customer_cpf_cnpj') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                            <div class="col-md-5"><label for="customer_phone" class="form-label">Celular com DDD</label><input id="customer_phone" name="customer_phone" type="tel" inputmode="numeric" class="form-control @error('customer_phone') is-invalid @enderror" value="{{ old('customer_phone') }}" required autocomplete="tel" data-digits>@error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                            <div class="col-md-4"><label for="postal_code" class="form-label">CEP</label><input id="postal_code" name="postal_code" type="text" inputmode="numeric" class="form-control @error('postal_code') is-invalid @enderror" value="{{ old('postal_code') }}" required autocomplete="postal-code" data-digits>@error('postal_code') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                            <div class="col-md-3"><label for="address_number" class="form-label">Número</label><input id="address_number" name="address_number" type="text" class="form-control @error('address_number') is-invalid @enderror" value="{{ old('address_number') }}" required autocomplete="address-line2">@error('address_number') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                            <div class="col-12"><label for="address_complement" class="form-label">Complemento <span class="text-secondary fw-normal">(opcional)</span></label><input id="address_complement" name="address_complement" type="text" class="form-control @error('address_complement') is-invalid @enderror" value="{{ old('address_complement') }}" autocomplete="address-line2">@error('address_complement') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                        </div>

                        <div class="checkout-section-title"><span>2</span> Dados do cartão</div>
                        <div class="credit-card-preview mb-4" data-credit-card><div class="credit-card-inner"><div class="credit-card-front"><div class="credit-card-top"><span class="credit-card-chip"></span><span class="credit-card-brand">CRÉDITO</span></div><div class="credit-card-number" data-card-number>•••• •••• •••• ••••</div><div class="credit-card-bottom"><span><small>titular</small><strong data-card-holder>SEU NOME</strong></span><span><small>validade</small><strong data-card-expiry>MM/AA</strong></span></div></div><div class="credit-card-back"><div class="credit-card-stripe"></div><div class="credit-card-cvv"><span>CVV</span><strong data-card-cvv>•••</strong></div><p>Para sua segurança, o código não é armazenado.</p></div></div></div>
                        <div class="row g-3">
                            <div class="col-12"><label for="credit_card_holder_name" class="form-label">Nome impresso no cartão</label><input id="credit_card_holder_name" name="credit_card_holder_name" type="text" class="form-control @error('credit_card_holder_name') is-invalid @enderror" value="{{ old('credit_card_holder_name') }}" required autocomplete="cc-name" data-card-holder-input>@error('credit_card_holder_name') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                            <div class="col-12"><label for="credit_card_number" class="form-label">Número do cartão</label><input id="credit_card_number" name="credit_card_number" type="text" inputmode="numeric" class="form-control @error('credit_card_number') is-invalid @enderror" required autocomplete="cc-number" maxlength="23" data-card-number-input>@error('credit_card_number') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                            <div class="col-sm-4"><label for="credit_card_expiry_month" class="form-label">Mês</label><select id="credit_card_expiry_month" name="credit_card_expiry_month" class="form-select @error('credit_card_expiry_month') is-invalid @enderror" required autocomplete="cc-exp-month" data-card-month-input><option value="">MM</option>@for($month = 1; $month <= 12; $month++) <option value="{{ $month }}" @selected((int) old('credit_card_expiry_month') === $month)>{{ str_pad((string) $month, 2, '0', STR_PAD_LEFT) }}</option> @endfor</select>@error('credit_card_expiry_month') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                            <div class="col-sm-4"><label for="credit_card_expiry_year" class="form-label">Ano</label><select id="credit_card_expiry_year" name="credit_card_expiry_year" class="form-select @error('credit_card_expiry_year') is-invalid @enderror" required autocomplete="cc-exp-year" data-card-year-input><option value="">AAAA</option>@for($year = now()->year; $year <= now()->year + 20; $year++) <option value="{{ $year }}" @selected((int) old('credit_card_expiry_year') === $year)>{{ $year }}</option> @endfor</select>@error('credit_card_expiry_year') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                            <div class="col-sm-4"><label for="credit_card_cvv" class="form-label">CVV</label><input id="credit_card_cvv" name="credit_card_cvv" type="password" inputmode="numeric" class="form-control @error('credit_card_cvv') is-invalid @enderror" required autocomplete="cc-csc" maxlength="4" data-card-cvv-input data-digits>@error('credit_card_cvv') <div class="invalid-feedback">{{ $message }}</div> @enderror</div>
                        </div>
                        <div class="checkout-section-title mt-4"><span>3</span> Parcelamento</div>
                        <label for="installments" class="form-label">Escolha o número de parcelas</label><select id="installments" name="installments" class="form-select form-select-lg @error('installments') is-invalid @enderror" required data-installments>@foreach($installmentOptions as $option) <option value="{{ $option['count'] }}" @selected((int) old('installments', 1) === $option['count'])>{{ $option['count'] }}x de R$ {{ number_format($option['installment'], 2, ',', '.') }}{{ $option['has_fee'] ? ' · total R$ '.number_format($option['total'], 2, ',', '.') : ' sem juros' }}</option> @endforeach</select>@error('installments') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if($maximumInstallments <= 5)
                            <p class="small text-secondary mt-2 mb-0">Para compras de até R$ 100,00, o parcelamento vai em até 5x sem juros.</p>
                        @else
                            <p class="small text-secondary mt-2 mb-0">Até 5x sem juros. De 6x a 12x: acréscimo de 3,49% + R$ 0,49 no total.</p>
                        @endif
                        <button type="submit" class="btn btn-violet btn-lg w-100 mt-4" data-card-submit>Pagar com cartão</button><p class="checkout-security-note mb-0 mt-3">🔒 Conexão segura. O número e o CVV do cartão não são gravados no Desapega.ghotme.</p>
                    </form>
                @endif
            </div></div>
        </div>
        <aside class="col-lg-5"><div class="checkout-summary rounded-4"><div class="eyebrow mb-2">Resumo do pedido</div><h2 class="h5 fw-bold">{{ $title }}</h2><div class="checkout-summary-row"><span>Valor original</span><strong>R$ {{ number_format($price, 2, ',', '.') }}</strong></div><div class="checkout-summary-installment" data-installment-preview>1x de R$ {{ number_format($price, 2, ',', '.') }} sem juros</div><div class="small text-secondary mt-3">{{ $maximumInstallments <= 5 ? 'Este item pode ser parcelado em até 5x sem juros.' : 'Até 5x não há acréscimo. A partir de 6x, a taxa é mostrada antes do pagamento.' }}</div></div></aside>
    </div>

    @if($payment)
        <section class="order-journey mt-5" data-payment-status-url="{{ route('purchase.payment.status', $payment) }}"><div class="text-center mb-4"><div class="eyebrow mb-2">Depois do pagamento</div><h2 class="h3 fw-bold mb-2">Acompanhe cada etapa do seu pedido</h2><p class="text-secondary mb-0">O status é atualizado automaticamente após a confirmação do cartão.</p></div><div class="row g-3"><div class="col-6 col-lg-3"><article class="order-stage {{ $stage > 1 ? 'is-complete' : '' }} {{ $stage === 1 ? 'is-current' : '' }}" data-stage="1"><div class="order-stage-art art-payment"></div><div class="order-step">01</div><h3>Pagamento</h3><p>Aguardando a confirmação do cartão.</p></article></div><div class="col-6 col-lg-3"><article class="order-stage {{ $stage > 2 ? 'is-complete' : '' }} {{ $stage === 2 ? 'is-current' : '' }}" data-stage="2"><div class="order-stage-art art-separate"></div><div class="order-step">02</div><h3>Separação</h3><p>O pagamento foi confirmado.</p></article></div><div class="col-6 col-lg-3"><article class="order-stage {{ $stage > 3 ? 'is-complete' : '' }} {{ $stage === 3 ? 'is-current' : '' }}" data-stage="3"><div class="order-stage-art art-pack"></div><div class="order-step">03</div><h3>Embalagem</h3><p>Seu item segue para o preparo.</p></article></div><div class="col-6 col-lg-3"><article class="order-stage {{ $stage === 4 ? 'is-current' : '' }}" data-stage="4"><div class="order-stage-art art-done"></div><div class="order-step">04</div><h3>Concluído</h3><p>Pagamento recebido e pedido concluído.</p></article></div></div><div class="order-info mt-3" data-order-info>{{ $stage === 4 ? '✦ Pagamento recebido. Pedido concluído!' : '✦ Aguardando a confirmação do cartão.' }}</div></section>
    @endif
</div>
@endsection

@push('scripts')
<script>
    const checkout = document.querySelector('[data-card-checkout]');
    const installmentOptions = {{ Illuminate\Support\Js::from($installmentOptions) }};
    if (checkout) {
        const card = document.querySelector('[data-credit-card]'); const number = document.querySelector('[data-card-number-input]'); const holder = document.querySelector('[data-card-holder-input]'); const month = document.querySelector('[data-card-month-input]'); const year = document.querySelector('[data-card-year-input]'); const cvv = document.querySelector('[data-card-cvv-input]'); const installments = document.querySelector('[data-installments]'); const money = (value) => new Intl.NumberFormat('pt-BR', { style:'currency', currency:'BRL' }).format(value);
        document.querySelectorAll('[data-digits]').forEach((input) => input.addEventListener('input', () => input.value = input.value.replace(/\D/g, '')));
        number.addEventListener('input', () => { const digits = number.value.replace(/\D/g, '').slice(0, 19); number.value = digits.replace(/(.{4})/g, '$1 ').trim(); document.querySelector('[data-card-number]').textContent = number.value || '•••• •••• •••• ••••'; });
        holder.addEventListener('input', () => document.querySelector('[data-card-holder]').textContent = (holder.value || 'SEU NOME').toUpperCase().slice(0, 26));
        const expiry = () => document.querySelector('[data-card-expiry]').textContent = `${month.value ? month.value.padStart(2, '0') : 'MM'}/${year.value ? year.value.slice(-2) : 'AA'}`; month.addEventListener('change', expiry); year.addEventListener('change', expiry);
        cvv.addEventListener('focus', () => card.classList.add('is-flipped')); cvv.addEventListener('blur', () => card.classList.remove('is-flipped')); cvv.addEventListener('input', () => document.querySelector('[data-card-cvv]').textContent = cvv.value ? '•'.repeat(cvv.value.length) : '•••');
        const updateInstallments = () => { const choice = installmentOptions.find((option) => option.count === Number(installments.value || 1)); document.querySelector('[data-installment-preview]').textContent = choice.has_fee ? `${choice.count}x de ${money(choice.installment)} · total ${money(choice.total)} (taxa ${money(choice.fee)})` : `${choice.count}x de ${money(choice.installment)} sem juros`; }; installments.addEventListener('change', updateInstallments); updateInstallments();
        checkout.addEventListener('submit', () => { const submit = document.querySelector('[data-card-submit]'); submit.disabled = true; submit.textContent = 'Processando pagamento...'; });
    }
    const journey = document.querySelector('[data-payment-status-url]');
    const paymentResult = document.querySelector('[data-card-payment-result]');

    const updatePaymentResult = (status) => {
        if (!paymentResult) return;

        const confirmed = ['CONFIRMED', 'RECEIVED'].includes(status);
        const received = status === 'RECEIVED';

        paymentResult.classList.toggle('payment-result-success', confirmed);
        paymentResult.classList.toggle('payment-result-pending', !confirmed);
        paymentResult.querySelector('[data-card-payment-icon]').textContent = confirmed ? '✓' : '◷';
        paymentResult.querySelector('[data-card-payment-title]').textContent = confirmed ? 'Pagamento aprovado!' : 'Pagamento em análise';
        paymentResult.querySelector('[data-card-payment-copy]').textContent = received
            ? 'Recebemos seu pagamento. O pedido já está concluído.'
            : (confirmed ? 'O cartão foi aprovado. Seu pedido segue para preparação.' : 'Estamos aguardando a confirmação final do cartão.');
    };

    if (journey) {
        const refresh = async () => {
            try {
                const response = await fetch(journey.dataset.paymentStatusUrl, { headers:{ Accept:'application/json' } });
                if (!response.ok) return false;

                const status = (await response.json()).status;
                const stage = status === 'RECEIVED' ? 4 : (status === 'CONFIRMED' ? 2 : 1);
                journey.querySelectorAll('[data-stage]').forEach((element) => {
                    const value = Number(element.dataset.stage);
                    element.classList.toggle('is-current', value === stage);
                    element.classList.toggle('is-complete', value < stage);
                });
                journey.querySelector('[data-order-info]').textContent = stage === 4
                    ? '✦ Pagamento recebido. Pedido concluído!'
                    : (stage === 2 ? '✦ Pagamento confirmado. Seu item foi reservado.' : '✦ Aguardando a confirmação do cartão.');
                updatePaymentResult(status);

                return ['CONFIRMED', 'RECEIVED'].includes(status);
            } catch (_) {
                return false;
            }
        };

        const timer = window.setInterval(async () => {
            if (await refresh()) window.clearInterval(timer);
        }, 5000);
    }
</script>
@endpush
