<?php

namespace App\Services;

use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use Illuminate\Database\Eloquent\Collection;

class RegionalService
{
    /**
     * Get all provinces.
     */
    public function getProvinces(): Collection
    {
        return CacheService::rememberManaged('regional', 'provinces_all', config('regional.cache_ttl'), function () {
            return Province::orderBy('name')->get();
        });
    }

    /**
     * Get districts, optionally filtered by province ID.
     */
    public function getDistricts(?int $provinceId = null): Collection
    {
        $cacheKey = $provinceId ? "districts_province_{$provinceId}" : 'districts_all';

        return CacheService::rememberManaged('regional', $cacheKey, config('regional.cache_ttl'), function () use ($provinceId) {
            $query = District::query()->orderBy('name');

            if ($provinceId) {
                $query->where('province_id', $provinceId);
            }

            return $query->get();
        });
    }

    /**
     * Get sub-districts, optionally filtered by district ID.
     */
    public function getSubdistricts(?int $districtId = null): Collection
    {
        $cacheKey = $districtId ? "subdistricts_district_{$districtId}" : 'subdistricts_all';

        return CacheService::rememberManaged('regional', $cacheKey, config('regional.cache_ttl'), function () use ($districtId) {
            $query = SubDistrict::query()->orderBy('name');

            if ($districtId) {
                $query->where('district_id', $districtId);
            }

            return $query->get();
        });
    }

    /**
     * Get villages, optionally filtered by sub-district ID.
     */
    public function getVillages(?int $subDistrictId = null): Collection
    {
        $cacheKey = $subDistrictId ? "villages_subdistrict_{$subDistrictId}" : 'villages_all';

        return CacheService::rememberManaged('regional', $cacheKey, config('regional.cache_ttl'), function () use ($subDistrictId) {
            $query = Village::query()->orderBy('name');

            if ($subDistrictId) {
                $query->where('sub_district_id', $subDistrictId);
            }

            return $query->get();
        });
    }

    /**
     * Flush all regional cache.
     */
    public function flushCache(): void
    {
        // This is a bit manual since we don't have a list of all province/district/subdistrict IDs in the cache key
        // But we can clear the known ones or use a cache tag if supported by the driver.
        // For simplicity, we can clearing the main lists.
        CacheService::forgetManaged('regional', 'provinces_all');
        CacheService::forgetManaged('regional', 'districts_all');
        CacheService::forgetManaged('regional', 'subdistricts_all');
        CacheService::forgetManaged('regional', 'villages_all');
    }
}
