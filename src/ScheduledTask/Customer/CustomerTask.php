<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\ScheduledTask\Customer;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CustomerTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'tigeruniconta_customer';
    }

    public static function getDefaultInterval(): int
    {
        return 60;
    }
}