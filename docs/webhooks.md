# Merza webhooks

Create a destination from **Admin → Settings → Webhooks → Create Webhook**.
Choose the event, enter the HTTPS delivery URL, and set the webhook to **Active**.
If the Secret field is left empty during creation, Merza generates a `whsec_...`
secret and shows it once in the success notification.

Merza sends a `POST` request with `Content-Type: application/json` and these
headers:

```text
X-Merza-Event: cart.added
X-Merza-API-Version: v1
X-Merza-Timestamp: 1791779400
X-Merza-Signature: sha256=<hex hmac>
```

The signature is calculated over the exact request body:

```text
HMAC-SHA256(secret, X-Merza-Timestamp + "." + raw_request_body)
```

The JSON envelope is:

```json
{
  "event": "checkout.started",
  "created_at": "2026-10-12T10:00:00+05:30",
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
line items.

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
// Process $payload['event'] and $payload['data'].
```
