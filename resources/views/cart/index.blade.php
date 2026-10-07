@extends('layouts.app')

@section('title', 'Minha sacola')

@section('content')
<div class="container py-5" style="max-width: 980px">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-end gap-3 mb-4">
        <div>
            <div class="eyebrow mb-2">Minha sacola</div>
            <h1 class="display-6 fw-bold mb-1">Seus itens selecionados</h1>
            <p class="text-secondary mb-0">Cada item é único e pode ser comprado uma única vez.</p>
        </div>
        <a href="{{ route('catalog.index') }}#catalogo" class="btn btn-outline-violet">Continuar comprando</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($cart->items->isEmpty())
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-5 text-center">
                <h2 class="h4 fw-bold">Sua sacola está vazia</h2>
                <p class="text-secondary mb-4">Escolha os itens que quer levar para ver o total aqui.</p>
                <a href="{{ route('catalog.index') }}#catalogo" class="btn btn-violet">Ver itens à venda</a>
            </div>
        </div>
    @else
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-0">
                        @foreach($cart->items as $cartItem)
                            <article class="d-flex gap-3 p-3 {{ $loop->last ? '' : 'border-bottom' }}">
                                @if($cartItem->product->primary_image_url)
                                    <img src="{{ $cartItem->product->primary_image_url }}" alt="{{ $cartItem->product->name }}" class="rounded-3 object-fit-cover" width="92" height="92">
                                @else
                                    <div class="rounded-3 placeholder-cover d-flex align-items-center justify-content-center flex-shrink-0" style="width: 92px; height: 92px">Sem foto</div>
                                @endif
                                <div class="flex-grow-1 min-width-0">
                                    <div class="small text-secondary">{{ $cartItem->product->category ?: 'Outros' }}</div>
                                    <h2 class="h6 fw-bold mb-1">{{ $cartItem->product->name }}</h2>
                                    <div class="fw-bold text-violet">R$ {{ number_format($cartItem->product->pix_price, 2, ',', '.') }}</div>
                                    @if($cartItem->product->status !== 'available')
                                        <div class="small text-danger mt-1">Este item não está mais disponível.</div>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('cart.items.destroy', $cartItem) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-link text-secondary" type="submit">Remover</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <aside class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold">Resumo da compra</h2>
                        <div class="d-flex justify-content-between text-secondary mt-3"><span>{{ $cart->items->count() }} item(ns)</span><span>Escolha como pagar</span></div>
                        <div class="d-flex justify-content-between align-items-end border-top mt-3 pt-3">
                            <span class="fw-semibold">Total</span>
                            <span class="h3 fw-bold mb-0">R$ {{ number_format($total, 2, ',', '.') }}</span>
                        </div>
                        @if($canCheckout)
                            <a href="{{ route('cart.pix') }}" class="btn btn-violet w-100 mt-4">Pagar todos no Pix</a>
                            @if($canPayByCard)
                                <a href="{{ route('cart.card') }}" class="btn btn-outline-violet w-100 mt-2">Pagar a sacola com cartão</a>
                                <p class="small text-secondary text-center mb-0 mt-3">Pix usa o valor à vista. No cartão, use o preço total de cada item e parcele em até 12x.</p>
                            @else
                                <p class="small text-secondary text-center mb-0 mt-3">O cartão aparece quando todos os itens têm preço total configurado.</p>
                            @endif
                        @else
                            <div class="alert alert-warning small mt-4 mb-0">Remova os itens indisponíveis para continuar.</div>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    @endif
</div>
@endsection
