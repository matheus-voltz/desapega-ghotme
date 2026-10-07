@extends('layouts.app')

@section('title', 'Criar combo')

@section('content')
<div class="container py-5"><div class="bg-white rounded-4 shadow-sm p-4"><h1 class="h3 mb-2">Criar combo</h1><p class="text-secondary mb-4">Escolha dois ou mais itens do seu catálogo para vender juntos.</p><form method="POST" enctype="multipart/form-data" action="{{ route('seller.bundles.store') }}">@csrf @include('seller.bundles._form')</form></div></div>
@endsection
