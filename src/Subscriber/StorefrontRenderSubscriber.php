<?php declare(strict_types=1);

namespace PrivatusAnalytics\Subscriber;

use PrivatusAnalytics\Tracker\Settings;
use PrivatusAnalytics\Tracker\ShopEvent;
use PrivatusAnalytics\Tracker\TagRenderer;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Gives the storefront template the Privatus Analytics tag for this sales
 * channel, as the "privatusAnalytics" variable.
 *
 * On the order review page it adds "Checkout Started" once per cart. On the
 * thank-you page it adds "Purchase" with the order total once per order, so a
 * reload does not count the sale twice. Both are remembered in the shop's own
 * session on the server. Nothing is stored in the shopper's browser.
 */
class StorefrontRenderSubscriber implements EventSubscriberInterface
{
    public const VARIABLE = 'privatusAnalytics';

    public const SESSION_KEY = 'privatus_analytics_sent';

    private const SESSION_LIMIT = 20;

    public function __construct(private readonly SystemConfigService $systemConfig)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [StorefrontRenderEvent::class => 'onRender'];
    }

    public function onRender(StorefrontRenderEvent $event): void
    {
        $context = $event->getSalesChannelContext();
        $config = $this->systemConfig->get('PrivatusAnalytics.config', $context->getSalesChannelId());
        $settings = Settings::fromConfig(\is_array($config) ? $config : []);
        if ($settings === null) {
            return;
        }

        $request = $event->getRequest();
        $page = $event->getParameters()['page'] ?? null;
        $events = [];

        if ($settings->trackCheckout && $page instanceof CheckoutConfirmPage
            && $this->firstTime($request, 'checkout:' . hash('sha256', $context->getToken()))) {
            $events[] = ShopEvent::checkoutStarted();
        }

        if ($settings->trackPurchases && $page instanceof CheckoutFinishPage
            && $this->firstTime($request, 'order:' . $page->getOrder()->getId())) {
            $order = $page->getOrder();
            $currency = $order->getCurrency()?->getIsoCode() ?? $context->getCurrency()->getIsoCode();
            $events[] = ShopEvent::purchase($order->getAmountTotal(), $currency);
        }

        $nonce = $request->attributes->get(PlatformRequest::ATTRIBUTE_CSP_NONCE);
        $event->setParameter(self::VARIABLE, TagRenderer::render($settings, $events, \is_string($nonce) ? $nonce : null));
    }

    /**
     * True the first time a key is seen in this session. Without a session it
     * is always true.
     */
    private function firstTime(Request $request, string $key): bool
    {
        if (!$request->hasSession()) {
            return true;
        }
        $session = $request->getSession();
        $sent = $session->get(self::SESSION_KEY, []);
        $sent = \is_array($sent) ? $sent : [];
        if (\in_array($key, $sent, true)) {
            return false;
        }
        $sent[] = $key;
        $session->set(self::SESSION_KEY, \array_slice($sent, -self::SESSION_LIMIT));

        return true;
    }
}
