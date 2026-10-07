@extends('layouts.app')

@section('title', 'Meus combos')

@section('content')
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div><div class="eyebrow mb-2">Painel do vendedor</div><h1 class="h2 fw-bold mb-1">Meus combos</h1><p class="text-secondary mb-0">Agrupe dois ou mais itens do seu catálogo e ofereça um preço especial.</p></div>
        <div class="d-flex gap-2"><a href="{{ route('seller.products.index') }}" class="btn btn-outline-violet">Meus itens</a><a href="{{ route('seller.bundles.create') }}" class="btn btn-violet">Criar combo</a></div>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($bundles->isEmpty())
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center"><div class="fs-2 mb-2">✦</div><h2 class="h4">Monte seu primeiro combo</h2><p class="text-secondary">Selecione pelo menos dois itens do seu catálogo, defina um preço no Pix e publique.</p><div><a class="btn btn-violet" href="{{ route('seller.bundles.create') }}">Criar combo</a></div></div>
    @else
        <div class="row g-3">@foreach($bundles as $bundle)<div class="col-md-6 col-xl-4"><article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">@if($bundle->cover_image_path)<img src="{{ asset('storage/'.$bundle->cover_image_path) }}" alt="{{ $bundle->name }}" style="height:190px;width:100%;object-fit:cover">@endif<div class="card-body"><div class="d-flex justify-content-between gap-2"><span class="small text-secondary">{{ $bundle->products->count() }} itens</span><span class="status {{ $bundle->active ? 'status-available' : 'status-sold' }}">{{ $bundle->active ? 'Ativo' : 'Inativo' }}</span></div><h2 class="h6 fw-bold mt-2">{{ $bundle->name }}</h2><div class="fw-bold">R$ {{ number_format((float) $bundle->pix_price, 2, ',', '.') }} <span class="small text-secondary fw-normal">no Pix</span></div><div class="d-flex gap-2 mt-3"><a class="btn btn-sm btn-outline-violet flex-grow-1" href="{{ route('seller.bundles.edit', $bundle) }}">Editar</a><form method="POST" action="{{ route('seller.bundles.destroy', $bundle) }}" onsubmit="return confirm('Excluir este combo?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Excluir</button></form></div></div></article></div>@endforeach</div>
        <div class="mt-4">@include('components.pagination-controls', ['paginator' => $bundles])</div>
    @endif
</div>
@endsection
