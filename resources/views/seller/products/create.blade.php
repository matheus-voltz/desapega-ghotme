@extends('layouts.app')

@section('title', 'Adicionar item')

@section('content')
<div class="container py-5" style="max-width:900px"><div class="mb-4"><div class="eyebrow mb-2">Painel do vendedor</div><h1 class="h2 fw-bold">Adicionar item</h1><p class="text-secondary mb-0">Ele será publicado no seu catálogo pessoal assim que você salvar.</p></div><form method="POST" action="{{ route('seller.products.store') }}" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4 p-4">@csrf @include('seller.products._form')</form></div>
@endsection
