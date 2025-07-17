<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use TigerMedia\General\TigerImportUniconta\Helper\ConfigHelper;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\TigerImportUniconta;

abstract class AbstractImport
{
    public RestApi $restApi;
    private ConfigHelper $configHelper;
    private bool $isPartialImport = false;

    public function __construct(
        RestApi $restApi,
        ConfigHelper $configHelper
    )
    {
        $this->restApi = $restApi;
        $this->configHelper = $configHelper;
    }

    abstract public function import(ImportParameterModel $params): void;
    abstract public function getServiceTag(): string;
    abstract public function webhookImport(ImportParameterModel $params): void;

    protected function saveLastRun(): void
    {
        $this->configHelper->setSystemConfig($this->getServiceTag() . TigerImportUniconta::CONFIG_TASK_LAST_RUN_SUFFIX, (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM));
    }

    public function getTaskLastRun(): ?string
    {
        return $this->configHelper->getSystemConfig($this->getServiceTag() . TigerImportUniconta::CONFIG_TASK_LAST_RUN_SUFFIX);
    }

    public function getTaskIsEnabled(): ?bool
    {
        return $this->configHelper->getSystemConfig($this->getServiceTag() . TigerImportUniconta::CONFIG_TASK_IS_ENABLED_SUFFIX);
    }

    public function getTaskIsPartial(): ?bool
    {
        return $this->configHelper->getSystemConfig($this->getServiceTag() . TigerImportUniconta::CONFIG_TASK_IS_PARTIAL);
    }

    public function getTaskInterval(): ?int
    {
        return $this->configHelper->getSystemConfig($this->getServiceTag() . TigerImportUniconta::CONFIG_TASK_INTERVAL_SUFFIX);

    }

    public function getImportBlockStartTime(): ?string
    {
        return $this->configHelper->getSystemConfig(TigerImportUniconta::CONFIG_IMPORT_BLOCK_START_TIME);
    }

    public function getImportBlockEndTime(): ?string
    {
        return $this->configHelper->getSystemConfig(TigerImportUniconta::CONFIG_IMPORT_BLOCK_END_TIME);
    }

    public function importPartial(): void
    {
        try {
            $lastExecution = (new DateTimeImmutable($this->getTaskLastRun() ?? '2000-01-01'))->format('Y-m-d\TH:i:s');
        } catch (Exception $exception) {
            $lastExecution = (new DateTimeImmutable('2000-01-01'))->format('Y-m-d\TH:i:s');
            $this->restApi->logger->info('Last Execution value failed to retrieve, using default 2000-01-01 instead.', ['exception' => $exception]);
        }

        $params = (new ImportParameterModel());
        $params->setQuery(['filter.UpdatedAt' => $lastExecution]);
        $this->import($params);
    }

    /**
     * @throws Exception
     *  Determines whether the import should run, based on:
     *  1. Whether it's currently within the configured block time window
     *  2. Whether enough time has passed since the last run
     *  3. Whether the task is enabled
    */
    public function shouldRun(): bool
    {
        $now = new DateTimeImmutable('now');

        $startTime = $this->getImportBlockStartTime();
        $endTime = $this->getImportBlockEndTime();

        if (!empty($startTime) && !empty($endTime)) {
            try {
                $startDate = new DateTimeImmutable($startTime);
                $endDate = new DateTimeImmutable($endTime);

                if ($now >= $startDate && $now <= $endDate) {
                    return false;
                }
            } catch (\Exception $e) {
                $this->restApi->logger->error('Import Block Start Time or End Time is not valid', ['exception' => $e]);
                return false;
            }
        }
        $lastRun = new DateTimeImmutable($this->getTaskLastRun() ?? '2000-01-01');
        $shouldRun = $now->getTimestamp() - $lastRun->getTimestamp() >= $this->getTaskInterval();

        return $this->getTaskIsEnabled() && $shouldRun;
    }

    public function isPartialImport(): bool
    {
        return $this->isPartialImport;
    }

    public function setIsPartialImport(bool $isPartialImport): static
    {
        $this->isPartialImport = $isPartialImport;
        return $this;
    }
}