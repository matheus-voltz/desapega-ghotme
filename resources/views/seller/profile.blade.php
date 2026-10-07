@extends('layouts.app')

@section('title', $seller->name.' — Desapega.ghotme')

@section('content')
<section class="hero py-5"><div class="container py-lg-4"><div class="hero-copy"><div class="eyebrow mb-2">Catálogo do vendedor</div><h1 class="fw-bold mb-3">Itens de {{ $seller->name }}</h1><p class="lead mb-0">Confira tudo que este vendedor colocou para circular.</p></div></div></section>
<div class="container py-5"><div class="d-flex justify-content-between align-items-center mb-4"><h2 class="section-heading mb-0">Disponíveis agora</h2><span class="small text-secondary">{{ $products->total() }} item(ns)</span></div>@if($products->isEmpty())<div class="text-center py-5 px-3 rounded-4" style="background:#f7f3ff"><h3 class="h5">Nenhum item disponível ainda.</h3><p class="text-secondary mb-0">Volte em breve para conferir as novidades.</p></div>@else<div class="row g-3 g-lg-4">@include('catalog._product-cards', ['products' => $products])</div><div class="mt-4">{{ $products->links() }}</div>@endif</div>
@endsection
