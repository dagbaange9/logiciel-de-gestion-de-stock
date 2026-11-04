<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réinitialiser le mot de passe</title>
</head>
<body>
    <main>
        <h1>Réinitialiser le mot de passe</h1>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email">Adresse e-mail</label>
                <input id="email" name="email" type="email" value="{{ $email }}" required>
            </div>
            <div>
                <label for="password">Nouveau mot de passe</label>
                <input id="password" name="password" type="password" required>
            </div>
            <div>
                <label for="password_confirmation">Confirmation</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required>
            </div>
            @error('email') <p>{{ $message }}</p> @enderror
            @error('password') <p>{{ $message }}</p> @enderror
            <button type="submit">Changer le mot de passe</button>
        </form>
    </main>
</body>
</html>
