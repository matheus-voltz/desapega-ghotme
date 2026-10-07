@extends('layouts.app')

@section('title', $bundle->name)

@section('content')
@php
    $allAvailable = $bundle->products->every(fn ($product) => $product->status === 'available');
@endphp
<div class="container py-5">
    <a href="{{ route('catalog.index') }}" class="back-link">← Voltar ao catálogo</a>
    <div class="row g-5 mt-1">
        <div class="col-lg-6">
            @if($bundle->cover_image_path)
                <img src="{{ asset('storage/'.$bundle->cover_image_path) }}" class="img-fluid rounded-4 shadow-sm w-100 product-detail-cover" alt="{{ $bundle->name }}">
            @endif
        </div>
        <div class="col-lg-6">
            <div class="eyebrow">Combo</div>
            <h1 class="display-6 fw-bold">{{ $bundle->name }}</h1>
            @if($bundle->description)<p>{{ $bundle->description }}</p>@endif

            <ul class="list-group list-group-flush my-4">
                @foreach($bundle->products as $product)
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span>{{ $product->name }}</span>
                        <span class="text-secondary">R$ {{ number_format($product->pix_price, 2, ',', '.') }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="price-box p-4 rounded-4 mb-4">
                <div class="small text-secondary">Separados</div>
                <div class="text-decoration-line-through">R$ {{ number_format($bundle->individual_total, 2, ',', '.') }}</div>
                <div class="small text-secondary mt-2">Combo no Pix</div>
                <div class="display-6 fw-bold">R$ {{ number_format($bundle->pix_price, 2, ',', '.') }}</div>
                @if($bundle->savings > 0)
                    <div class="text-success fw-semibold">Economize R$ {{ number_format($bundle->savings, 2, ',', '.') }}</div>
                @endif
            </div>

            @if($allAvailable)
                <a href="{{ route('purchase.bundle.pix', $bundle) }}" class="btn btn-violet btn-lg w-100">Quero este combo no Pix</a>
                <div class="small text-secondary mt-2">O QR Code do Asaas será exibido para pagamento.</div>
            @elseif(!$allAvailable)
                <div class="alert alert-secondary">Um ou mais itens deste combo não estão mais disponíveis.</div>
            @endif
        </div>
    </div>
    @if($bundle->products->isNotEmpty())
        <section class="mt-5 pt-4 border-top">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4"><div><div class="eyebrow mb-2">Prefere escolher separado?</div><h2 class="section-heading mb-0">Também à venda separadamente</h2></div><span class="small text-secondary">Cada item também pode ser comprado individualmente</span></div>
            <div class="row g-3 g-lg-4">@include('catalog._product-cards', ['products' => $bundle->products])</div>
        </section>
    @endif
</div>
@endsection
