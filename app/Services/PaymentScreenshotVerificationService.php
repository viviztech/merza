<?php

namespace App\Services;

use App\Models\BotSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Reads a payment screenshot with ChatGPT vision and decides whether it matches
 * the order total. This is a plausibility check on what the image itself
 * claims — not a real bank/UPI statement lookup — so a match still gets
 * tagged as AI-verified rather than silently treated as unconditional truth,
 * and anything short of a clean match always routes to a human.
 */
class PaymentScreenshotVerificationService
{
    // ₹1 tolerance for rounding/paise differences between the order total and
    // whatever amount the screenshot displays.
    private const AMOUNT_TOLERANCE = 1.0;

    public function __construct(private readonly BotSetting $settings) {}

    /**
     * @return array{status: string, extracted_amount: float|null, extracted_reference: string|null, raw: string|null}
     */
    public function verify(Order $order, string $imageUrl): array
    {
        if (empty($this->settings->openai_api_key)) {
            return $this->result('ai_unclear', null, null, null);
        }

        $openAi = new OpenAiService(
            $this->settings->openai_api_key,
            $this->settings->openai_model ?: 'gpt-4o-mini',
        );

        $systemPrompt = <<<PROMPT
You are verifying a UPI/bank payment screenshot for an e-commerce order. Read
the image carefully and respond with ONLY a JSON object, no other text, in
exactly this shape:
{"status": "success" | "failed" | "unclear", "amount": <number or null>, "reference": "<UTR/transaction id or null>"}

"status" is "success" only if the screenshot clearly shows a completed,
successful payment. Use "failed" if it shows a declined/failed transaction.
Use "unclear" if you cannot confidently read the amount or the status.
PROMPT;

        $userText = "Order total to match against: ₹{$order->total}. Read this payment screenshot and report the JSON.";

        $raw = $openAi->chatWithImage($systemPrompt, $userText, $imageUrl);

        $parsed = $raw ? json_decode($raw, true) : null;

        if (! is_array($parsed) || ! isset($parsed['status'])) {
            Log::warning('PaymentScreenshotVerificationService: could not parse AI response', [
                'order_id' => $order->id,
                'raw'      => $raw,
            ]);
            return $this->result('ai_unclear', null, null, $raw);
        }

        $extractedAmount = is_numeric($parsed['amount'] ?? null) ? (float) $parsed['amount'] : null;
        $extractedRef    = is_string($parsed['reference'] ?? null) ? $parsed['reference'] : null;
        $aiStatus        = $parsed['status'];

        $amountMatches = $extractedAmount !== null
            && abs($extractedAmount - (float) $order->total) < self::AMOUNT_TOLERANCE;

        $verdict = match (true) {
            $aiStatus === 'success' && $amountMatches => 'ai_matched',
            $aiStatus === 'failed'                    => 'ai_mismatch',
            default                                    => 'ai_unclear', // success-but-wrong-amount, or genuinely unclear
        };

        return $this->result($verdict, $extractedAmount, $extractedRef, $raw);
    }

    /**
     * @return array{status: string, extracted_amount: float|null, extracted_reference: string|null, raw: string|null}
     */
    private function result(string $status, ?float $amount, ?string $reference, ?string $raw): array
    {
        return [
            'status'              => $status,
            'extracted_amount'    => $amount,
            'extracted_reference' => $reference,
            'raw'                 => $raw,
        ];
    }
}
