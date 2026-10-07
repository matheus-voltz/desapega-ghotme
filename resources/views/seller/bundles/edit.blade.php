@extends('layouts.app')

@section('title', 'Editar combo')

@section('content')
<div class="container py-5"><div class="bg-white rounded-4 shadow-sm p-4"><h1 class="h3 mb-4">Editar combo</h1><form method="POST" enctype="multipart/form-data" action="{{ route('seller.bundles.update', $bundle) }}">@csrf @method('PUT') @include('seller.bundles._form')</form></div></div>
@endsection
