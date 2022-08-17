<?php

namespace Dev1437\ModelParser\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Mechanic extends Model
{
    use HasFactory;

    public function carOwner(): HasOneThrough
    {
        return $this->hasOneThrough(User::class, Car::class);
    }
}
