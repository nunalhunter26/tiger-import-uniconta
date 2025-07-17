<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use TigerMedia\General\TigerImportUniconta\MessageQueue\WebhookDataMessage;
use TigerMedia\General\TigerImportUniconta\Service\WebhooksApiService;
use Symfony\Component\Messenger\MessageBusInterface;
use TigerMedia\General\TigerImportUniconta\TigerImportUniconta;

#[Route(defaults: ['_routeScope' => ['api']])]
class WebhooksController extends AbstractController
{
    /**
     * @param MessageBusInterface $messageBus
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route(
        path: '/tiger-import-uniconta/webhook/{entity}',
        name: 'uniconta_webhook_catch',
        defaults: ['XmlHttpRequest' => true, '_noStore' => true, '_routeScope' => ['storefront']],
        methods: ['POST']
    )]
    public function catchWebhook(string $entity, Request $request): JsonResponse
    {
        $this->logger->info('Webhook triggered.', ['entity' => $entity, 'query' => $request->query->all()]);
        $this->messageBus->dispatch(new WebhookDataMessage($entity, $request->request->all(), $request->query->all()));
        return new JsonResponse(['success' => true]);
    }
}
