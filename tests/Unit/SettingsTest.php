<?php declare(strict_types=1);

namespace PrivatusAnalytics\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PrivatusAnalytics\Tracker\Settings;

final class SettingsTest extends TestCase
{
    public function testNoSiteIdMeansNothing(): void
    {
        self::assertNull(Settings::fromConfig([]));
        self::assertNull(Settings::fromConfig(['siteId' => '']));
        self::assertNull(Settings::fromConfig(['siteId' => null]));
    }

    /**
     * @return iterable<array{mixed}>
     */
    public static function badSiteIds(): iterable
    {
        yield ['ABC123'];
        yield ['pa_'];
        yield ['pa_abc"onload="x'];
        yield ['pa_abc def'];
        yield ['pa_abc-def'];
        yield ["pa_ab\ncd"];
        yield [123];
        yield [['pa_abc']];
    }

    #[DataProvider('badSiteIds')]
    public function testRejectsBadSiteIds(mixed $siteId): void
    {
        self::assertNull(Settings::fromConfig(['siteId' => $siteId]));
    }

    public function testDefaults(): void
    {
        $s = Settings::fromConfig(['siteId' => ' pa_7Q2K9XH3AB ']);
        self::assertNotNull($s);
        self::assertSame('pa_7Q2K9XH3AB', $s->siteId);
        self::assertSame(['engage', 'auto', 'vitals', 'clicks', 'ask'], $s->modules);
        self::assertSame('', $s->exclude);
        self::assertSame('https://privatusstats.com', $s->host);
        self::assertTrue($s->trackAddToCart);
        self::assertTrue($s->trackCheckout);
        self::assertTrue($s->trackPurchases);
    }

    public function testModulesAreFilteredAndOrdered(): void
    {
        $s = Settings::fromConfig(['siteId' => 'pa_A1', 'modules' => ['ask', 'evil', 'ENGAGE', 7, 'clicks']]);
        self::assertSame(['engage', 'clicks', 'ask'], $s?->modules);

        $s = Settings::fromConfig(['siteId' => 'pa_A1', 'modules' => []]);
        self::assertSame([], $s?->modules);

        $s = Settings::fromConfig(['siteId' => 'pa_A1', 'modules' => 'vitals, engage']);
        self::assertSame(['engage', 'vitals'], $s?->modules);
    }

    public function testExcludeIsCleaned(): void
    {
        $s = Settings::fromConfig(['siteId' => 'pa_A1', 'exclude' => " /account/* , ,/wishlist\x00\n"]);
        self::assertSame('/account/*,/wishlist', $s?->exclude);

        $s = Settings::fromConfig(['siteId' => 'pa_A1', 'exclude' => str_repeat('a', 5000)]);
        self::assertSame(1000, mb_strlen((string) $s?->exclude));
    }

    /**
     * @return iterable<array{mixed, string}>
     */
    public static function hosts(): iterable
    {
        yield ['https://stats.example.com/', 'https://stats.example.com'];
        yield ['https://stats.example.com:8443', 'https://stats.example.com:8443'];
        yield ['http://stats.example.com', 'https://privatusstats.com'];
        yield ['javascript:alert(1)', 'https://privatusstats.com'];
        yield ['https://evil.com/"><script>', 'https://privatusstats.com'];
        yield ['https://stats.example.com/path', 'https://privatusstats.com'];
        yield ['', 'https://privatusstats.com'];
        yield [null, 'https://privatusstats.com'];
    }

    #[DataProvider('hosts')]
    public function testHost(mixed $value, string $expected): void
    {
        self::assertSame($expected, Settings::fromConfig(['siteId' => 'pa_A1', 'scriptHost' => $value])?->host);
    }

    public function testShopEventsCanBeSwitchedOff(): void
    {
        $s = Settings::fromConfig([
            'siteId' => 'pa_A1',
            'trackAddToCart' => false,
            'trackCheckout' => false,
            'trackPurchases' => false,
        ]);
        self::assertNotNull($s);
        self::assertFalse($s->trackAddToCart);
        self::assertFalse($s->trackCheckout);
        self::assertFalse($s->trackPurchases);
    }
}
