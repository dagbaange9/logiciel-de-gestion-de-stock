<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestion des utilisateurs</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-white">
    <main class="mx-auto max-w-6xl px-4 py-10">
        <a href="{{ route('dashboard') }}" class="text-sm text-blue-700 hover:underline dark:text-blue-400">Retour au tableau de bord</a>
        <h1 class="mt-4 text-3xl font-bold">Utilisateurs</h1>
        @if (session('status'))<p class="mt-4 rounded border border-green-300 bg-green-50 p-3 text-green-800">{{ session('status') }}</p>@endif
        @if ($errors->any())<ul class="mt-4 rounded border border-red-300 bg-red-50 p-3 text-red-800">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif

        <form method="GET" action="{{ route('admin.users.index') }}" class="mt-6 flex flex-wrap gap-3 rounded border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <label class="grid gap-1 text-sm">Statut
                <select name="status" class="rounded border px-3 py-2 dark:bg-gray-950">
                    <option value="">Tous</option>
                    @foreach ($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach
                </select>
            </label>
            <label class="grid gap-1 text-sm">Rôle
                <select name="role" class="rounded border px-3 py-2 dark:bg-gray-950">
                    <option value="">Tous</option>
                    @foreach ($roles as $role)<option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>@endforeach
                </select>
            </label>
            <button class="mt-auto rounded bg-blue-700 px-4 py-2 text-white">Filtrer</button>
        </form>

        <div class="mt-6 grid gap-3">
            @forelse ($users as $user)
                <article class="flex flex-wrap items-center justify-between gap-4 rounded border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <div>
                        <a href="{{ route('admin.users.show', $user) }}" class="font-semibold hover:underline">{{ $user->name }}</a>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $user->email }} — {{ $user->status }} — {{ $user->role?->name ?? 'sans rôle' }}</p>
                    </div>
                    <div class="flex gap-2">
                        @if ($user->status === \App\Models\User::STATUS_DESACTIVE)
                            <form method="POST" action="{{ route('admin.users.activate', $user) }}">@csrf<button class="rounded bg-green-700 px-3 py-2 text-white">Activer</button></form>
                        @else
                            <form method="POST" action="{{ route('admin.users.deactivate', $user) }}">@csrf<button class="rounded bg-amber-700 px-3 py-2 text-white">Désactiver</button></form>
                        @endif
                    </div>
                </article>
            @empty
                <p class="rounded border border-gray-200 bg-white p-4">Aucun utilisateur trouvé.</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $users->links() }}</div>
    </main>
</body>
</html>
