@foreach($products as $product)
    <div class="col-6 col-md-4 col-lg-3">
        <article class="product-card {{ $product->status === 'sold' ? 'opacity-75' : '' }}">
            @if($product->primary_image_url)
                <img src="{{ $product->primary_image_url }}" class="product-cover" alt="{{ $product->name }}">
            @else
                <div class="product-cover placeholder-cover d-flex align-items-center justify-content-center"><span>Sem foto</span></div>
            @endif
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center gap-1 mb-2">
                    <span class="small text-secondary text-truncate">{{ $product->category ?: 'Outros' }}</span>
                    <span class="status status-{{ $product->status }}">{{ $product->status_label }}</span>
                </div>
                <h3 class="card-title fw-bold mb-1">{{ $product->name }}</h3>
                @if($product->condition)
                    <p class="small text-secondary mb-0">{{ $product->condition }}</p>
                @endif
                <div class="mt-auto pt-3">
                    <div class="price-label">No Pix</div>
                    <div class="price">R$ {{ number_format($product->pix_price, 2, ',', '.') }}</div>
                    @if($product->savings > 0)
                        <div class="saving">R$ {{ number_format($product->savings, 2, ',', '.') }} mais barato</div>
                    @endif
                    <a class="btn btn-outline-violet w-100 mt-3" href="{{ route('catalog.product', $product) }}">Ver item</a>
                    @if($product->status === 'available')
                        @auth
                            <form method="POST" action="{{ route('cart.items.store', $product) }}" class="d-grid mt-2">
                                @csrf
                                <button class="btn btn-violet" type="submit">Adicionar à sacola</button>
                            </form>
                        @endauth
                    @endif
                </div>
            </div>
        </article>
    </div>
@endforeach
