# GTM ecommerce setup

The storefront loads the container ID configured in **Admin → Settings → Integrations**. It pushes GA4 ecommerce events to `window.dataLayer` with an `ecommerce` object. Each event clears the previous ecommerce object first.

Events: `view_item`, `add_to_cart`, `remove_from_cart`, `view_cart`, `begin_checkout`, and `purchase`. Item IDs use the variant SKU, falling back to the variant ID. Currency is INR. `purchase` includes the order number as `transaction_id`, item subtotal as `value`, and delivery and packaging as `shipping`. Gateway purchases are pushed only after payment is verified; manual and prebooking orders are pushed on order placement.

In GTM, configure and publish:

1. A **Google tag** using the GA4 web stream measurement ID, triggered on all storefront pages.
2. A **GA4 Event** tag for each event above, triggered by a **Custom Event** with the same name.
3. Enable **Send ecommerce data** and select **Data Layer** as its source for each ecommerce event tag. Alternatively, map the `ecommerce.*` keys to event parameters using Data Layer Variables.

Use GTM **Preview** to verify the custom events and payloads on a product page, in the cart, and through checkout. Check GA4 **DebugView** to confirm the events reach the property. The container snippet alone does not send ecommerce data to GA4; the Google tag and event tags must be published in GTM.
