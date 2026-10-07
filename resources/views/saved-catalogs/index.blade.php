@extends('layouts.app')

@section('title', 'Meus catálogos')

@section('content')
<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <div class="eyebrow mb-2">Sua seleção</div>
            <h1 class="h2 fw-bold mb-1">Meus catálogos</h1>
            <p class="text-secondary mb-0">Catálogos salvos quando você começou uma compra. Volte quando quiser.</p>
        </div>
        <a href="{{ route('catalog.index') }}" class="btn btn-outline-violet">Explorar itens</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($savedCatalogs->isEmpty())
        <section class="card border-0 shadow-sm rounded-4 p-5 text-center">
            <div class="fs-2 mb-2">✦</div>
            <h2 class="h4">Nenhum catálogo salvo ainda</h2>
            <p class="text-secondary mb-4">Ao adicionar um item à sacola ou iniciar um pagamento, o catálogo daquele vendedor ficará salvo aqui.</p>
            <div><a class="btn btn-violet" href="{{ route('catalog.index') }}">Explorar itens</a></div>
        </section>
    @else
        <div class="row g-3 g-lg-4">
            @foreach($savedCatalogs as $savedCatalog)
                <div class="col-md-6 col-lg-4">
                    <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <span class="brand-mark flex-shrink-0">{{ mb_strtoupper(mb_substr($savedCatalog->seller->name, 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <div class="small text-secondary">Catálogo de</div>
                                    <h2 class="h5 fw-bold mb-0 text-truncate">{{ $savedCatalog->seller->name }}</h2>
                                </div>
                            </div>
                            <p class="text-secondary small mb-4">{{ $savedCatalog->seller->visible_products_count }} {{ $savedCatalog->seller->visible_products_count === 1 ? 'item disponível' : 'itens disponíveis' }}</p>
                            <div class="mt-auto d-flex gap-2">
                                <a class="btn btn-violet flex-grow-1" href="{{ route('seller.profile', $savedCatalog->seller) }}">Abrir catálogo</a>
                                <form method="POST" action="{{ route('saved-catalogs.destroy', $savedCatalog) }}" onsubmit="return confirm('Remover este catálogo da sua lista?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" type="submit">Remover</button>
                                </form>
                            </div>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
