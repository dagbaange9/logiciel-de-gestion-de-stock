<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mon profil</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-white">
    <main class="mx-auto max-w-2xl px-4 py-10">
        <a href="{{ route('dashboard') }}" class="text-sm text-blue-700 hover:underline dark:text-blue-400">Retour au tableau de bord</a>
        <h1 class="mt-4 text-3xl font-bold">Mon profil</h1>

        @if (session('status'))
            <p class="mt-4 rounded border border-green-300 bg-green-50 p-3 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <ul class="mt-4 rounded border border-red-300 bg-red-50 p-3 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="mt-6 grid gap-5 rounded border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            @csrf
            @method('PUT')
            <label class="grid gap-2">Nom
                <input name="name" value="{{ old('name', $user->name) }}" required class="rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
            </label>
            <label class="grid gap-2">Téléphone
                <input name="phone" value="{{ old('phone', $user->phone) }}" required class="rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
            </label>
            <label class="grid gap-2">Adresse e-mail
                <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
                @if ($user->pending_email)<p class="text-sm text-amber-700">Nouvelle adresse en attente : {{ $user->pending_email }}</p>@endif
            </label>
            <label class="grid gap-2">Nouveau mot de passe
                <input name="password" type="password" minlength="8" class="rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
            </label>
            <label class="grid gap-2">Confirmation du mot de passe
                <input name="password_confirmation" type="password" minlength="8" class="rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950">
            </label>
            <button type="submit" class="rounded bg-blue-700 px-4 py-2 text-white hover:bg-blue-800">Enregistrer</button>
        </form>
    </main>
</body>
</html>
