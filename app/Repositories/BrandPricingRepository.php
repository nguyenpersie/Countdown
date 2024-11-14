<?php

namespace App\Repositories;

use App\Models\BrandPricing;

class BrandPricingRepository extends BaseRepository
{
    public function getModel()
    {
        return BrandPricing::class;
    }
}
