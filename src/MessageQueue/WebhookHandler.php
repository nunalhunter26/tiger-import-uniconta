<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\MessageQueue;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\Service\AbstractImport;

#[AsMessageHandler]
class WebhookHandler
{
    const UNICONTA_TABLE_MAPPING = [
        'InvItem'         => 'product',
        'Debtor'          => 'customer',
        'Contact'         => 'customer',
        'InvItemStorage'  => 'productPriceStock',
        'DebtorPriceList' => '' //to-do
    ];

    /**
     * @param AbstractImport[] $importServices
     */
    public function __construct(
        private readonly iterable $importServices,
        private readonly LoggerInterface $logger
    )
    {
    }

    public function __invoke(WebhookDataMessage $message): void
    {
        $action = $message->getWebhookQuery()['Action'] ?? null;

        if ($action === null) {
            $this->logger->critical('Webhook [' . $message->getEntity() . '] action is missing', ['query' => $message->getWebhookQuery()]);
            return;
        }

        if (empty($message->getWebhookQuery()['Key'] ?? null)) {
            $this->logger->critical('Webhook [' . $message->getEntity() . '] Key field is missing from the query', ['query' => $message->getWebhookQuery()]);
            return;
        }

        $entityKey = self::UNICONTA_TABLE_MAPPING[$message->getEntity()] ?? null;

        if ($entityKey === null) {
            $this->logger->critical('No mapping found for entity ' . $message->getEntity(), ['query' => $message->getWebhookQuery()]);
            return;
        }

        foreach ($this->importServices as $importService) {
            if ($importService->getServiceTag() !== $entityKey) {
                continue;
            }

            $this->logger->info('Importing ' . $message->getEntity() . ' via webhook', ['query' => $message->getWebhookQuery()]);
            $importService->webhookImport(
                (new ImportParameterModel())
                    ->setIsWebhook(true)
                    ->setWebhookRequest($message->getWebhookRequest())
                    ->setWebhookQuery($message->getWebhookQuery())
            );

            return;
        }

        $this->logger->critical('No import service found for entity ' . $message->getEntity(), ['query' => $message->getWebhookQuery()]);
    }
}