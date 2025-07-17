<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\ScheduledTask\Product;

use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\Service\ProductService;

#[AsMessageHandler(
    handles: ProductTask::class
)]
class ProductTaskHandler extends ScheduledTaskHandler
{
    private ProductService $productService;

    /**
     * @param EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     * @param ProductService $productService
     * @param LoggerInterface|null $exceptionLogger
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        ProductService $productService,
        ?LoggerInterface $exceptionLogger = null)
    {
        $this->productService = $productService;
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        if ($this->productService->shouldRun() === false) {
            return;
        }

        $this->productService->import(new ImportParameterModel());
    }
}