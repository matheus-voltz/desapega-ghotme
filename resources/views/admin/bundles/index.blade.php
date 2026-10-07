@extends('layouts.app')
@section('title', 'Combos')
@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Combos</h1>
        <a href="{{ route('admin.bundles.create') }}" class="btn btn-dark">Novo combo</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="table-responsive bg-white rounded-4 shadow-sm">
        <table class="table align-middle mb-0">
            <thead><tr><th>Combo</th><th>Itens</th><th>Pix</th><th>Ativo</th><th></th></tr></thead>
            <tbody>
            @foreach($bundles as $bundle)
                <tr>
                    <td>{{ $bundle->name }}</td>
                    <td>{{ $bundle->products->count() }}</td>
                    <td>R$ {{ number_format($bundle->pix_price, 2, ',', '.') }}</td>
                    <td>{{ $bundle->active ? 'Sim' : 'Não' }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.bundles.edit', $bundle) }}">Editar</a>
                        <form class="d-inline" method="POST" action="{{ route('admin.bundles.destroy', $bundle) }}" onsubmit="return confirm('Excluir este combo?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">@include('components.pagination-controls', ['paginator' => $bundles])</div>
</div>
@endsection
