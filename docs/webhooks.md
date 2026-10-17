# Merza webhooks

Create a destination from **Admin → Settings → Webhooks → Create Webhook**.
Choose the event, enter the HTTPS delivery URL, and set the webhook to **Active**.
If the Secret field is left empty during creation, Merza generates a `whsec_...`
secret and shows it once in the success notification.

Merza sends a `POST` request with `Content-Type: application/json` and these
headers:

```text
X-Merza-Event: cart.added
X-Merza-Event-ID: evt_8e31481e-2b21-4ab1-8a1d-438c553e5663
X-Merza-API-Version: v1
X-Merza-Timestamp: 1791779400
X-Merza-Signature: sha256=<hex hmac>
Idempotency-Key: evt_8e31481e-2b21-4ab1-8a1d-438c553e5663
```

The signature is calculated over the exact request body:

```text
HMAC-SHA256(secret, X-Merza-Timestamp + "." + raw_request_body)
```

The JSON envelope is:

```json
{
  "event_id": "evt_8e31481e-2b21-4ab1-8a1d-438c553e5663",
  "event": "checkout.started",
  "timestamp": "2026-10-12T10:00:00+05:30",
  "created_at": "2026-10-12T10:00:00+05:30",
  "api_version": "v1",
  "data": {
    "cart": {
      "item_count": 2,
      "subtotal": 598,
      "items": []
    },
    "source": "website"
  }
}
```

Available events are `customer.created`, `customer.updated`, `cart.added`,
`checkout.started`, `order.created`, and `order.updated`. Cart and checkout
events contain the current cart; customer events contain customer contact data;
order events contain the order, customer, delivery, totals, payment status, and
line items. Order customer phone numbers are sent in E.164 form (for example,
`+919876543210`) along with the captured `phone_raw` value; all prices include
`currency: "INR"`.

Merza queues deliveries with four attempts (after 1, 5, and 15 minute backoffs).
Every retry uses the same event ID and byte-for-byte request body. Receivers
should store `event_id` or `Idempotency-Key` and treat a repeat as already
processed. Open a webhook record in Admin to see its delivery log, HTTP status,
response body, error, attempts, and request JSON.

Example receiver verification in PHP:

```php
$rawBody = file_get_contents('php://input');
$timestamp = $_SERVER['HTTP_X_MERZA_TIMESTAMP'] ?? '';
$provided = $_SERVER['HTTP_X_MERZA_SIGNATURE'] ?? '';
$expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $webhookSecret);

if (! hash_equals($expected, $provided)) {
    http_response_code(401);
    exit;
}

$payload = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
$eventId = $payload['event_id'];

// Store $eventId before processing. If it already exists, return a 2xx response
// without processing it a second time. Then process $payload['event'] and data.
```
