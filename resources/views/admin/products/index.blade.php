@extends('layouts.app')
@section('title', 'Produtos')
@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 gap-2 flex-wrap">
        <div>
            <h1 class="h3 mb-1">Produtos</h1>
            <a href="{{ route('admin.shopee.index') }}" class="small">Configurar sincronização Shopee →</a>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-dark">Novo produto</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="table-responsive bg-white rounded-4 shadow-sm">
        <table class="table align-middle mb-0">
            <thead><tr><th>Produto</th><th>Status</th><th>Pix</th><th>Cartão</th><th>Última sincronização</th><th></th></tr></thead>
            <tbody>
            @foreach($products as $product)
                <tr>
                    <td>
                        {{ $product->name }}
                        @if($product->shopee_item_id)
                            <div class="small text-secondary">Item #{{ $product->shopee_item_id }}@if($product->shopee_model_id) · modelo #{{ $product->shopee_model_id }}@endif</div>
                        @endif
                    </td>
                    <td>
                        {{ $product->status_label }}
                        @if($product->sale_channel)
                            <div class="small text-secondary">via {{ $product->sale_channel === 'shopee' ? 'Shopee' : 'manual/Pix' }}</div>
                        @endif
                        @if($product->sale_channel === 'manual' && in_array($product->shopee_order_status, ['READY_TO_SHIP','PROCESSED','SHIPPED','TO_CONFIRM_RECEIVE','COMPLETED','INVOICE_PENDING','RETRY_SHIP'], true))
                            <span class="badge text-bg-danger mt-1">Conflito: pedido Shopee pago</span>
                        @endif
                    </td>
                    <td>R$ {{ number_format($product->pix_price, 2, ',', '.') }}</td>
                    <td>
                        {{ $product->marketplace_url ? 'Anúncio' : 'Sem link' }}
                        @if($product->shopee_order_status)
                            <div class="small text-secondary">{{ $product->shopee_order_status }}</div>
                        @endif
                    </td>
                    <td class="small text-secondary">{{ $product->shopee_synced_at?->format('d/m/Y H:i') ?? '—' }}</td>
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
    <div class="mt-3">{{ $products->links() }}</div>
</div>
@endsection
