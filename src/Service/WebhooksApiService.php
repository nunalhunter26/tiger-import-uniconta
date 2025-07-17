<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Service;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Throwable;
use TigerMedia\Base\HttpClient\TigerClient;
use TigerMedia\General\TigerImportUniconta\TigerImportUniconta;

class WebhooksApiService
{

    private const WEBHOOK_ENDPOINT = 'Crud/TableChangeEvent';
    const TABLE_IDS = [
        'InvItem'          => 23,
        'Debtor'           => 50,
        'Contact'          => 60,
        'InvPriceListLine' => 0, // need to verify table ID
        'InvItemStorage'   => 134
    ];

    public function __construct(
        private readonly TigerClient $client,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Create a webhook for a specific entity.
     * @throws Throwable
     */
    public function createWebhook(string $entity): void
    {
        $tableId = self::TABLE_IDS[$entity] ?? null;

        if ($tableId === null) {
            throw new Exception('Unsupported entity: ' . $entity);
        }

        $this->deleteWebhook($entity, true);
        $webhookData = [
            'tableId' => $tableId,
            'url'     => $this->getValidatedAppUrl($entity),
            'UsePost' => true,
        ];

        try {
            $this->client->post(self::WEBHOOK_ENDPOINT, ['json' => $webhookData]);
        } catch (Throwable $e) {
            $this->logError('create', $e, self::WEBHOOK_ENDPOINT, $webhookData);
            throw $e;
        }

        $this->systemConfigService->set(TigerImportUniconta::ROOT_CONFIG_PREFIX . 'webhook' . $entity, true);
    }

    /**
     * Delete a webhook for a specific entity.
     *
     * @throws GuzzleException
     */
    public function deleteWebhook(string $entity, bool $isFlow = false): void
    {
        $url = $this->getValidatedAppUrl($entity);
        $endpoint = self::WEBHOOK_ENDPOINT . '?filter.Url=' . urlencode($url) . '&limit=1';

        try {
            $this->client->delete($endpoint);
        } catch (GuzzleException $e) {
            $this->logError('delete', $e, $endpoint);
            throw $e;
        }

        if ($isFlow === false) {
            $this->systemConfigService->set(TigerImportUniconta::ROOT_CONFIG_PREFIX . 'webhook' . $entity, false);
        }
    }

    /**
     * @return mixed[]
     */
    public function getWebhookStatus(): array
    {
        $status = [];

        foreach (self::TABLE_IDS as $entity => $id) {
            $status[$entity] = $this->systemConfigService->get(TigerImportUniconta::ROOT_CONFIG_PREFIX . 'webhook' . $entity);
        }

        return $status;
    }

    private function getValidatedAppUrl(string $entity): string
    {
        if (empty(EnvironmentHelper::getVariable('APP_URL'))) {
            throw new \RuntimeException('APP_URL environment variable is not set');
        }

        $appUrl = rtrim(EnvironmentHelper::getVariable('APP_URL'), '/');

        if (!filter_var($appUrl, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('APP_URL is not a valid URL');
        }

        return $appUrl . '/tiger-import-uniconta/webhook/' . ltrim($entity, '/');
    }

    /**
     * @param string $action
     * @param Throwable $e
     * @param string $endpoint
     * @param array<string, mixed> $data
     */
    private function logError(string $action, Throwable $e, string $endpoint, array $data = []): void
    {
        $context = [
            'error' => $e->getMessage(),
            'endpoint' => $endpoint,
        ];

        if (!empty($data)) {
            $context['data'] = $data;
        }

        $this->logger->error("Failed to {$action} webhook", $context);
    }
}