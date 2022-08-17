<?php

namespace Dev1437\ModelParser\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Account extends Model
{
    use HasFactory;

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function pirateName(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes): ?string => "Argh! {$attributes['id']}"
        );
    }
}
