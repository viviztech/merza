<?php

namespace App\Livewire\Storefront;

use App\Services\DeliveryCalculatorService;
use App\Services\PincodeService;
use Livewire\Component;

class PincodeChecker extends Component
{
    public string $pincode     = '';
    public bool   $checked     = false;
    public bool   $serviceable = false;
    public ?string $zoneName   = null;
    public ?int    $etaDays    = null;

    public function check(): void
    {
        $this->reset(['checked', 'serviceable', 'zoneName', 'etaDays']);
        $this->resetErrorBag();

        if (! preg_match('/^\d{6}$/', $this->pincode)) {
            $this->addError('pincode', 'Enter a valid 6-digit pincode.');
            return;
        }

        $result = (new PincodeService())->lookup($this->pincode);
        $this->checked = true;

        if (! $result || empty($result['district']) || empty($result['state'])) {
            return;
        }

        $zone = (new DeliveryCalculatorService())->findZone($result['district'], $result['state']);

        if ($zone) {
            $this->serviceable = true;
            $this->zoneName    = $zone->name;
            $this->etaDays     = $zone->eta_days;
        }
    }

    public function render()
    {
        return view('livewire.storefront.pincode-checker');
    }
}
