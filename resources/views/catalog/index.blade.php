@extends('layouts.app')

@section('title', config('desapego.site_name', 'Desapego do Matheus'))

@section('content')
<section class="hero py-5">
    <div class="container py-lg-5">
        <div class="hero-copy">
            <div class="eyebrow mb-3">Um novo capítulo começa aqui</div>
            <h1 class="fw-bold mb-4">Coisas boas merecem uma próxima história.</h1>
            <p class="lead mb-4">Itens bem cuidados, preços justos e uma forma simples de dar um novo destino ao que você não usa mais.</p>
            <div class="d-flex flex-wrap gap-2"><span class="hero-stat">✦ Pagamento fácil por Pix</span><span class="hero-stat">⌁ Retirada combinada</span><span class="hero-stat">♡ Itens selecionados</span></div>
        </div>
    </div>
</section>

<div class="container py-5" id="catalogo">
    <form method="GET" action="{{ route('catalog.index') }}" class="catalog-search card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4">
        <div class="row g-2 align-items-end"><div class="col-md-6"><label class="form-label" for="search">Buscar item</label><input id="search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Ex.: livros, iPad, decoração..."></div><div class="col-md-3"><label class="form-label" for="category_filter">Categoria</label><select id="category_filter" class="form-select" name="category"><option value="">Todas</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select></div><div class="col-md-2"><label class="form-label" for="max_price">Até R$</label><input id="max_price" class="form-control" name="max_price" inputmode="decimal" value="{{ request('max_price') }}" placeholder="Preço"></div><div class="col-md-1 d-grid"><button class="btn btn-violet">Buscar</button></div></div>
        @if(request()->filled('search') || request()->filled('category') || request()->filled('max_price'))<div class="mt-3"><a class="small fw-bold" href="{{ route('catalog.index') }}">Limpar filtros</a></div>@endif
    </form>
    @if($categories->isNotEmpty())
        <div class="mb-5"><div class="eyebrow mb-2">Explore por categoria</div><div class="filter-pills"><a href="{{ route('catalog.index') }}" class="filter-pill {{ request('category') ? '' : 'active' }}">Tudo</a>@foreach($categories as $category)<a href="{{ route('catalog.index', ['category' => $category]) }}" class="filter-pill {{ request('category') === $category ? 'active' : '' }}">{{ $category }}</a>@endforeach</div></div>
    @endif

    @if($bundles->isNotEmpty())
        <section class="mb-5"><div class="d-flex align-items-end justify-content-between mb-3"><div><div class="eyebrow">Leve mais, pague menos</div><h2 class="section-heading mb-0">Combos especiais</h2></div></div><div class="row g-4">@foreach($bundles as $bundle)<div class="col-md-6 col-lg-3"><article class="product-card bundle-card">@if($bundle->cover_image_path)<img src="{{ asset('storage/'.$bundle->cover_image_path) }}" class="product-cover" alt="{{ $bundle->name }}">@endif<div class="card-body d-flex flex-column"><span class="bundle-ribbon">{{ $bundle->products->count() }} itens selecionados</span><h3 class="card-title fw-bold">{{ $bundle->name }}</h3><div class="mt-auto pt-3">@if($bundle->savings > 0)<div class="small text-decoration-line-through">Separados: R$ {{ number_format($bundle->individual_total, 2, ',', '.') }}</div>@endif<div class="price mt-1">R$ {{ number_format($bundle->pix_price, 2, ',', '.') }}</div>@if($bundle->savings > 0)<div class="small mb-3">Você economiza R$ {{ number_format($bundle->savings, 2, ',', '.') }}</div>@endif<a class="btn w-100 mt-2" href="{{ route('catalog.bundle', $bundle) }}">Ver combo</a></div></div></article></div>@endforeach</div></section>
    @endif

    <section>
        <div class="d-flex align-items-end justify-content-between mb-4">
            <div><div class="eyebrow">Disponíveis agora</div><h2 class="section-heading mb-0">Itens à venda</h2></div>
            <span class="small text-secondary d-none d-sm-inline">{{ $products->total() }} item(ns) encontrados</span>
        </div>

        @if($products->isEmpty())
            <div class="text-center py-5 px-3 rounded-4" style="background:#f7f3ff"><div class="fs-2 mb-2">✦</div><h3 class="h5">Nenhum item encontrado.</h3><p class="text-secondary mb-0">Tente ajustar a busca ou remover os filtros.</p></div>
        @else
            <div class="row g-3 g-lg-4" id="product-grid">
                @include('catalog._product-cards', ['products' => $products])
            </div>

            @if($products->hasMorePages())
                <div class="text-center mt-4" id="load-more-products">
                    <a class="btn btn-outline-violet px-4" href="{{ $products->nextPageUrl() }}" data-load-more>Carregar mais itens</a>
                </div>
            @endif
        @endif
    </section>
</div>
@endsection

@push('scripts')
    <script>
        const productGrid = document.getElementById('product-grid');
        const loadMoreContainer = document.getElementById('load-more-products');

        loadMoreContainer?.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-load-more]');

            if (!button) {
                return;
            }

            event.preventDefault();
            button.classList.add('disabled');
            button.textContent = 'Carregando itens...';

            const url = new URL(button.href);
            url.searchParams.set('load_more', '1');

            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' } });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar mais itens.');
                }

                const data = await response.json();
                productGrid.insertAdjacentHTML('beforeend', data.html);

                if (data.next_page_url) {
                    button.href = data.next_page_url;
                    button.classList.remove('disabled');
                    button.textContent = 'Carregar mais itens';
                } else {
                    loadMoreContainer.remove();
                }
            } catch (error) {
                button.classList.remove('disabled');
                button.textContent = 'Tentar novamente';
            }
        });
    </script>
@endpush
