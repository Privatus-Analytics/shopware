<?php declare(strict_types=1);

namespace PrivatusAnalytics\Tracker;

/**
 * Builds the HTML for the page head: a small queue so track() calls work
 * before pa.js loads, the shop events for this page, and the pa.js tag.
 *
 * Every value is escaped here, so the template prints the result as is.
 */
final class TagRenderer
{
    private const JSON_FLAGS = \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT
        | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR;

    /**
     * The add to cart forms of the Shopware storefront. Listening in the
     * capture phase sees the submit before the storefront sends it by Ajax.
     */
    private const ADD_TO_CART_FORMS = 'form[data-add-to-cart], form[action*="/checkout/line-item/add"]';

    /**
     * @param list<ShopEvent> $events
     */
    public static function render(Settings $settings, array $events = [], ?string $nonce = null): string
    {
        $config = [
            'events' => array_map(static fn (ShopEvent $e): array => $e->toArray(), $events),
            'cart' => $settings->trackAddToCart ? self::ADD_TO_CART_FORMS : null,
        ];
        $nonceAttr = $nonce !== null && $nonce !== '' ? ' nonce="' . self::attr($nonce) . '"' : '';

        $inline = 'window.privatus=window.privatus||{q:[]};'
            . "['track','pageview','props','optOut','optIn','ready'].forEach(function(m){"
            . 'window.privatus[m]=window.privatus[m]||function(){'
            . 'window.privatus.q.push([m,Array.prototype.slice.call(arguments)]);};});'
            // pa.js swaps window.privatus for the real object when it loads,
            // so look it up at call time instead of keeping the stub.
            . '(function(c){'
            . 'c.events.forEach(function(e){window.privatus.track(e.name,e.props);});'
            . 'if(c.cart){document.addEventListener("submit",function(ev){var f=ev.target;'
            . 'if(f&&f.matches&&f.matches(c.cart)){window.privatus.track("Add to Cart");}},true);}'
            . '})(' . json_encode($config, self::JSON_FLAGS) . ');';

        $attrs = [
            'src' => $settings->host . '/js/pa.js',
            'data-site' => $settings->siteId,
            'data-modules' => implode(',', $settings->modules),
        ];
        if ($settings->exclude !== '') {
            $attrs['data-exclude'] = $settings->exclude;
        }
        if ($settings->host !== Settings::DEFAULT_HOST) {
            $attrs['data-api'] = $settings->host;
        }

        $tag = '<script defer';
        foreach ($attrs as $name => $value) {
            $tag .= ' ' . $name . '="' . self::attr($value) . '"';
        }

        return '<script' . $nonceAttr . '>' . $inline . '</script>' . "\n" . $tag . $nonceAttr . '></script>';
    }

    private static function attr(string $value): string
    {
        return htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');
    }
}
