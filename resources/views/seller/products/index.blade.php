@extends('layouts.app')

@section('title', 'Meus itens')

@section('content')
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><div class="eyebrow mb-2">Painel do vendedor</div><h1 class="h2 fw-bold mb-1">Meus itens</h1><p class="text-secondary mb-0">Publique, edite e acompanhe o que está no seu catálogo.</p></div><div class="d-flex gap-2"><a href="{{ route('seller.settings.edit') }}" class="btn btn-outline-violet">Configurações</a><a href="{{ route('seller.products.create') }}" class="btn btn-violet">Adicionar item</a></div></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($products->isEmpty())<div class="card border-0 shadow-sm rounded-4 p-5 text-center"><div class="fs-2 mb-2">✦</div><h2 class="h4">Seu catálogo começa aqui</h2><p class="text-secondary">Cadastre seu primeiro item para gerar um catálogo compartilhável.</p><div><a class="btn btn-violet" href="{{ route('seller.products.create') }}">Cadastrar primeiro item</a></div></div>@else
        <div class="row g-3">@foreach($products as $product)<div class="col-md-6 col-xl-4"><article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden"><img src="{{ $product->primary_image_url ?? asset('images/product-placeholder.svg') }}" alt="{{ $product->name }}" style="height:190px;width:100%;object-fit:cover"><div class="card-body"><div class="d-flex justify-content-between gap-2"><span class="small text-secondary">{{ $product->category ?: 'Sem categoria' }}</span><span class="status status-{{ $product->status }}">{{ $product->status_label }}</span></div><h2 class="h6 fw-bold mt-2">{{ $product->name }}</h2><div class="fw-bold">R$ {{ number_format((float) $product->pix_price, 2, ',', '.') }} <span class="small text-secondary fw-normal">no Pix</span></div><div class="d-flex gap-2 mt-3"><a class="btn btn-sm btn-outline-violet flex-grow-1" href="{{ route('seller.products.edit', $product) }}">Editar</a><form method="POST" action="{{ route('seller.products.destroy', $product) }}" onsubmit="return confirm('Remover este item do seu catálogo?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remover</button></form></div></div></article></div>@endforeach</div>
        <div class="mt-4">@include('components.pagination-controls', ['paginator' => $products])</div>
    @endif
</div>
@endsection
