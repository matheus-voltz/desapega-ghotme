@extends('layouts.app')
@section('title', 'Integração Shopee')
@section('content')
<div class="container py-5" style="max-width: 900px">
    <div class="d-flex justify-content-between align-items-start mb-4 gap-3 flex-wrap">
        <div>
            <h1 class="h3 mb-1">Integração Shopee</h1>
            <p class="text-secondary mb-0">Sincroniza compras da Shopee com a disponibilidade do catálogo.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Produtos</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @if(!config('shopee.enabled'))
        <div class="alert alert-warning">A integração está desativada. Defina <code>SHOPEE_ENABLED=true</code> no <code>.env</code>.</div>
    @endif

    <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
        <h2 class="h5">Conexão</h2>
        @if($connection)
            <p class="mb-2"><strong>Loja:</strong> {{ $connection->shop_id }}</p>
            <p class="mb-2"><strong>Token:</strong> válido até {{ $connection->access_token_expires_at?->format('d/m/Y H:i') ?? '—' }}</p>
            <p class="mb-3"><strong>Última varredura:</strong> {{ $connection->last_synced_at?->format('d/m/Y H:i') ?? 'ainda não executada' }}</p>
            <div class="d-flex gap-2 flex-wrap">
                <form method="POST" action="{{ route('admin.shopee.sync') }}">@csrf<button class="btn btn-dark">Sincronizar agora</button></form>
                <form method="POST" action="{{ route('admin.shopee.configure-push') }}">@csrf<button class="btn btn-outline-dark">Reconfigurar webhook</button></form>
                <form method="POST" action="{{ route('admin.shopee.disconnect') }}" onsubmit="return confirm('Remover a conexão local com a Shopee?')">@csrf @method('DELETE')<button class="btn btn-outline-danger">Desconectar</button></form>
            </div>
        @else
            <p class="text-secondary">Nenhuma loja conectada.</p>
            <a href="{{ route('admin.shopee.connect') }}" class="btn btn-dark">Conectar minha Shopee</a>
        @endif
    </div>

    <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
        <h2 class="h5">URLs configuradas</h2>
        <div class="mb-3">
            <div class="small text-secondary">Callback OAuth</div>
            <code class="text-break">{{ config('shopee.redirect_url') ?: 'SHOPEE_REDIRECT_URL não configurada' }}</code>
        </div>
        <div>
            <div class="small text-secondary">Webhook</div>
            <code class="text-break">{{ config('shopee.webhook_url') ?: 'SHOPEE_WEBHOOK_URL não configurada' }}</code>
        </div>
    </div>

    <div class="bg-white rounded-4 shadow-sm p-4">
        <h2 class="h5">Regra automática</h2>
        <ul class="mb-0">
            <li><code>UNPAID</code> ou <code>PENDING</code> → produto fica <strong>Reservado</strong>.</li>
            <li><code>READY_TO_SHIP</code>, <code>PROCESSED</code>, <code>SHIPPED</code>, <code>TO_CONFIRM_RECEIVE</code>, <code>COMPLETED</code>, <code>INVOICE_PENDING</code> ou <code>RETRY_SHIP</code> → <strong>Vendido</strong>.</li>
            <li><code>CANCELLED</code> → volta para <strong>Disponível</strong>, mas somente se aquele pedido da Shopee foi quem bloqueou o item.</li>
        </ul>
        <p class="small text-secondary mt-3 mb-0">Para o vínculo funcionar, informe o Shopee Item ID na edição de cada produto.</p>
    </div>
</div>
@endsection
