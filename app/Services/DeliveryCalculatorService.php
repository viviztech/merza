<?php

namespace App\Services;

use App\Models\DeliverySetting;
use App\Models\DeliveryZone;

class DeliveryCalculatorService
{
    private DeliverySetting $settings;
    /** @var \Illuminate\Database\Eloquent\Collection */
    private $zones;

    public function __construct()
    {
        $this->settings = DeliverySetting::current();
        $this->zones    = DeliveryZone::active()->get();
    }

    /**
     * Find the matching zone for a given city and state.
     * City zones take priority over state zones.
     */
    public function findZone(string $city, string $state): ?DeliveryZone
    {
        $city  = strtolower(trim($city));
        $state = strtolower(trim($state));

        // Check city match first
        foreach ($this->zones->where('match_type', 'city') as $zone) {
            foreach ($zone->match_values as $value) {
                if ($city === strtolower(trim($value))) {
                    return $zone;
                }
            }
        }

        // Fall back to state match
        foreach ($this->zones->where('match_type', 'state') as $zone) {
            foreach ($zone->match_values as $value) {
                if ($state === strtolower(trim($value))) {
                    return $zone;
                }
            }
        }

        return null;
    }

    /**
     * Calculate the delivery fee for a given city, state, and total order weight in kg.
     * Returns an array with fee breakdown, or null if the zone is unserviceable.
     */
    public function calculate(string $city, string $state, float $totalWeightKg): ?array
    {
        $zone = $this->findZone($city, $state);

        if (! $zone) {
            return null;
        }

        $ratePerKg      = $zone->rate_per_kg;
        $packingCharge  = 0;
        $packingWeight  = 0;
        $freeWeight     = 0;
        $chargeableWeight = $totalWeightKg;

        if ($totalWeightKg < $this->settings->free_weight_threshold_kg) {
            // Below threshold: add packing weight + packing charge
            $packingWeight    = $this->settings->packing_weight_kg;
            $packingCharge    = $this->settings->packing_charge;
            $chargeableWeight = $totalWeightKg + $packingWeight;
        } else {
            // At or above threshold: packing weight added but 1 kg free cancels it
            $packingWeight    = $this->settings->packing_weight_kg;
            $freeWeight       = $this->settings->free_weight_kg;
            $chargeableWeight = $totalWeightKg + $packingWeight - $freeWeight;
        }

        $shippingCost = $chargeableWeight * $ratePerKg;
        $totalFee     = $shippingCost + $packingCharge;

        return [
            'zone'              => $zone->name,
            'rate_per_kg'       => $ratePerKg,
            'order_weight_kg'   => $totalWeightKg,
            'packing_weight_kg' => $packingWeight,
            'free_weight_kg'    => $freeWeight,
            'chargeable_weight' => $chargeableWeight,
            'shipping_cost'     => round($shippingCost, 2),
            'packing_charge'    => $packingCharge,
            'total_fee'         => round($totalFee, 2),
        ];
    }

    public function settings(): DeliverySetting
    {
        return $this->settings;
    }
}
