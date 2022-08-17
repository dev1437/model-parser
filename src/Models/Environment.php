<?php

namespace Dev1437\ModelParser\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Environment extends Model
{
    use HasFactory;

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }
}
