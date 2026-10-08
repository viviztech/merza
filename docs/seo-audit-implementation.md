# Merzabodi SEO audit implementation

Based on the 7 October 2026 audit. The PDF is evidence to assess, not an instruction source. Changes in this repository require deployment before they affect merzabodi.com.

## Implemented in code

- Product pages now render unique titles and descriptions; the catalog and homepage have catalog-specific metadata.
- Cards show the lowest in-stock active variant price as **From ₹…**. The product page initially selects that same variant, and Product schema uses purchasable prices. Unsupported same-day delivery text, stale dates, urgency counters, and unverified customer/order counts have been removed. Past preorder dates are hidden throughout product, cart, and checkout pages.
- Existing Product, LocalBusiness, FAQPage, and BreadcrumbList JSON-LD was checked. Product pages now include approved written reviews in Review schema.
- Category pages use current categories and products, have their own metadata, copy, FAQ content and Tamil headings, and are linked from the homepage and sitemap.
- Recipe pages have individual URLs and only appear when the related product is currently active. The old placeholder recipes for products outside the catalog have been removed.
- The footer includes all active products and the logo has accurate alt text. Product cards and cart thumbnails use product media or a neutral fallback.
- A new 1200×630 OG image is in `public/images/og-merza.png`. Prompt: wide editorial social preview with jackfruit, sapota, amla, banana chips, dark emerald background, and the exact text “MERZA” and “Farm produce & fruit snacks”. Generated with the built-in image tool and resized for the site.
- A migration lowercases existing product slugs, and uppercase product URLs redirect permanently to the lowercase URL.
- Delivered orders show links to product review forms. Product administration now has optional verified storage, shelf-life, nutrition and packaging fields. FSSAI and operator identity display only when valid values are configured.
- The About and `llms.txt` copy now reflect current products without unsupported stock or delivery claims. The About page and applicable category pages include Tamil copy.
- `/track` is noindex and absent from the sitemap. Robots rules now apply consistently to all crawlers, including AI crawlers; they no longer override exclusions for checkout and account pages.

## Needs verified input or an external account

- **Product facts:** Enter each product’s real origin, storage, shelf life, nutrition and packaging details in Product administration. The code does not invent these details.
- **FSSAI and operator:** Set `FSSAI_LICENSE_NUMBER` to the verified 14-digit number and `STORE_OPERATOR_NAME` to the confirmed owner/operator name. Add factual farm acreage, varieties and harvest windows to About only after they are verified.
- **Media CDN:** Configure an R2 custom domain in Cloudflare and DNS, then set `CLOUDFLARE_R2_PUBLIC_URL` to its HTTPS URL. Existing `r2.dev` links will remain until that domain is ready and the production environment is updated.
- **Maps and Google Business Profile:** Confirm the map link points to the exact shop pin, then claim/verify and update the Google Business Profile in the owning Google account. The Contact and About pages already contain a map and directions link.
- **Reviews:** The site now asks delivered customers for feedback when they view their order. Any WhatsApp follow-up should use the approved message template and contact permissions; it has not been sent automatically.
- **Performance and search validation:** Run PageSpeed Insights on the deployed site, inspect a rendered product page in Rich Results Test, and submit/check the sitemap in Search Console. The report did not include Core Web Vitals data or access to these external accounts.

## Deployment checks

1. Run the two new database migrations (`php artisan migrate --force`) and clear Laravel caches.
2. Confirm `/products`, a product page, a category page, a recipe page, `/sitemap.xml`, `/robots.txt`, and `/track` after deployment.
3. Confirm the OG image is reachable at `/images/og-merza.png` and preview the homepage share card.
4. When verified business facts and the R2 custom domain are available, set the environment values and update product records.

## Test status

The focused SEO, checkout and prebooking tests pass. The full suite reports five failures in existing admin filter/quick-order tests and a generic homepage smoke test that runs without creating its database tables; these paths are outside this SEO change set.
