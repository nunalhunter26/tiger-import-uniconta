<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\ScheduledTask\Product;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class StockPricesTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'tigeruniconta_product.stock.prices';
    }

    public static function getDefaultInterval(): int
    {
        return 60;
    }
}