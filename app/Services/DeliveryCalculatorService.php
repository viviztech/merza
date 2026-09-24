<?php

namespace App\Services;

use App\Models\DeliverySetting;
use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Collection;

class DeliveryCalculatorService
{
    private DeliverySetting $settings;

    /** @var Collection */
    private $zones;

    public function __construct()
    {
        $this->settings = DeliverySetting::current();
        $this->zones = DeliveryZone::active()->get();
    }

    /**
     * Find the matching zone for a given city and state.
     * City zones take priority over state zones.
     */
    public function findZone(string $city, string $state): ?DeliveryZone
    {
        $city = strtolower(trim($city));
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

        return $this->calculateForZone($zone, $totalWeightKg);
    }

    /**
     * Calculate the delivery fee for an already-known zone (e.g. one the customer
     * explicitly picked) and total order weight in kg.
     */
    public function calculateForZone(DeliveryZone $zone, float $totalWeightKg): array
    {
        $ratePerKg = $zone->rate_per_kg;
        $threshold = $this->settings->free_weight_threshold_kg; // 5 kg
        $belowThreshold = $totalWeightKg < $threshold;

        if ($belowThreshold) {
            // Below 5 kg: charge actual weight + ₹50 packing charge (no packing weight added)
            $chargeableWeight = $totalWeightKg;
            $packingWeight = 0;
            $packingCharge = $this->settings->packing_charge;
        } else {
            // 5 kg and above: packing material adds packing_weight_kg to the chargeable
            // weight, but free_weight_kg offsets it (both default to 1 kg, netting to no
            // extra charge) — see the formula documented on the Delivery Settings page.
            // No flat ₹50 packing charge at this tier.
            $packingWeight = $this->settings->packing_weight_kg;
            $chargeableWeight = max(0, $totalWeightKg + $packingWeight - $this->settings->free_weight_kg);
            $packingCharge = 0;
        }

        $unroundedChargeableWeight = $chargeableWeight;
        $minimumWeight = max(0, (float) $this->settings->minimum_chargeable_weight_kg);
        $billingStep = max(0.001, (float) $this->settings->billing_weight_step_kg);

        // Courier partners bill light parcels at a minimum slab and normally
        // round the remaining weight upward (commonly to the next 500 g).
        $chargeableWeight = max($chargeableWeight, $minimumWeight);
        $chargeableWeight = ceil(($chargeableWeight - 0.0000001) / $billingStep) * $billingStep;

        $shippingCost = $chargeableWeight * $ratePerKg;
        $totalFee = $shippingCost + $packingCharge;

        return [
            'zone' => $zone->name,
            'rate_per_kg' => $ratePerKg,
            'order_weight_kg' => $totalWeightKg,
            'below_threshold' => $belowThreshold,
            'packing_weight_kg' => $packingWeight,
            'unrounded_chargeable_weight' => round($unroundedChargeableWeight, 3),
            'minimum_chargeable_weight_kg' => $minimumWeight,
            'billing_weight_step_kg' => $billingStep,
            'chargeable_weight' => $chargeableWeight,
            'shipping_cost' => round($shippingCost, 2),
            'packing_charge' => $packingCharge,
            'total_fee' => round($totalFee, 2),
        ];
    }

    public function settings(): DeliverySetting
    {
        return $this->settings;
    }
}
