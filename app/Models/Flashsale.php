<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flashsale extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(ProductFlashsale::class);
    }

    public function scopeCurrent($query)
    {
        return $query
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->latest('start_time');
    }
}
