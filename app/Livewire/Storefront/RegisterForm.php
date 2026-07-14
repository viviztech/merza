<?php

namespace App\Livewire\Storefront;

use App\Models\BotSetting;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class RegisterForm extends Component
{
    // ── Steps: 'form' → 'otp' ────────────────────────────────────────────────
    public string $step = 'form';

    // ── Form fields ───────────────────────────────────────────────────────────
    public string $name                  = '';
    public string $email                 = '';
    public string $phone                 = '';
    public string $password              = '';
    public string $password_confirmation = '';

    // ── OTP state ─────────────────────────────────────────────────────────────
    public string $otp      = '';
    public int    $attempts = 0;

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validate the full form and send a 6-digit OTP to the phone via WhatsApp.
     */
    public function sendOtp(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'                 => ['required', 'string', 'regex:/^\+?[0-9\s\-]{7,20}$/', 'unique:users,phone'],
            'password'              => ['required', 'confirmed', Password::min(8)],
            'password_confirmation' => ['required'],
        ], [
            'phone.unique'  => 'This phone number is already registered. Try logging in.',
            'phone.regex'   => 'Enter a valid phone number (e.g. +91 93600 64278).',
            'email.unique'  => 'This email is already registered. Try logging in.',
        ]);

        $phone = $this->normalizePhone($this->phone);

        // Rate limit: max 3 OTP sends per phone per 30 minutes
        $sendCountKey = "wa_otp_sends_{$phone}";
        $sendCount    = Cache::get($sendCountKey, 0);

        if ($sendCount >= 3) {
            $this->addError('phone', 'Too many OTP requests. Please wait 30 minutes and try again.');
            return;
        }

        // Generate and cache OTP (10-minute expiry)
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("wa_otp_{$phone}", $code, now()->addMinutes(10));
        Cache::put($sendCountKey, $sendCount + 1, now()->addMinutes(30));

        // Send via WhatsApp
        $settings  = BotSetting::current();
        $waService = new WhatsAppService($settings);

        $sent = $waService->sendTextMessage(
            $phone,
            "Your *Merza* verification code:\n\n*{$code}*\n\nThis code is valid for 10 minutes. Never share it with anyone.\n\n— Merza 🥭"
        );

        if (! $sent) {
            Log::error('RegisterForm: WhatsApp OTP send failed', ['phone' => $phone]);
            $this->addError('phone', 'Could not send WhatsApp OTP. Please check the number and try again.');
            return;
        }

        $this->step     = 'otp';
        $this->attempts = 0;
    }

    /**
     * Resend OTP — goes back through sendOtp() which re-validates and re-sends.
     */
    public function resendOtp(): void
    {
        $this->step = 'form';
        $this->sendOtp();
    }

    /**
     * Verify the entered OTP and complete registration on success.
     */
    public function verifyOtp(): void
    {
        $this->resetErrorBag();

        $this->validate(['otp' => ['required', 'digits:6']]);

        if ($this->attempts >= 5) {
            $this->addError('otp', 'Too many failed attempts. Please request a new OTP.');
            $this->step = 'form';
            return;
        }

        $phone  = $this->normalizePhone($this->phone);
        $stored = Cache::get("wa_otp_{$phone}");

        if (! $stored) {
            $this->addError('otp', 'This OTP has expired. Please request a new one.');
            $this->step = 'form';
            return;
        }

        if ($stored !== $this->otp) {
            $this->attempts++;
            $remaining = 5 - $this->attempts;
            $this->addError('otp', "Incorrect OTP. {$remaining} attempt(s) remaining.");
            return;
        }

        // Correct — consume the OTP and create account
        Cache::forget("wa_otp_{$phone}");
        $this->createAccount($phone);
    }

    /**
     * Go back to the form step to change details or request a new OTP.
     */
    public function goBack(): void
    {
        $this->step = 'form';
        $this->otp  = '';
        $this->resetErrorBag();
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function createAccount(string $normalizedPhone): void
    {
        $user = User::create([
            'name'     => trim($this->name),
            'email'    => strtolower(trim($this->email)),
            'phone'    => $normalizedPhone,
            'password' => Hash::make($this->password),
        ]);

        Auth::login($user);
        session()->regenerate();

        $this->redirect(route('account.dashboard'), navigate: true);
    }

    /**
     * Normalise a phone number to the format WhatsApp expects: digits only, with country code.
     * Assumes India (+91) when a bare 10-digit number is given.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone);

        // Bare 10-digit Indian mobile number
        if (strlen($digits) === 10) {
            return '91' . $digits;
        }

        // Already has country code (11–15 digits)
        return $digits;
    }

    public function render()
    {
        return view('livewire.storefront.register-form');
    }
}
