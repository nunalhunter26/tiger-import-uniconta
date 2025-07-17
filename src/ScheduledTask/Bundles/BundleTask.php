<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\ScheduledTask\Bundles;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class BundleTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'tigeruniconta_bundle';
    }

    public static function getDefaultInterval(): int
    {
        return 60;
    }
}