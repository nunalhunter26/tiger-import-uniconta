<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use TigerMedia\Base\HttpClient\Authentication\BasicAuthentication;
use TigerMedia\Base\HttpClient\Events\HttpClientConfigEvent;
use TigerMedia\General\TigerImportUniconta\Helper\ConfigHelper;

class HttpClientSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ConfigHelper $configHelper
    )
    {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): iterable
    {
        return [
            HttpClientConfigEvent::class => 'onHttpClientConfig',
        ];
    }

    public function onHttpClientConfig(HttpClientConfigEvent $event): void
    {
        $event->setConfig(array_merge_recursive($event->getConfig(), [
            'base_uri' => $this->configHelper->getSystemConfig('URL')
        ], (new BasicAuthentication(
            $this->configHelper->getSystemConfig('Username'),
            $this->configHelper->getSystemConfig('Password')))->getHeader()
        ));
    }
}