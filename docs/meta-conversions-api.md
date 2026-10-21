# Meta Pixel and Conversions API

Merza sends standard browser Pixel events and matching server-side Conversions API (CAPI) events. Browser/server pairs use exactly the same `event_id`, so Meta can deduplicate them.

## Configuration

Set these values in the production secret manager or the untracked `.env` file, then run `php artisan config:clear` (or rebuild the cached configuration during deployment):

```dotenv
META_TRACKING_ENABLED=true
META_DATASET_ID=2128624118065839
META_CONVERSIONS_API_ENABLED=true
META_CONVERSIONS_API_ACCESS_TOKEN=your-server-only-token
META_GRAPH_API_VERSION=v26.0
META_REQUIRE_CONSENT=true
```

Do not use a `VITE_` variable for the CAPI token and do not add it to `.env.example`, source control, Blade, JavaScript, or GTM. Rotate the token in Meta Events Manager if it was ever exposed.

The Pixel/Dataset ID is public by design. `META_DATASET_ID` takes precedence over the old Admin > Integrations Pixel ID; the old value remains only as a temporary browser-Pixel fallback for existing deployments.

Deploy the code, configure the production environment, then run:

```bash
php artisan migrate --force
php artisan config:cache
```

The existing queue worker must remain running (`php artisan queue:work`, or the project's Supervisor process). Verified purchases are deliberately queued after the database transaction commits, so a payment webhook is never delayed by Meta's API.

## Events

| Activity | Browser Pixel | CAPI |
| --- | --- | --- |
| Storefront page | `PageView` | — |
| Product opened | `ViewContent` | `ViewContent` |
| Product added/increased in cart | `AddToCart` | `AddToCart` |
| Checkout opened | `InitiateCheckout` | `InitiateCheckout` |
| Contact enquiry submitted | `Lead` | `Lead` |
| OTP registration completed | `CompleteRegistration` | `CompleteRegistration` |
| Payment verified as paid | `Purchase` when the confirmation page is visited | `Purchase` from the paid-status transition |

Purchase is not emitted when an order is placed. It is queued only when existing server-side payment verification changes `payment_status` to `paid` (SabPaisa signed return/webhook, verified screenshot flow, or staff confirmation). This is deliberate.

## Consent and privacy

When `META_REQUIRE_CONSENT=true`, Pixel/CAPI tracking starts only after the visitor chooses **Allow**. Their choice is stored in the `merza_meta_consent` first-party cookie for 180 days and can be changed through **Privacy Choices** in the footer. For consented checkouts, the order retains the consent timestamp and an encrypted matching context (`_fbp`, `_fbc`, request IP, and user agent) solely to let a later verified-payment webhook report the purchase.

Review the consent language and privacy policy with counsel for every jurisdiction in which Merza operates. Set `META_REQUIRE_CONSENT=false` only where the applicable legal basis permits tracking without opt-in.

## Test Events verification

1. In Meta Events Manager, select Dataset `2128624118065839`, then open **Test Events** and copy its test-event code.
2. Set `META_TEST_EVENT_CODE` to that value in the environment and clear/rebuild Laravel config.
3. Open a private browser window, accept tracking, then view a product, add it to cart, start checkout, submit the contact form, and complete a test registration. Each should appear in Test Events.
4. Complete a test payment only through a verified SabPaisa webhook/return or a staff-confirmed test order. Confirm one `Purchase` appears, with a matching browser and server pair deduplicated by the same event ID.
5. Remove `META_TEST_EVENT_CODE`, clear/rebuild config again, and use **Diagnostics** in Events Manager to monitor production events.

Meta's current implementation and testing guidance is available in the [Conversions API documentation](https://developers.facebook.com/docs/marketing-api/conversions-api/) and [Meta Pixel documentation](https://developers.facebook.com/docs/meta-pixel/).
