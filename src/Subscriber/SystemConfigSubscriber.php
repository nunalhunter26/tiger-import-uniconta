<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Subscriber;

use Shopware\Core\System\SystemConfig\Event\SystemConfigDomainLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TigerMedia\General\TigerImportUniconta\Service\WebhooksApiService;
use TigerMedia\General\TigerImportUniconta\TigerImportUniconta;

class SystemConfigSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private readonly WebhooksApiService $webhooksApiService
    )
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): iterable
    {
        return [
            SystemConfigDomainLoadedEvent::class => 'onSystemConfigDomainLoaded'
        ];
    }

    public function onSystemConfigDomainLoaded(SystemConfigDomainLoadedEvent $event): void
    {
        $webhookEntities = [];

        foreach ($this->webhooksApiService->getWebhookStatus() as $entity => $status) {
            if ($status !== true) {
                continue;
            }

            $webhookEntities[] = $entity;
        }

        $event->setConfig(array_merge($event->getConfig(), [
            TigerImportUniconta::ROOT_CONFIG_PREFIX . 'webhookEntities' => $webhookEntities
        ]));
    }
}