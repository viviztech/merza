<?php

namespace App\Livewire\Storefront;

use App\Models\Contact;
use App\Models\Lead;
use App\Services\MetaConversionsService;
use Livewire\Component;

/**
 * Public "Contact Us" enquiry form. Reuses the same Contact-lookup pattern
 * as CheckoutForm::sendWhatsAppConfirmation() so a returning visitor's
 * message lands on their existing CRM contact instead of creating a
 * duplicate, and always creates a fresh Lead (source: website) so staff
 * see it in the "New Leads" / "Enquiries Today" dashboard widgets.
 */
class ContactForm extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $message = '';

    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'message' => ['required', 'string', 'max:1000'],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $phone = preg_replace('/[^0-9+]/', '', $this->phone);

        $contact = Contact::where('phone', $phone)
            ->orWhere('phone', ltrim($phone, '+'))
            ->first();

        if (! $contact) {
            $contact = Contact::create([
                'name' => $this->name,
                'phone' => $phone,
                'email' => $this->email ?: null,
                'source' => 'website',
            ]);
        } elseif (blank($contact->email) && filled($this->email)) {
            $contact->update(['email' => $this->email]);
        }

        $lead = Lead::create([
            'contact_id' => $contact->id,
            'stage' => 'new',
            'source' => 'website',
            'notes' => $this->message,
        ]);

        $eventId = MetaConversionsService::eventId('Lead');
        $customData = [
            'content_name' => 'Contact enquiry',
            'content_category' => 'website_contact',
        ];
        app(MetaConversionsService::class)->trackFromRequest(
            'Lead',
            customerData: [
                'email' => $contact->email,
                'phone' => $contact->phone,
                'external_id' => (string) $lead->id,
            ],
            customData: $customData,
            eventId: $eventId,
            eventSourceUrl: route('contact'),
        );
        $this->dispatch('meta-event', eventName: 'Lead', eventId: $eventId, customData: $customData);

        $this->reset(['name', 'phone', 'email', 'message']);
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.storefront.contact-form');
    }
}
