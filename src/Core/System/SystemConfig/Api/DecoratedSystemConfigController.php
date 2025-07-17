<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Core\System\SystemConfig\Api;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SystemConfig\Api\SystemConfigController;
use Shopware\Core\System\SystemConfig\Service\ConfigurationService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SystemConfig\Validation\SystemConfigValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use TigerMedia\General\TigerImportUniconta\Service\WebhooksApiService;
use TigerMedia\General\TigerImportUniconta\TigerImportUniconta;

class DecoratedSystemConfigController extends SystemConfigController
{
    public function __construct(
        ConfigurationService $configurationService,
        SystemConfigService $systemConfig,
        SystemConfigValidator $systemConfigValidator,
        private readonly WebhooksApiService $webhooksApiService,
        private readonly LoggerInterface $logger
    )
    {
        parent::__construct($configurationService, $systemConfig, $systemConfigValidator);
    }

    public function batchSaveConfiguration(Request $request, Context $context): JsonResponse
    {
        $response = parent::batchSaveConfiguration($request, $context);
        $requestArray = $request->request->all();

        if (!empty($requestArray)) {
            $this->processWebhookEntities(current($requestArray));
        }

        return $response;
    }

    /**
     * @param mixed[] $configs
     * @return void
     */
    private function processWebhookEntities(array $configs): void
    {
        if (array_key_exists(TigerImportUniconta::ROOT_CONFIG_PREFIX . 'webhookEntities', $configs) === false) {
            return;
        }

        $webhookEntities = $configs[TigerImportUniconta::ROOT_CONFIG_PREFIX . 'webhookEntities'];

        foreach (array_diff(array_keys(WebhooksApiService::TABLE_IDS), $webhookEntities) as $entityToRemove) {
            try {
                $this->webhooksApiService->deleteWebhook($entityToRemove);
            } catch (Throwable $throwable) {
                $this->logger->critical("Failed to remove entity [$entityToRemove]", ['exception' => $throwable]);
            }
        }

        foreach ($webhookEntities as $webhookEntity) {
            try {
                $this->webhooksApiService->createWebhook($webhookEntity);
            } catch (Throwable $throwable) {
                $this->logger->critical($throwable->getMessage(), ['exception' => $throwable]);
            }
        }
    }
}