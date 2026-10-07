<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acessar · Desapega.ghotme</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/desapego.css') }}">
</head>
<body class="auth-page">
    <div class="auth-orb auth-orb-one"></div><div class="auth-orb auth-orb-two"></div>
    <main class="auth-shell">
        <a href="{{ route('landing') }}" class="brand auth-brand"><span class="brand-mark">D</span><span>Desapega<span class="brand-muted">.ghotme</span></span></a>
        <section class="auth-card">
            {{ $slot }}
        </section>
        <p class="auth-note">Sua conta para escolher itens e fechar a compra com tranquilidade.</p>
    </main>
</body>
</html>
