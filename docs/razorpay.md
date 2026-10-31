# Razorpay Standard Checkout

Merza keeps its manual UPI/screenshot checkout and SabPaisa integration intact. Razorpay is enabled only when `PAYMENT_GATEWAY=razorpay`; until then, the current manual checkout remains active.

## Test Mode setup

1. In the Razorpay Dashboard, create **Test Mode** API keys. Do not use Live Mode keys while validating this integration.
2. Set these server-side environment values. `RAZORPAY_KEY_ID` is the public `rzp_test_...` key; the other two values are secrets and must not be committed, put in `VITE_*`, or shared with the browser.

   ```dotenv
   PAYMENT_GATEWAY=razorpay
   RAZORPAY_KEY_ID=rzp_test_your_key_id
   RAZORPAY_KEY_SECRET=your_test_key_secret
   ```

3. Install the PHP dependencies and apply the schema change:

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan optimize:clear
   ```

4. In Razorpay Dashboard **Test Mode > Webhooks**, create a webhook for the public HTTPS URL:

   ```text
   https://your-domain.example/webhook/razorpay
   ```


5. Add a product, proceed through checkout, and complete a Test Mode payment using the current test methods from Razorpay's dashboard/docs. The browser opens Razorpay Standard Checkout with only the public key and Razorpay order ID. Merza verifies the returned signature, fetches the payment from Razorpay, verifies the Razorpay order ID/currency/amount against the stored order total, and marks it paid only when Razorpay reports `captured`.

6. Test all of these cases before launch:

   - Successful payment: the order becomes `paid`, has Razorpay IDs, and reaches the success confirmation page.
   - Failed or dismissed payment: the order remains `unpaid`; the customer sees a retry button that reopens the same Razorpay order.
   - A deliberately invalid signature or changed amount: the verification endpoint rejects it and the order stays unpaid.
   - Webhook delivery: Razorpay shows a successful delivery and a `payment.captured` webhook marks the matching order paid even if the browser is closed.

## Live Mode activation

1. Complete Razorpay account activation and use **Live Mode** API keys only after Test Mode checks pass.
2. Replace the three Razorpay values in the production secret manager with the Live Mode key ID, key secret, and a newly generated Live Mode webhook secret. Keep `PAYMENT_GATEWAY=razorpay`.
3. Create/update the Live Mode webhook for the production HTTPS URL and subscribe to the same events. Test a small real payment and webhook delivery.
4. Run `php artisan optimize:clear` on each deployment after changing environment values. Rotate a key or webhook secret immediately if it is ever exposed.

## Implementation notes

- Razorpay order/payment IDs, their last known gateway status, and the verified timestamp are stored on the existing `orders` table. Their unique indexes prevent one Razorpay payment from being applied to two Merza orders.
- `payment_reference` continues to hold the captured payment ID for existing admin/reporting code. Existing manual UPI and SabPaisa flows are not changed.
- The webhook verifies `X-Razorpay-Signature` against the raw request body. Do not add this route to authentication middleware or CSRF protection; it is already excluded by the existing `webhook/*` CSRF rule and has cryptographic verification.
- Razorpay documents the current Standard Checkout and PHP SDK flow in its [server integration guide](https://razorpay.com/docs/payments/server-integration/php/) and [official PHP SDK repository](https://github.com/razorpay/razorpay-php).
