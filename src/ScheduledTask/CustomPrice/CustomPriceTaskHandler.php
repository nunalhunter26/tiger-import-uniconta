<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\ScheduledTask\CustomPrice;

use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\Service\CustomPriceService;

#[AsMessageHandler(
    handles: CustomPriceTask::class
)]
class CustomPriceTaskHandler extends ScheduledTaskHandler
{
    private CustomPriceService $customPriceService;

    /**
     * @param EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     * @param CustomPriceService $customPriceService
     * @param LoggerInterface|null $exceptionLogger
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        CustomPriceService $customPriceService,
        ?LoggerInterface $exceptionLogger = null)
    {
        $this->customPriceService = $customPriceService;
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function run(): void
    {
        if ($this->customPriceService->shouldRun() === false) {
            return;
        }

        $this->customPriceService->import(new ImportParameterModel());
    }
}