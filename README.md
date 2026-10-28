# Privatus Analytics for Shopware 6

**See how many people visit your Shopware store, and which visits end in a sale. No cookies, no bots.**

You want to know if people visit your store and which visits turn into sales.
But Google Analytics uses cookies, so in many countries you must show a cookie
pop-up first (the box that asks visitors to say yes to cookies). Many people
click no, so they never show up in your numbers. Bots add fake visits on top.

This plugin adds the Privatus Analytics script to your Shopware storefront. It
also counts Add to Cart, Checkout Started and Purchase with the order total.
You paste no code. The script sets no cookies and keeps no IP addresses. Bots
are filtered out before they are counted.

So you see how many real shoppers came, which pages they looked at, where they
came from and how many bought, on one simple dashboard. A report comes to your
inbox each week, and you can set alerts for when traffic jumps or drops. And you
don't need a cookie pop-up for your analytics.

**Free plan, no card:** 10,000 events a month (an event is a page view or an action you track) on up to 3 sites. [Start free at privatusanalytics.com](https://privatusanalytics.com).

- **No cookies.** No cookies and no hidden IDs on your visitors' devices, and no IP
  addresses are kept. Laws differ by country, so check your own legal advice.
- **Real people, not bots.** Known bots, data centers and spam visits are left
  out on every plan.
- **Small and fast.** The script is about 3 KB when compressed.

## What this plugin does

- Adds the Privatus Analytics script to the `<head>` of every storefront page.
- Counts these shop events:

  | Event | When | What is sent |
  | --- | --- | --- |
  | `Add to Cart` | A shopper adds a product to the cart | Only the event name |
  | `Checkout Started` | The order review page opens (once per cart) | Only the event name |
  | `Purchase` | The thank-you page opens (once per order, a reload does not count it twice) | The order total and its currency |

- Never sends an order number, product, customer name, email or address.
- Never sends events from your server. Everything goes from the shopper's browser through the script.
- Stores nothing in the shopper's browser. The "once per order" check uses your shop's own session on the server.

You need a Privatus Analytics account to see reports, and there is a free plan. Sign up at https://privatusanalytics.com.

## Requirements

Shopware 6.6 or 6.7.

## Installation

### From the Shopware Store

In your Administration, go to Extensions, Store. Search for "Privatus Analytics", then install and activate it.

### With Composer

```bash
composer require privatus/shopware-privatus-analytics
bin/console plugin:refresh
bin/console plugin:install --activate PrivatusAnalytics
bin/console cache:clear
```

### From a zip file

Download the zip from the [releases page](https://github.com/Privatus-Analytics/shopware/releases). In your Administration, go to Extensions, My extensions, Upload extension. Then install and activate it.

## Setup

1. In your Administration, go to Extensions, My extensions, Privatus Analytics, Configure.
2. Enter your site ID. It starts with `pa_`. You find it in Privatus Analytics under Site settings, Tracking.
3. Save. Nothing is added to your shop until you enter a valid site ID.

Each sales channel can have its own site ID. Pick the sales channel at the top of the settings page.

## Settings

| Setting | Default | What it does |
| --- | --- | --- |
| Site ID | empty | The `data-site` value. Required. Must look like `pa_` followed by letters and numbers. |
| Extra reports | all five | Time on page, outbound links and downloads, page speed, click maps, surveys. Sets `data-modules`. See the [modules docs](https://privatusanalytics.com/docs/tracker/modules). |
| Count "Add to Cart" | on | Sends `Add to Cart` when an add to cart form is sent. |
| Count "Checkout Started" | on | Sends `Checkout Started` on the order review page, once per cart. |
| Count "Purchase" with the order total | on | Sends `Purchase` with `revenue` and `currency` on the thank-you page, once per order. |
| Pages not to count | empty | Comma-separated paths, `*` matches anything. Sets `data-exclude`. |
| Script host | `https://privatusstats.com` | Where `pa.js` loads from. Change it only if you host your own copy. |

## Your own events

The script is ready on every page, so your theme can send its own events:

```js
window.privatus.track('Newsletter Signup', { place: 'footer' })
```

See the [JavaScript API](https://privatusanalytics.com/docs/tracker/javascript-api).

## Content Security Policy

If your shop sends a Content Security Policy, allow `https://privatusstats.com` in `script-src` and `connect-src`. When Shopware sets a nonce for the request, the plugin adds it to both script tags.

## Development

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse
```

`bin/package.sh` builds the zip for the Shopware Store in `build/`, without tests or dev files.

## Links

- [Privatus Analytics](https://privatusanalytics.com)
- [Docs](https://privatusanalytics.com/docs)
- [JSON API](https://privatusanalytics.com/docs/api)
- Support: hello@privatusanalytics.com

## License

MIT, see [LICENSE](LICENSE). Copyright Two Phase LLC.
