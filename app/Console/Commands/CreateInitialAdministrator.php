<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-initial-administrator {--name= : Nom complet} {--email= : Adresse e-mail} {--phone= : Numero de telephone}')]
#[Description('Crée l’administrateur initial sans mot de passe en dur')]
class CreateInitialAdministrator extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name') ?: $this->ask('Nom complet');
        $email = $this->option('email') ?: $this->ask('Adresse e-mail');
        $phone = $this->option('phone') ?: $this->ask('Numéro de téléphone');
        $password = env('INITIAL_ADMIN_PASSWORD') ?: config('auth.initial_administrator_password');

        if (! is_string($password) || $password === '') {
            $password = $this->secret('Mot de passe initial');
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $administratorRole = Role::query()->where('name', Role::ADMINISTRATEUR)->first();

        if (! $administratorRole) {
            $this->error('Le rôle administrateur est absent. Exécutez d’abord le seeder des rôles.');

            return self::FAILURE;
        }

        if (User::query()->where('role_id', $administratorRole->id)->exists()) {
            $this->error('Un administrateur existe déjà.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $email));

        if (User::query()->withTrashed()->where('email', $email)->exists()) {
            $this->error('Cette adresse e-mail est déjà utilisée.');

            return self::FAILURE;
        }

        $administrator = User::query()->create([
            'name' => trim((string) $name),
            'email' => $email,
            'phone' => trim((string) $phone),
            'password' => $password,
        ]);
        $administrator->forceFill([
            'status' => User::STATUS_ACTIF,
            'role_id' => $administratorRole->id,
            'email_verified_at' => now(),
        ])->save();

        $this->info('Administrateur initial créé.');

        return self::SUCCESS;
    }
}
