<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\ScheduledTask\Product;

use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\Service\ProductPriceStockService;

#[AsMessageHandler(
    handles: StockPricesTask::class
)]
class StockPricesTaskHandler extends ScheduledTaskHandler
{
    private ProductPriceStockService $productPriceService;

    /**
     * @param EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     * @param ProductPriceStockService $productPriceStockService
     * @param LoggerInterface|null $exceptionLogger
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        ProductPriceStockService $productPriceStockService,
        ?LoggerInterface $exceptionLogger = null)
    {
        $this->productPriceService = $productPriceStockService;
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        if ($this->productPriceService->shouldRun() === false) {
            return;
        }

        $this->productPriceService->import(new ImportParameterModel());
    }
}