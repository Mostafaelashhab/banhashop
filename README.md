# بنها شوب — Banha.shop

A local commerce marketplace for **Banha, Egypt**. Not a store, not a generic
multi-vendor platform:

```
Central product  →  Multiple local offers  →  Shipping options
                 →  Real total price  →  Best deal  →  Fast local delivery
```

One product exists once in a shared catalog. Local stores attach competing
offers to it. The customer compares **price + delivery = the real total**, which
is frequently not the offer with the cheapest sticker price.

---

## Running it

Requires PHP 8.3+, MySQL 8+, Composer.

```bash
composer install
cp .env.example .env && php artisan key:generate
mysql -u root -e "CREATE DATABASE banha_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate --seed
php artisan serve
```

There is **no front-end build step**. The stylesheet is hand-written and served
from `public/assets/css/app.css`; there is no JavaScript bundle to compile.

### Seeded accounts

All passwords are `password`.

| Role     | Login                     |
| -------- | ------------------------- |
| Admin    | `admin@banha.shop`        |
| Seller   | `sherbiny@banha.shop`     |
| Seller   | `elamin@banha.shop`       |
| Customer | `customer@banha.shop`     |

### Tests

```bash
mysql -u root -e "CREATE DATABASE banha_shop_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan test
```

Tests run against MySQL, not SQLite, because search depends on MySQL FULLTEXT —
SQLite would pass tests the real application cannot run.

---

## The core rule

```
Product != Seller Offer
```

`products` describe *what a thing is*. `seller_offers` describe *what one store
charges for it, how many they have, and when they last confirmed that*. Price
and stock never live on a product.

Variants (256GB / 512GB) are sibling products sharing a `parent_id` variant-group
key, so an offer always points at exactly one buyable thing.

## Where the logic lives

Blade renders. It never queries, sorts or prices.

| Concern | Class |
| --- | --- |
| Delivery quotes for a seller into a zone | `Services\Shipping\ShippingQuoteService` |
| Offer ranking and the comparison board | `Services\Catalog\OfferComparisonService` |
| Price/stock writes + the audit trail | `Services\Catalog\OfferInventoryService` |
| Catalog listing and Arabic search | `Services\Catalog\ProductQueryService` |
| Cart grouping and per-seller totals | `Services\Cart\CartSummaryBuilder` |
| Turning a priced cart into an order | `Services\Checkout\PlaceOrderService` |
| Seller order lifecycle | `Services\Orders\SellerOrderWorkflow` |
| Metadata, JSON-LD, indexing rules | `Support\Seo\*` |

### Ranking is deterministic and explainable

| Mode | Order |
| --- | --- |
| `total` (default) | cheapest total → faster promise → fresher stock → id |
| `price` | cheapest product price → cheapest total → id |
| `fastest` | earliest promised delivery → cheapest total → id |

Offers that cannot reach the selected zone sort last in every mode. A "best
deal" badge is only awarded when the win is **unambiguous** — a tie crowns
nobody.

### Shipping

```
ShippingProvider → ShippingRate (per zone; seller_id NULL = platform rate)
Seller           → enabled providers + the zones it serves
Shipment         → what actually happened
```

A rate with a `seller_id` beats the platform rate, which is how self-delivery
and negotiated pricing are both expressed. An in-house fleet ("توصيل بنها شوب",
already seeded as inactive) is one more provider row, not an order rewrite.

Quotes are priced against the **basket subtotal**, so free-delivery thresholds
apply exactly when they really apply — this is what flips the winner on the
seeded iPhone.

### Orders

```
Order (what the customer agreed to pay)
  └── SellerOrder (what one store must fulfil)
        ├── OrderItem   (product + price snapshot)
        └── Shipment
Payment (COD today; the enum is the extension point)
```

The customer-facing order status is **derived** from its seller orders, so it
cannot drift out of sync with fulfilment. Stock is decremented inside the
checkout transaction with the offer rows locked, and returned to the shelf when
a store rejects or cancels.

### Inventory trust

Every offer carries `inventory_updated_at`, and every price/stock write appends
to `offer_inventory_logs`. The storefront says "المخزون محدَّث منذ ١٤ دقيقة" from
that real timestamp, and flags anything older than `BANHA_STALE_INVENTORY_HOURS`
instead of presenting it as current. A seller can confirm "still accurate" in one
tap without changing a number.

`php artisan offers:expire-stale` (scheduled daily) retires offers nobody has
confirmed for a month. It marks them, never deletes them.

No trust metric is ever displayed unless it is computed from real rows —
`Seller::acceptanceRate()` returns `null` until there is enough data.

### Arabic search

MySQL FULLTEXT over a normalised `search_text` column. `Support\ArabicText`
folds أ/إ/آ→ا, ة→ه, ى→ي, strips diacritics and Arabic punctuation, and converts
Arabic-Indic digits, so "آيفون ١٧ برو" and "ايفون 17 برو" hit the same row.
Brands carry an Arabic alias (`سامسونج` → Samsung) and products carry
`search_keywords`, because customers do not type Latin brand names.

Query strategy: require every token first, relax to any-token if that returns
nothing, fall back to LIKE for tokens shorter than the InnoDB minimum. A barcode
redirects straight to the product.

No dedicated search engine — a single-city catalog does not justify one.

### SEO

`Support\Seo\IndexingPolicy` allows exactly one indexable URL per real page:
anything with a query string beyond `page` becomes `noindex, follow` and
canonicalises back to the clean URL. Search results are never indexable. The
sitemap lists only products, categories, stores and static pages, and gives
products with real local competition a higher priority.

Structured data is server-rendered `Product` + `AggregateOffer` +
`BreadcrumbList`. There is deliberately **no** `aggregateRating` or review
markup: there are no reviews yet, and inventing them would be fraud.

### Reactivity (Livewire)

Four surfaces are reactive. Everything else is still a plain server-rendered
page, and there is no SPA router.

| Component | What it does |
| --- | --- |
| `Livewire\OfferBoard` | Sort and zone changes re-price the comparison in place; polls for stock/price changes |
| `Livewire\ZonePicker` | Writes the zone to the session and announces `zone-changed` |
| `Livewire\CartCounter` | Header badge, updated by the `cart-updated` event |
| `Livewire\ShoppingCart` | Quantity and removal without a page load, re-quoting delivery each time |
| `Livewire\Seller\OfferInventory` | Inline price/stock editing and one-tap stock confirmation |

Three rules keep this from undoing the rest of the architecture:

**The first paint is still server-rendered HTML.** Livewire renders on the
server, so the offers, prices, totals and JSON-LD are all in the initial
response. Reactivity is an upgrade on interaction, not a replacement for SSR —
the SEO and LCP properties are unchanged.

**No pricing logic moved to the browser.** Components call
`OfferComparisonService`, `ShippingQuoteService`, `CartManager` and
`OfferInventoryService` exactly as the controllers do. There is still one
implementation of the shipping and ranking rules.

**Every interaction degrades.** Sort controls are real `<a href>` links, cart
and add-to-cart controls are real `<form method="POST">` posts to the existing
routes, and the zone picker keeps a `<noscript>` submit. With JavaScript off the
whole marketplace still works — it just reloads the page.

Ids arriving from the client are never trusted: `OfferBoard::addToCart()`
re-resolves the offer through the current product, cart lines are re-checked
against the signed-in customer's cart, and seller actions are re-checked against
the signed-in store. `#[Locked]` prevents the board being repointed at another
product.

Live stock and price use `wire:poll.45s.visible`, so a page only polls while it
is actually on screen. Swapping that for true push is a small change — add
Laravel Reverb and broadcast from `OfferInventoryService` — but it costs a
long-running websocket process to operate, which the MVP does not need.

### Performance

Server-rendered Blade, no SPA router, no build step. Livewire's runtime is the
only JavaScript on the storefront.

Offer aggregates (`offers_count`, `sellers_count`, `min_price_cents`) are
denormalised onto `products` and maintained by `SellerOfferObserver`, so listing
pages sort and filter without touching `seller_offers`. A product page costs a
fixed handful of queries no matter how many stores compete on it. Tests run with
`Model::preventLazyLoading()` on, so an N+1 fails the build.

---

## What is deliberately not built

Loyalty, wallets, coupons, recommendations, notifications, reviews, online
payments, microservices, a JS framework. The MVP's job is to prove that a
central catalog with competing local offers and honest total pricing is useful.
Everything above is a Phase 6 decision to make with real usage data.

The metric that decides it is on the admin dashboard: **how many products have
more than one local seller**.
