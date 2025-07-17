<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Events;

use Symfony\Contracts\EventDispatcher\Event;

class AbstractWebhookEvent extends Event
{
    /**
     * @param mixed[] $webhookRequest
     * @param mixed[] $webhookQuery
     */
    public function __construct(
        private readonly array $webhookRequest,
        private readonly array $webhookQuery
    )
    {
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