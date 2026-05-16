<?php

namespace App\Models;

use App\Traits\HasModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallmentPlan extends Model
{
    use HasFactory, HasModelTrait, SoftDeletes;

    protected $fillable = [
        'tenor',
        'fee_percentage',
        'description',
        'is_active',
    ];

    protected $casts = [
        'fee_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function calculateTotal(float $principalAmount): float
    {
        return $principalAmount * (1 + ($this->fee_percentage / 100));
    }

    public function calculateMonthly(float $principalAmount): float
    {
        return $this->calculateTotal($principalAmount) / $this->tenor;
    }
}