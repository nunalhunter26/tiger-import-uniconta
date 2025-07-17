<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Events\Product;

use TigerMedia\General\TigerImportUniconta\Events\AbstractWebhookEvent;

class ProductWebhookDeleteActionEvent extends AbstractWebhookEvent
{

    /**
     * @param mixed[] $webhookRequest
     * @param mixed[] $webhookQuery
     */
    public function __construct(
        array $webhookRequest,
        array $webhookQuery,
    )
    {
        parent::__construct($webhookRequest, $webhookQuery);
    }

    public function getProductNumber(): string
    {
        return $this->getWebhookQuery()['Key'];
    }
}