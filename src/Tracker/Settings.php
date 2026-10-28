<?php declare(strict_types=1);

namespace PrivatusAnalytics\Tracker;

/**
 * The plugin settings for one sales channel, checked and cleaned.
 *
 * fromConfig() returns null when the site ID is missing or not valid, so
 * nothing is printed until the merchant enters a real one.
 */
final class Settings
{
    public const DEFAULT_HOST = 'https://privatusstats.com';

    public const MODULES = ['engage', 'auto', 'vitals', 'clicks', 'ask'];

    private const SITE_ID = '/^pa_[A-Za-z0-9]+$/D';

    private const HOST = '#^https://[A-Za-z0-9.-]+(:[0-9]{1,5})?$#D';

    private const MAX_EXCLUDE = 1000;

    /**
     * @param list<string> $modules
     */
    private function __construct(
        public readonly string $siteId,
        public readonly array $modules,
        public readonly string $exclude,
        public readonly string $host,
        public readonly bool $trackAddToCart,
        public readonly bool $trackCheckout,
        public readonly bool $trackPurchases,
    ) {
    }

    /**
     * @param array<mixed> $config the "PrivatusAnalytics.config" domain, keys without the prefix
     */
    public static function fromConfig(array $config): ?self
    {
        $siteId = \is_string($config['siteId'] ?? null) ? trim($config['siteId']) : '';
        if (preg_match(self::SITE_ID, $siteId) !== 1) {
            return null;
        }

        return new self(
            $siteId,
            self::modules($config['modules'] ?? null),
            self::exclude($config['exclude'] ?? null),
            self::host($config['scriptHost'] ?? null),
            self::flag($config['trackAddToCart'] ?? null),
            self::flag($config['trackCheckout'] ?? null),
            self::flag($config['trackPurchases'] ?? null),
        );
    }

    /**
     * Unknown modules are dropped and the order is fixed. A missing value
     * means all modules, an empty list means none.
     *
     * @return list<string>
     */
    private static function modules(mixed $value): array
    {
        if ($value === null) {
            return self::MODULES;
        }
        if (\is_string($value)) {
            $value = explode(',', $value);
        }
        if (!\is_array($value)) {
            return self::MODULES;
        }
        $chosen = array_map(static fn ($m): string => \is_string($m) ? strtolower(trim($m)) : '', $value);

        return array_values(array_intersect(self::MODULES, $chosen));
    }

    private static function exclude(mixed $value): string
    {
        if (!\is_string($value)) {
            return '';
        }
        $clean = preg_replace('/[\x00-\x1F\x7F]+/', '', $value) ?? '';
        $parts = array_filter(array_map('trim', explode(',', $clean)), static fn (string $p): bool => $p !== '');

        return mb_substr(implode(',', $parts), 0, self::MAX_EXCLUDE);
    }

    private static function host(mixed $value): string
    {
        if (!\is_string($value)) {
            return self::DEFAULT_HOST;
        }
        $host = rtrim(trim($value), '/');

        return preg_match(self::HOST, $host) === 1 ? $host : self::DEFAULT_HOST;
    }

    /**
     * Shop events are on unless the merchant switched them off.
     */
    private static function flag(mixed $value): bool
    {
        return $value === null ? true : (bool) $value;
    }
}
