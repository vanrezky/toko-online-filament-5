<?php

namespace App\Models;

use App\Traits\HasModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerLevel extends Model
{
    use HasFactory, HasModelTrait, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_credit_limit',
        'is_active',
    ];

    protected $casts = [
        'default_credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasCustomers(): bool
    {
        return $this->customers()->exists();
    }

    protected static function booted(): void
    {
        static::deleting(function (CustomerLevel $level) {
            if ($level->hasCustomers()) {
                return false;
            }
        });
    }
}