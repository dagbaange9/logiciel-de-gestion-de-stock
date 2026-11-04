<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion</title>
</head>
<body>
    <main>
        <h1>Connexion</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <div>
                <label for="email">Adresse e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            </div>
            <div>
                <label for="password">Mot de passe</label>
                <input id="password" name="password" type="password" required>
            </div>
            @error('email') <p>{{ $message }}</p> @enderror
            @error('password') <p>{{ $message }}</p> @enderror
            <button type="submit">Se connecter</button>
        </form>

        <a href="{{ route('password.request') }}">Mot de passe oublié</a>
    </main>
</body>
</html>
