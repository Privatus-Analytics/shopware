<?php declare(strict_types=1);

namespace PrivatusAnalytics\Tracker;

/**
 * One event sent with window.privatus.track() when the page loads.
 */
final class ShopEvent
{
    public const CHECKOUT_STARTED = 'Checkout Started';

    public const PURCHASE = 'Purchase';

    /**
     * @param array<string, string|float> $props
     */
    private function __construct(
        public readonly string $name,
        public readonly array $props,
    ) {
    }

    public static function checkoutStarted(): self
    {
        return new self(self::CHECKOUT_STARTED, []);
    }

    /**
     * Only the order total and its currency. No order number, customer or
     * product data. A total that is not a finite number is left out, and so
     * is a currency that is not a three-letter code.
     */
    public static function purchase(float $total, ?string $currency): self
    {
        $props = [];
        if (is_finite($total)) {
            $props['revenue'] = round($total, 2);
        }
        $code = strtoupper(trim((string) $currency));
        if (preg_match('/^[A-Z]{3}$/', $code) === 1) {
            $props['currency'] = $code;
        }

        return new self(self::PURCHASE, $props);
    }

    /**
     * @return array{name: string, props: array<string, string|float>|object}
     */
    public function toArray(): array
    {
        // An empty object, not an empty list, in the JSON.
        return ['name' => $this->name, 'props' => $this->props === [] ? new \stdClass() : $this->props];
    }
}
