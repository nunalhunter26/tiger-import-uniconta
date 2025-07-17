<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\MessageQueue;

use Shopware\Core\Framework\MessageQueue\LowPriorityMessageInterface;

class WebhookDataMessage implements LowPriorityMessageInterface
{
    /**
     * @param string $entity
     * @param mixed[] $webhookRequest
     * @param mixed[] $webhookQuery
     */
    public function __construct(
        private readonly string $entity,
        private readonly array $webhookRequest,
        private readonly array $webhookQuery
    )
    {
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * @return mixed[]
     */
    public function getWebhookRequest(): array
    {
        return $this->webhookRequest;
    }

    /**
     * @return mixed[]
     */
    public function getWebhookQuery(): array
    {
        return $this->webhookQuery;
    }
}