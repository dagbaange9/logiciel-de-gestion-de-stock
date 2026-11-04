<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comptes en attente</title>
</head>
<body>
    <main>
        <h1>Comptes en attente de validation</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        @endif

        @forelse ($users as $user)
            <article>
                <p>{{ $user->name }} — {{ $user->email }} — {{ $user->phone }}</p>

                <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                    @csrf
                    <label for="role-{{ $user->id }}">Rôle</label>
                    <select id="role-{{ $user->id }}" name="role" required>
                        <option value="vendeur">Vendeur</option>
                        <option value="magasinier">Magasinier</option>
                    </select>
                    <button type="submit">Approuver</button>
                </form>

                <form method="POST" action="{{ route('admin.users.refuse', $user) }}">
                    @csrf
                    <button type="submit">Refuser</button>
                </form>
            </article>
        @empty
            <p>Aucun compte en attente.</p>
        @endforelse
    </main>
</body>
</html>
