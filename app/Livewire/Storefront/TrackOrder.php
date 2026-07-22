<?php

namespace App\Livewire\Storefront;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
#[Title('Track Your Order — Merza')]
class TrackOrder extends Component
{
    #[Url(as: 'order')]
    public string $orderNumber = '';
    public string $phone       = '';
    public ?Order $order       = null;
    public bool   $searched    = false;

    protected function rules(): array
    {
        return [
            'orderNumber' => 'required|string',
            'phone'       => 'required|string|min:10',
        ];
    }

    public function find(): void
    {
        $this->validate();
        $this->searched = true;
        $this->order    = null;

        $last10 = substr(preg_replace('/\D/', '', $this->phone), -10);

        $candidate = Order::where('order_number', trim($this->orderNumber))
            ->with('items')
            ->first();

        if ($candidate && substr(preg_replace('/\D/', '', $candidate->customer_phone), -10) === $last10) {
            $this->order = $candidate;
        }
    }

    public function searchAgain(): void
    {
        $this->order      = null;
        $this->searched   = false;
        $this->orderNumber = '';
        $this->phone       = '';
    }

    public function render()
    {
        return view('livewire.storefront.track-order');
    }
}
