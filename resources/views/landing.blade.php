@extends('layouts.app')

@section('title', 'Desapega.ghotme — venda e encontre novos itens')

@section('content')
<section class="market-hero">
    <div class="container py-5 py-lg-6">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="hero-pill">Marketplace gratuito para desapegar</span>
                <h1 class="market-title mt-4">O que não faz mais sentido para você pode fazer sentido para alguém.</h1>
                <p class="market-lead mt-4">Publique seus itens, encontre boas oportunidades e dê uma nova história para aquilo que está parado.</p>
                <div class="d-flex flex-wrap gap-3 mt-4"><a href="{{ route('register') }}" class="btn btn-violet btn-lg">Comece a vender</a></div>
                <div class="d-flex flex-wrap gap-3 mt-4 small text-secondary"><span>✓ Sem mensalidade</span><span>✓ Sem comissão da plataforma</span><span>✓ Você define o preço</span></div>
            </div>
            <div class="col-lg-5"><div class="landing-orbit"><div class="landing-card landing-card-one"><span>📚</span><strong>Livros</strong><small>novas histórias</small></div><div class="landing-card landing-card-two"><span>🎧</span><strong>Eletrônicos</strong><small>bem cuidados</small></div><div class="landing-card landing-card-three"><span>🏠</span><strong>Para sua casa</strong><small>um novo lar</small></div><div class="landing-center"><span class="brand-mark">D</span><strong>Desapega</strong><small>feito para circular</small></div></div></div>
        </div>
    </div>
</section>

<section class="container py-5 py-lg-6"><div class="text-center mb-5"><span class="eyebrow">Como funciona</span><h2 class="section-heading mt-2">Desapegar ficou simples</h2><p class="text-secondary">Uma experiência leve para quem vende e para quem compra.</p></div><div class="row g-4"><div class="col-md-4"><article class="landing-step"><span>01</span><h3>Crie sua conta</h3><p>Escolha se quer comprar ou vender e monte seu perfil em poucos minutos.</p></article></div><div class="col-md-4"><article class="landing-step"><span>02</span><h3>Publique ou encontre</h3><p>Apresente seus itens ou navegue pelo catálogo em busca de algo especial.</p></article></div><div class="col-md-4"><article class="landing-step"><span>03</span><h3>Combine e conclua</h3><p>Negocie a entrega e pague com segurança. No cartão, aplica-se apenas a taxa do Asaas.</p></article></div></div></section>

<section class="landing-free"><div class="container py-5"><div class="row align-items-center g-4"><div class="col-lg-8"><span class="eyebrow">Feito para todo mundo</span><h2 class="section-heading mt-2">Venda sem pagar para anunciar.</h2><p class="mb-0 text-secondary">O Desapega.ghotme não cobra mensalidade nem comissão pela venda. A única cobrança adicional pode ser a taxa do meio de pagamento escolhido, como as tarifas do Asaas no cartão.</p></div><div class="col-lg-4 text-lg-end"><a href="{{ route('register') }}" class="btn btn-violet">Criar minha conta →</a></div></div></div></section>
@endsection
