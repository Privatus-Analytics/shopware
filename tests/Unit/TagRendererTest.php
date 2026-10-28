<?php declare(strict_types=1);

namespace PrivatusAnalytics\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PrivatusAnalytics\Tracker\Settings;
use PrivatusAnalytics\Tracker\ShopEvent;
use PrivatusAnalytics\Tracker\TagRenderer;

final class TagRendererTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     */
    private static function settings(array $config = []): Settings
    {
        $s = Settings::fromConfig($config + ['siteId' => 'pa_7Q2K9XH3AB']);
        self::assertNotNull($s);

        return $s;
    }

    public function testDefaultTag(): void
    {
        $html = TagRenderer::render(self::settings());
        self::assertStringContainsString(
            '<script defer src="https://privatusstats.com/js/pa.js" data-site="pa_7Q2K9XH3AB" data-modules="engage,auto,vitals,clicks,ask"></script>',
            $html
        );
        self::assertStringNotContainsString('data-api', $html);
        self::assertStringNotContainsString('data-exclude', $html);
        self::assertStringContainsString("window.privatus=window.privatus||{q:[]};", $html);
        self::assertStringContainsString('"events":[]', $html);
    }

    public function testQueueStubComesBeforeTheTag(): void
    {
        $html = TagRenderer::render(self::settings());
        self::assertLessThan(strpos($html, 'pa.js'), strpos($html, 'window.privatus.q.push'));
    }

    public function testCustomHostAddsDataApi(): void
    {
        $html = TagRenderer::render(self::settings(['scriptHost' => 'https://stats.example.com']));
        self::assertStringContainsString('src="https://stats.example.com/js/pa.js"', $html);
        self::assertStringContainsString('data-api="https://stats.example.com"', $html);
    }

    public function testExcludeIsEscaped(): void
    {
        $html = TagRenderer::render(self::settings(['exclude' => '/a"b,<x>&']));
        self::assertStringContainsString('data-exclude="/a&quot;b,&lt;x&gt;&amp;"', $html);
    }

    public function testEmptyModules(): void
    {
        $html = TagRenderer::render(self::settings(['modules' => []]));
        self::assertStringContainsString('data-modules=""', $html);
    }

    public function testAddToCartListener(): void
    {
        $on = TagRenderer::render(self::settings());
        // JSON_HEX_QUOT writes the inner quotes as ".
        $q = '\\' . 'u0022';
        self::assertStringContainsString('"cart":"form[data-add-to-cart], form[action*=' . $q . '/checkout/line-item/add' . $q . ']"', $on);

        $off = TagRenderer::render(self::settings(['trackAddToCart' => false]));
        self::assertStringContainsString('"cart":null', $off);
    }

    public function testEvents(): void
    {
        $html = TagRenderer::render(self::settings(), [
            ShopEvent::checkoutStarted(),
            ShopEvent::purchase(129.9, 'eur'),
        ]);
        self::assertStringContainsString(
            '"events":[{"name":"Checkout Started","props":{}},{"name":"Purchase","props":{"revenue":129.9,"currency":"EUR"}}]',
            $html
        );
    }

    public function testNoRawClosingTagInInlineScript(): void
    {
        // The only "</script>" strings are the two real closing tags.
        $html = TagRenderer::render(self::settings(['exclude' => '</script><script>alert(1)</script>']));
        self::assertSame(2, substr_count(strtolower($html), '</script>'));
    }

    public function testNonce(): void
    {
        $html = TagRenderer::render(self::settings(), [], 'abc"123');
        self::assertSame(2, substr_count($html, 'nonce="abc&quot;123"'));
        self::assertStringNotContainsString('nonce', TagRenderer::render(self::settings(), [], ''));
    }
}
