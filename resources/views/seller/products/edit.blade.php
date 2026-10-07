@extends('layouts.app')

@section('title', 'Editar item')

@section('content')
<div class="container py-5" style="max-width:900px"><div class="mb-4"><div class="eyebrow mb-2">Painel do vendedor</div><h1 class="h2 fw-bold">Editar item</h1><p class="text-secondary mb-0">Atualize informações, fotos e preços.</p></div><form method="POST" action="{{ route('seller.products.update', $product) }}" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4 p-4">@csrf @method('PUT') @include('seller.products._form', ['product' => $product])</form></div>
@endsection
