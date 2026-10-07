<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('desapego.site_name', 'Desapego do Matheus'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/desapego.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="container d-flex align-items-center justify-content-between py-3">
            <a class="brand" href="{{ route('landing') }}"><span class="brand-mark">D</span><span>Desapega<span class="brand-muted">.ghotme</span></span></a>
            <nav class="site-nav">
                <div class="site-nav-desktop d-flex align-items-center gap-2">
                    @if(request()->routeIs('landing'))
                        <a class="nav-link-custom" href="{{ route('login') }}">Entrar na conta</a>
                    @else
                        <a class="nav-link-custom" href="{{ route('catalog.index') }}#catalogo">Catálogo</a>
                    @endif
                    @auth
                        <a class="btn btn-sm btn-outline-violet" href="{{ route('cart.index') }}">Minha sacola</a>
                        @if(auth()->user()->isAdmin())<a class="nav-link-custom" href="{{ route('admin.products.index') }}">Meu painel</a>@elseif(auth()->user()->isSeller())<a class="nav-link-custom" href="{{ route('seller.products.index') }}">Painel do vendedor</a>@endif
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-link text-secondary text-decoration-none" type="submit">Sair</button></form>
                    @else
                        <a class="btn btn-sm btn-outline-violet" href="{{ route('login') }}">Entrar</a>
                    @endauth
                </div>
                <details class="site-nav-mobile">
                    <summary aria-label="Abrir menu">☰ <span>Menu</span></summary>
                    <div class="site-nav-menu">
                        @if(request()->routeIs('landing'))<a href="{{ route('login') }}">Entrar na conta</a>@else<a href="{{ route('catalog.index') }}#catalogo">Catálogo</a>@endif
                        @auth
                            <a href="{{ route('cart.index') }}">Minha sacola</a>
                            @if(auth()->user()->isAdmin())<a href="{{ route('admin.products.index') }}">Meu painel</a>@elseif(auth()->user()->isSeller())<a href="{{ route('seller.products.index') }}">Painel do vendedor</a><a href="{{ route('seller.settings.edit') }}">Configurações</a>@endif
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sair</button></form>
                        @else
                            <a class="menu-highlight" href="{{ route('login') }}">Entrar</a>
                        @endauth
                    </div>
                </details>
            </nav>
        </div>
    </header>

    <main>@yield('content')</main>

    <footer class="site-footer mt-5">
        <div class="container py-4 d-flex flex-column flex-sm-row justify-content-between gap-2 small">
            <span>Desapega.ghotme · itens em busca de um novo lar</span>
            <a href="{{ route('catalog.index') }}">Voltar ao catálogo</a>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
