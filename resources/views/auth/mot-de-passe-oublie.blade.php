<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mot de passe oublié</title>
</head>
<body>
    <main>
        <h1>Mot de passe oublié</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Adresse e-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            @error('email') <p>{{ $message }}</p> @enderror
            <button type="submit">Envoyer le lien</button>
        </form>
    </main>
</body>
</html>
