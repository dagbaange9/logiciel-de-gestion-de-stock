<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description'])]
class Role extends Model
{
    public const ADMINISTRATEUR = 'administrateur';

    public const MAGASINIER = 'magasinier';

    public const VENDEUR = 'vendeur';

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
