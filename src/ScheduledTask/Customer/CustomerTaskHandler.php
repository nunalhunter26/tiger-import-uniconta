<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\ScheduledTask\Customer;

use Exception;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskCollection;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\Service\CustomerService;

#[AsMessageHandler(
    handles: CustomerTask::class
)]
class CustomerTaskHandler extends ScheduledTaskHandler
{
    private CustomerService $customerService;

    /**
     * @param EntityRepository<ScheduledTaskCollection> $scheduledTaskRepository
     * @param CustomerService $customerService
     * @param LoggerInterface|null $exceptionLogger
     */
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        CustomerService $customerService,
        ?LoggerInterface $exceptionLogger = null)
    {
        $this->customerService = $customerService;
        parent::__construct($scheduledTaskRepository, $exceptionLogger);
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        if ($this->customerService->shouldRun() === false) {
            return;
        }

        $this->customerService->import(new ImportParameterModel());
    }
}