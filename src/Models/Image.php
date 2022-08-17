<?php

namespace Dev1437\ModelParser\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use HasFactory;

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
