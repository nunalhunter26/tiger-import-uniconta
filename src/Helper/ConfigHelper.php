<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Helper;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigHelper
{
    CONST CONFIG_BASE = 'TigerImportUniconta.config.%s';

    /** @var SystemConfigService $systemConfigService */
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @param String $key
     * @return mixed
     */
    public function getSystemConfig(String $key): mixed
    {
        return $this->systemConfigService->get(sprintf(self::CONFIG_BASE, $key));
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setSystemConfig(string $key, mixed $value): void
    {
        $this->systemConfigService->set(sprintf(self::CONFIG_BASE, $key), $value);
    }
}