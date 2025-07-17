<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\ScheduledTask\Bundles;

use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\Service\BundleStockService;

#[AsMessageHandler(
    handles: StockTask::class
)]
class StockTaskHandler extends ScheduledTaskHandler
{
    private BundleStockService $bundleStockService;

    /**
     * @param EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     * @param BundleStockService $bundleStockService
     * @param LoggerInterface|null $exceptionLogger
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        BundleStockService $bundleStockService,
        ?LoggerInterface $exceptionLogger = null)
    {
        $this->bundleStockService = $bundleStockService;
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        if ($this->bundleStockService->shouldRun() === false) {
            return;
        }

        $this->bundleStockService->import(new ImportParameterModel());
    }
}