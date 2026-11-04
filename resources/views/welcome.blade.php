<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Gestion de stock') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-white">
    <header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4" aria-label="Navigation principale">
            <a href="{{ route('dashboard') }}" class="font-semibold text-blue-700 dark:text-blue-400">Gestion de stock</a>
            @auth
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <a href="{{ route('profile.edit') }}" class="rounded px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800">Profil</a>
                    @if (session('auth_role', auth()->user()->role?->name) === 'administrateur')
                        <a href="{{ route('admin.users.index') }}" class="rounded px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800">Utilisateurs</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded bg-gray-800 px-3 py-2 text-white hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600">Déconnexion</button>
                    </form>
                </div>
            @else
                <div class="flex gap-3 text-sm">
                    <a href="{{ route('login') }}" class="rounded px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800">Connexion</a>
                    <a href="{{ route('register') }}" class="rounded bg-blue-700 px-3 py-2 text-white hover:bg-blue-800">Inscription</a>
                </div>
            @endauth
        </nav>
    </header>
    <main class="mx-auto max-w-6xl px-4 py-10">
        @auth
            <h1 class="text-3xl font-bold">Tableau de bord</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-300">Connecté en tant que {{ auth()->user()->name }} — rôle {{ auth()->user()->role?->name ?? 'non attribué' }}.</p>
        @else
            <h1 class="text-3xl font-bold">Bienvenue</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-300">Connectez-vous ou créez un compte pour accéder à l’application.</p>
        @endauth
    </main>
</body>
</html>
