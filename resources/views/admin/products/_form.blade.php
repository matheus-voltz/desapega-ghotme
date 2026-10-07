@php($product = $product ?? null)
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Nome</label>
        <input name="name" class="form-control" value="{{ old('name', $product?->name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Categoria</label>
        <input name="category" class="form-control" value="{{ old('category', $product?->category) }}" placeholder="Livros, Jogos...">
    </div>
    <div class="col-md-4">
        <label class="form-label">Estado</label>
        <input name="condition" class="form-control" value="{{ old('condition', $product?->condition) }}" placeholder="Excelente, marcas de uso...">
    </div>
    <div class="col-md-4">
        <label class="form-label">Preço Pix</label>
        <input name="pix_price" type="number" step="0.01" class="form-control" value="{{ old('pix_price', $product?->pix_price) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Preço do cartão</label>
        <input name="marketplace_price" type="number" step="0.01" class="form-control" value="{{ old('marketplace_price', $product?->marketplace_price) }}">
        <div class="form-text">Valor-base exibido no pagamento por cartão. A taxa de parcelamento é calculada no checkout.</div>
    </div>
    <div class="col-md-8">
        <label class="form-label">Link Shopee</label>
        <input name="marketplace_url" type="url" class="form-control" value="{{ old('marketplace_url', $product?->marketplace_url) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            @foreach(['available' => 'Disponível', 'reserved' => 'Reservado', 'sold' => 'Vendido'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $product?->status ?? 'available') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12"><hr><h2 class="h6">Sincronização Shopee</h2></div>
    <div class="col-md-6">
        <label class="form-label">Shopee Item ID</label>
        <input name="shopee_item_id" inputmode="numeric" class="form-control" value="{{ old('shopee_item_id', $product?->shopee_item_id) }}" placeholder="Ex.: 12345678901">
        <div class="form-text">Identifica o anúncio. É o campo usado para relacionar o item comprado na Shopee com este produto.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Shopee Model ID <span class="text-secondary">(opcional)</span></label>
        <input name="shopee_model_id" inputmode="numeric" class="form-control" value="{{ old('shopee_model_id', $product?->shopee_model_id) }}" placeholder="Use apenas se o anúncio tiver variações">
        <div class="form-text">Se não houver variação, deixe vazio.</div>
    </div>

    @if($product?->shopee_order_sn)
        <div class="col-12">
            <div class="alert alert-light border mb-0">
                <strong>Último pedido Shopee:</strong> {{ $product->shopee_order_sn }} ·
                <strong>Status:</strong> {{ $product->shopee_order_status ?? '—' }}
                @if($product->shopee_synced_at)
                    · sincronizado em {{ $product->shopee_synced_at->format('d/m/Y H:i') }}
                @endif
            </div>
        </div>
    @endif

    <div class="col-md-4">
        <label class="form-label">Ordem</label>
        <input name="sort_order" type="number" class="form-control" value="{{ old('sort_order', $product?->sort_order ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Fotos do item</label>
        <input name="cover_images[]" type="file" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
        <div class="form-text">Envie até 4 fotos em JPG, PNG ou WebP, com até 12 MB cada. Ao editar, as novas fotos substituem as atuais.</div>
    </div>
    <div class="col-md-8">
        <label class="form-label">Imagem por URL (src)</label>
        <input name="cover_image_url" type="url" class="form-control" value="{{ old('cover_image_url', $product?->cover_image_url) }}" placeholder="https://exemplo.com/imagem.jpg">
        <div class="form-text">Usada automaticamente como imagem principal quando não houver uma foto enviada.</div>
    </div>
    @if($product?->image_urls)
        <div class="col-12">
            <div class="small text-secondary mb-2">Fotos atuais</div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($product->image_urls as $imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="rounded-3 border" style="width: 120px; height: 120px; object-fit: cover;">
                @endforeach
            </div>
        </div>
    @endif
    <div class="col-12">
        <label class="form-label">Descrição</label>
        <textarea name="description" rows="5" class="form-control">{{ old('description', $product?->description) }}</textarea>
    </div>
</div>
@if($errors->any())
    <div class="alert alert-danger mt-3"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<div class="mt-4 d-flex gap-2">
    <button class="btn btn-dark">Salvar</button>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancelar</a>
</div>
