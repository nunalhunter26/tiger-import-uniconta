<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\ScheduledTask\CustomPrice;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CustomPriceTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'tigeruniconta_custom.price';
    }

    public static function getDefaultInterval(): int
    {
        return 60;
    }
}