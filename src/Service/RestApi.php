<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Service;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use TigerMedia\Base\HttpClient\Authentication\BasicAuthentication;
use TigerMedia\Base\HttpClient\TigerClient;

class RestApi
{
    private TigerClient $tigerClient;
    public LoggerInterface $logger;

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        TigerClient $tigerClient,
        LoggerInterface $logger
    )
    {
        $this->tigerClient = $tigerClient;
        $this->logger = $logger;
    }

    /**
     * @param string $service
     * @param array<mixed> $query
     * @return mixed
     */
    public function get(string $service, array $query = []): mixed
    {
        try {
            $data = $this->tigerClient->get($service, ['query' => $query]);
            $contentType = $data->getHeader('Content-Type')[0];
            return str_contains($contentType, 'application/json') === true ? json_decode($data->getBody()->getContents()) : $data->getBody()->getContents();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        } catch (GuzzleException $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }

        return '';
    }

    /**
     * @param string $service
     * @param array<mixed> $query
     * @return mixed
     */
    public function getStream(string $service, array $query = []): mixed
    {
        $options = !empty($query) ? ['query' => $query] : [];

        try {
            return $this->tigerClient->getJsonStream($service, $options);
        } catch (GuzzleException|Exception $exception) {
            $this->logger->error($exception->getMessage(), $exception->getTrace());
        }

        return [];
    }

    public function getPlainClient(): TigerClient
    {
        $newClient = new TigerClient([
            'base_uri' => $this->systemConfigService->getString('TigerImportUniconta.config.URL')
        ]);

        $newClient->setAuthentication(new BasicAuthentication(
            $this->systemConfigService->get('TigerImportUniconta.config.Username'),
            $this->systemConfigService->get('TigerImportUniconta.config.Password')));

        return $newClient;
    }
}