<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inscription</title>
</head>
<body>
    <main>
        <h1>Inscription</h1>

        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <div>
                <label for="name">Nom</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                @error('name') <p>{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email">Adresse e-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                @error('email') <p>{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone">Téléphone</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required>
                @error('phone') <p>{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password">Mot de passe</label>
                <input id="password" name="password" type="password" required>
                @error('password') <p>{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation">Confirmation du mot de passe</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required>
            </div>

            <button type="submit">Créer mon compte</button>
        </form>
    </main>
</body>
</html>
