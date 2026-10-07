@extends('layouts.app')
@section('title', 'Novo produto')
@section('content')
<div class="container py-5"><div class="bg-white rounded-4 shadow-sm p-4"><h1 class="h3 mb-4">Novo produto</h1><form method="POST" enctype="multipart/form-data" action="{{ route('admin.products.store') }}">@csrf @include('admin.products._form')</form></div></div>
@endsection
