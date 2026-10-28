<?php declare(strict_types=1);

namespace PrivatusAnalytics\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PrivatusAnalytics\Tracker\ShopEvent;

final class ShopEventTest extends TestCase
{
    public function testPurchaseHasOnlyTotalAndCurrency(): void
    {
        $e = ShopEvent::purchase(49.999, ' usd ');
        self::assertSame('Purchase', $e->name);
        self::assertSame(['revenue' => 50.0, 'currency' => 'USD'], $e->props);
    }

    public function testBadValuesAreLeftOut(): void
    {
        self::assertSame([], ShopEvent::purchase(\NAN, 'EURO')->props);
        self::assertSame(['revenue' => 10.0], ShopEvent::purchase(10, null)->props);
        self::assertSame(['revenue' => 10.0], ShopEvent::purchase(10, '"><')->props);
    }

    public function testCheckoutStartedHasNoProps(): void
    {
        $e = ShopEvent::checkoutStarted();
        self::assertSame('Checkout Started', $e->name);
        self::assertEquals(new \stdClass(), $e->toArray()['props']);
    }
}
