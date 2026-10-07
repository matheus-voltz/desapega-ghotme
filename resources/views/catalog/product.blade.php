@extends('layouts.app')

@section('title', $product->name)

@section('content')
@php
    $sellerUsesAsaas = $product->seller_id === null || (bool) $product->seller?->sellerSetting?->asaas_api_key;
    $sellerHasManualPix = ! $sellerUsesAsaas && (bool) $product->seller?->sellerSetting?->pix_key;
@endphp
<div class="container py-5">
    <a href="{{ route('catalog.index') }}" class="back-link">← Voltar ao catálogo</a>
    <div class="row g-5 mt-1">
        <div class="col-lg-6">
            @if($product->image_urls)
                <img src="{{ $product->primary_image_url }}" class="img-fluid rounded-4 shadow-sm w-100 product-detail-cover" alt="{{ $product->name }}" data-product-main-image>
                @if(count($product->image_urls) > 1)
                    <div class="product-gallery-thumbnails mt-3" aria-label="Outras fotos do item">
                        @foreach($product->image_urls as $imageUrl)
                            <button type="button" class="product-gallery-thumbnail {{ $loop->first ? 'is-active' : '' }}" data-product-image="{{ $imageUrl }}" aria-label="Ver foto {{ $loop->iteration }} de {{ count($product->image_urls) }}">
                                <img src="{{ $imageUrl }}" alt="{{ $product->name }} — foto {{ $loop->iteration }}">
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="product-detail-cover placeholder-cover rounded-4 d-flex align-items-center justify-content-center">Sem foto</div>
            @endif
        </div>
        <div class="col-lg-6">
            <div class="d-flex gap-2 align-items-center mb-2">
                <span class="status status-{{ $product->status }}">{{ $product->status_label }}</span>
                @if($product->category)<span class="small text-secondary">{{ $product->category }}</span>@endif
            </div>
            <h1 class="display-6 fw-bold">{{ $product->name }}</h1>
            <button type="button" class="btn btn-sm btn-outline-violet mb-3" data-share-product>↗ Compartilhar item</button>
            @if($product->condition)<div class="text-secondary mb-3">Estado: {{ $product->condition }}</div>@endif
            @if($product->description)<p class="detail-description">{!! nl2br(e($product->description)) !!}</p>@endif

            <div class="price-box p-4 rounded-4 my-4">
                <div class="small text-secondary">Preço no Pix</div>
                <div class="display-6 fw-bold mb-1">R$ {{ number_format($product->pix_price, 2, ',', '.') }}</div>
                @if($product->marketplace_price)
                    <div class="text-secondary">Cartão: R$ {{ number_format($product->marketplace_price, 2, ',', '.') }}</div>
                @endif
                @if($product->savings > 0)
                    <div class="text-success fw-semibold">Economize R$ {{ number_format($product->savings, 2, ',', '.') }} no Pix</div>
                @endif
            </div>

            @if($product->status === 'available')
                <div class="d-grid gap-2">
                    @auth
                        <form method="POST" action="{{ route('cart.items.store', $product) }}" class="d-grid">
                            @csrf
                            <button type="submit" class="btn btn-outline-violet btn-lg">Adicionar à sacola</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-violet btn-lg">Entrar para adicionar à sacola</a>
                    @endauth
                    @if($sellerUsesAsaas || $sellerHasManualPix)<a href="{{ route('purchase.product.pix', $product) }}" class="btn btn-violet btn-lg">Comprar no Pix</a>@endif
                    @if($product->marketplace_price !== null && $sellerUsesAsaas)
                        <a href="{{ route('purchase.product.card', $product) }}" class="btn btn-outline-violet btn-lg">Pagar com cartão em até 12x</a>
                    @endif
                    @if($product->marketplace_url)
                        <form method="POST" action="{{ route('purchase.product.shopee', $product) }}" target="_blank" class="d-grid">
                            @csrf
                            <button type="submit" class="btn btn-outline-violet btn-lg">Comprar pela Shopee</button>
                        </form>
                    @endif
                </div>
                <div class="small text-secondary mt-2">Adicione outros itens à sacola ou compre este item agora pelo Pix.</div>
            @else
                <div class="alert alert-secondary">Este item está {{ strtolower($product->status_label) }}.</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const productMainImage = document.querySelector('[data-product-main-image]');

    document.querySelectorAll('[data-product-image]').forEach((button) => {
        button.addEventListener('click', () => {
            productMainImage.src = button.dataset.productImage;
            document.querySelectorAll('[data-product-image]').forEach((item) => item.classList.toggle('is-active', item === button));
        });
    });

    document.querySelector('[data-share-product]')?.addEventListener('click', async (event) => {
        const shareData = { title: @json($product->name), text: 'Confira este item no Desapega.ghotme', url: window.location.href };
        try {
            if (navigator.share) {
                await navigator.share(shareData);
            } else {
                await navigator.clipboard.writeText(window.location.href);
                event.currentTarget.textContent = 'Link copiado';
            }
        } catch (_) {
            // O usuário pode fechar a janela nativa de compartilhamento sem concluir.
        }
    });
</script>
@endpush
