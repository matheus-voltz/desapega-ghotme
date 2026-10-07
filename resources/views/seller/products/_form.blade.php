@php($product = $product ?? null)
<div class="row g-3">
    <div class="col-md-8"><label class="form-label" for="name">Nome do item</label><input id="name" name="name" class="form-control" value="{{ old('name', $product?->name) }}" required></div>
    <div class="col-md-4"><label class="form-label" for="category">Categoria</label><input id="category" name="category" class="form-control" value="{{ old('category', $product?->category) }}" placeholder="Livros, jogos, casa..."></div>
    <div class="col-md-4"><label class="form-label" for="condition">Estado do item</label><input id="condition" name="condition" class="form-control" value="{{ old('condition', $product?->condition) }}" placeholder="Novo, impecável..." ></div>
    <div class="col-md-4"><label class="form-label" for="pix_price">Preço no Pix</label><input id="pix_price" name="pix_price" type="number" min="0" step="0.01" class="form-control" value="{{ old('pix_price', $product?->pix_price) }}" required></div>
    <div class="col-md-4"><label class="form-label" for="marketplace_price">Preço total no cartão</label><input id="marketplace_price" name="marketplace_price" type="number" min="0" step="0.01" class="form-control" value="{{ old('marketplace_price', $product?->marketplace_price) }}"><div class="form-text">Deixe vazio se não quiser aceitar cartão.</div></div>
    <div class="col-md-4"><label class="form-label" for="sort_order">Ordem de exibição</label><input id="sort_order" name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $product?->sort_order ?? 0) }}"></div>
    <div class="col-md-8"><label class="form-label" for="cover_images">Fotos</label><input id="cover_images" name="cover_images[]" type="file" class="form-control" accept="image/jpeg,image/png,image/webp" multiple><div class="form-text">Até 4 fotos em JPG, PNG ou WebP, com até 12 MB cada.</div></div>
    <div class="col-12"><label class="form-label" for="cover_image_url">Imagem por link (opcional)</label><input id="cover_image_url" name="cover_image_url" type="url" class="form-control" value="{{ old('cover_image_url', $product?->cover_image_url) }}" placeholder="https://exemplo.com/foto.jpg"></div>
    @if($product?->image_urls)
        <div class="col-12"><div class="small text-secondary mb-2">Fotos atuais</div><div class="d-flex flex-wrap gap-2">@foreach($product->image_urls as $imageUrl)<img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="rounded-3 border" style="width: 112px;height:112px;object-fit:cover">@endforeach</div></div>
    @endif
    <div class="col-12"><label class="form-label" for="description">Descrição</label><textarea id="description" name="description" rows="6" class="form-control" placeholder="Conte detalhes, conservação e o que acompanha o item.">{{ old('description', $product?->description) }}</textarea></div>
</div>
@if($errors->any())<div class="alert alert-danger mt-3"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="mt-4 d-flex flex-wrap gap-2"><button class="btn btn-violet">Salvar item</button><a href="{{ route('seller.products.index') }}" class="btn btn-outline-violet">Cancelar</a></div>
