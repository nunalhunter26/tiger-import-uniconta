<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\ScheduledTask\Bundles;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class StockTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'tigeruniconta_bundle.stock';
    }

    public static function getDefaultInterval(): int
    {
        return 60;
    }
}