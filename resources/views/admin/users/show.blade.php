<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détail utilisateur</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-white">
    <main class="mx-auto max-w-3xl px-4 py-10">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-700 hover:underline dark:text-blue-400">Retour à la liste</a>
        <h1 class="mt-4 text-3xl font-bold">{{ $user->name }}</h1>
        @if (session('status'))<p class="mt-4 rounded border border-green-300 bg-green-50 p-3 text-green-800">{{ session('status') }}</p>@endif

        <dl class="mt-6 grid gap-3 rounded border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div><dt class="font-semibold">E-mail</dt><dd>{{ $user->email }}</dd></div>
            <div><dt class="font-semibold">Téléphone</dt><dd>{{ $user->phone }}</dd></div>
            <div><dt class="font-semibold">Statut</dt><dd>{{ $user->status }}</dd></div>
            <div><dt class="font-semibold">Rôle actuel</dt><dd>{{ $user->role?->name ?? 'sans rôle' }}</dd></div>
        </dl>

        <form method="POST" action="{{ route('admin.users.role.update', $user) }}" class="mt-6 flex flex-wrap items-end gap-3 rounded border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            @csrf @method('PUT')
            <label class="grid gap-1">Nouveau rôle
                <select name="role" class="rounded border px-3 py-2 dark:bg-gray-950">
                    @foreach ($roles as $role)<option value="{{ $role->name }}" @selected($user->role_id === $role->id)>{{ $role->name }}</option>@endforeach
                </select>
            </label>
            <button class="rounded bg-blue-700 px-4 py-2 text-white">Modifier le rôle</button>
        </form>

        <div class="mt-6 flex flex-wrap gap-3">
            @if ($user->status === \App\Models\User::STATUS_DESACTIVE)
                <form method="POST" action="{{ route('admin.users.activate', $user) }}">@csrf<button class="rounded bg-green-700 px-4 py-2 text-white">Activer</button></form>
            @else
                <form method="POST" action="{{ route('admin.users.deactivate', $user) }}">@csrf<button class="rounded bg-amber-700 px-4 py-2 text-white">Désactiver</button></form>
            @endif
            @if (! auth()->user()->is($user))
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Confirmer la suppression logique de ce compte ?')">
                    @csrf @method('DELETE')
                    <button class="rounded bg-red-700 px-4 py-2 text-white">Supprimer logiquement</button>
                </form>
            @endif
        </div>
    </main>
</body>
</html>
