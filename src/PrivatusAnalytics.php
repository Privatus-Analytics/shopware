<?php declare(strict_types=1);

namespace PrivatusAnalytics;

use Shopware\Core\Framework\Plugin;

/**
 * Adds the Privatus Analytics tracker to the storefront.
 *
 * The plugin has no database tables. Shopware removes its settings on
 * uninstall unless the merchant chooses to keep user data.
 */
class PrivatusAnalytics extends Plugin
{
}
