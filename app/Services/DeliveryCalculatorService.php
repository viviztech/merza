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

        $ratePerKg    = $zone->rate_per_kg;
        $threshold    = $this->settings->free_weight_threshold_kg; // 5 kg
        $belowThreshold = $totalWeightKg < $threshold;

        if ($belowThreshold) {
            // Below 5 kg: charge actual weight + ₹50 packing charge (no packing weight added)
            $chargeableWeight = $totalWeightKg;
            $packingWeight    = 0;
            $packingCharge    = $this->settings->packing_charge;
        } else {
            // 5 kg and above: customer gets 1 kg free product (handled by business),
            // packing material adds 1 kg to chargeable weight. No ₹50 packing charge.
            $packingWeight    = $this->settings->packing_weight_kg; // 1 kg
            $chargeableWeight = $totalWeightKg + $packingWeight;
            $packingCharge    = 0;
        }

        $shippingCost = $chargeableWeight * $ratePerKg;
        $totalFee     = $shippingCost + $packingCharge;

        return [
            'zone'              => $zone->name,
            'rate_per_kg'       => $ratePerKg,
            'order_weight_kg'   => $totalWeightKg,
            'below_threshold'   => $belowThreshold,
            'packing_weight_kg' => $packingWeight,
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
