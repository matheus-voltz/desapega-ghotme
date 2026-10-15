@extends('layouts.app')
@section('title', 'Produtos')
@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 gap-2 flex-wrap">
        <div>
            <h1 class="h3 mb-1">Produtos</h1>
            <div class="d-flex flex-wrap gap-3 small"><a href="{{ route('admin.bundles.index') }}">Gerenciar combos →</a><a href="{{ route('seller.settings.edit') }}">Prévia das configurações do vendedor →</a></div>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-dark">Novo produto</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="d-md-none product-admin-cards">
        @foreach($products as $product)
            <article class="product-admin-card">
                <div class="d-flex justify-content-between gap-2"><strong>{{ $product->name }}</strong><span class="status status-{{ $product->status }}">{{ $product->status_label }}</span></div>
                <div class="small text-secondary mt-2">Pix: <strong>R$ {{ number_format($product->pix_price, 2, ',', '.') }}</strong></div>
                <form method="POST" action="{{ route('admin.products.visibility', $product) }}" class="mt-3">
                    @csrf @method('PATCH')
                    <input type="hidden" name="is_visible" value="0">
                    <label class="form-check form-switch mb-0 d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" name="is_visible" value="1" @checked($product->is_visible) onchange="this.form.submit()"><span class="small fw-semibold">{{ $product->is_visible ? 'Exibido no catálogo' : 'Oculto do catálogo' }}</span></label>
                </form>
                <div class="d-flex gap-2 mt-3"><a class="btn btn-sm btn-outline-dark flex-grow-1" href="{{ route('admin.products.edit', $product) }}">Editar</a><form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Excluir este produto?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Excluir</button></form></div>
            </article>
        @endforeach
    </div>
    <div class="table-responsive bg-white rounded-4 shadow-sm d-none d-md-block">
        <table class="table align-middle mb-0">
            <thead><tr><th>Produto</th><th>Status</th><th>Pix</th><th>Cartão</th><th>Catálogo</th><th></th></tr></thead>
            <tbody>
            @foreach($products as $product)
                <tr>
                    <td>
                        {{ $product->name }}
                    </td>
                    <td>
                        {{ $product->status_label }}
                        @if($product->sale_channel)
                            <div class="small text-secondary">via {{ $product->sale_channel === 'asaas' ? 'Asaas' : 'manual/Pix' }}</div>
                        @endif
                    </td>
                    <td>R$ {{ number_format($product->pix_price, 2, ',', '.') }}</td>
                    <td>
                        {{ $product->marketplace_price !== null ? 'R$ '.number_format($product->marketplace_price, 2, ',', '.') : '—' }}
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.products.visibility', $product) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="is_visible" value="0">
                            <label class="form-check form-switch mb-0 d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" name="is_visible" value="1" @checked($product->is_visible) onchange="this.form.submit()"><span class="small">{{ $product->is_visible ? 'Exibido' : 'Oculto' }}</span></label>
                        </form>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.products.edit', $product) }}">Editar</a>
                        <form class="d-inline" method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Excluir este produto?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">@include('components.pagination-controls', ['paginator' => $products])</div>
</div>
@endsection
