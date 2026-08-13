<?php

namespace Tests\Feature;

use App\Livewire\Storefront\RegisterForm;
use App\Models\BotSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerRegistrationOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        BotSetting::current()->update([
            'whatsapp_phone_number_id' => '123456789',
            'whatsapp_access_token' => 'test-token',
        ]);
    }

    private function validForm(): array
    {
        return [
            'name' => 'Registration Test',
            'email' => 'register@example.com',
            'phone' => '93440 64631',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ];
    }

    public function test_authentication_template_includes_copy_code_button_and_registration_completes(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.registration-test']],
            ]),
        ]);

        $component = Livewire::test(RegisterForm::class)
            ->set($this->validForm())
            ->call('sendOtp')
            ->assertHasNoErrors()
            ->assertSet('step', 'otp');

        $phone = '919344064631';
        $code = Cache::get("wa_otp_{$phone}");

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        Http::assertSent(function (Request $request) use ($code, $phone): bool {
            $components = $request->data()['template']['components'];

            return $request['to'] === $phone
                && $request['template']['name'] === 'merza_otp'
                && $components[0]['parameters'][0]['text'] === $code
                && $components[1]['type'] === 'button'
                && $components[1]['sub_type'] === 'url'
                && $components[1]['index'] === '0'
                && $components[1]['parameters'][0]['text'] === $code;
        });

        $component->set('otp', $code)
            ->call('verifyOtp')
            ->assertHasNoErrors()
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'register@example.com',
            'phone' => $phone,
        ]);
        $this->assertNull(Cache::get("wa_otp_{$phone}"));
    }

    public function test_failed_meta_send_does_not_cache_an_otp_or_consume_the_rate_limit(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Template rejected'],
            ], 400),
        ]);

        Livewire::test(RegisterForm::class)
            ->set($this->validForm())
            ->call('sendOtp')
            ->assertHasErrors(['phone'])
            ->assertSet('step', 'form');

        $phone = '919344064631';
        $this->assertNull(Cache::get("wa_otp_{$phone}"));
    }

    public function test_customer_can_request_another_otp_without_a_waiting_period(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.registration-resend']],
            ]),
        ]);

        $component = Livewire::test(RegisterForm::class)
            ->set($this->validForm());

        foreach (range(1, 4) as $attempt) {
            $component->call('sendOtp')
                ->assertHasNoErrors()
                ->assertSet('step', 'otp')
                ->call('goBack');
        }

        Http::assertSentCount(4);
        $this->assertMatchesRegularExpression('/^\d{6}$/', Cache::get('wa_otp_919344064631'));
    }
}
