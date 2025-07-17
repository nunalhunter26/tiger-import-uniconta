<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Model;

use DateTimeImmutable;

class TaskModel
{
    private int $interval;
    private DateTimeImmutable $lastRun;
    private string $serviceKey;
    private ?bool $isPartialImport;

    public function __construct(int $interval, DateTimeImmutable $lastRun, string $serviceKey, ?bool $isPartialImport)
    {
        $this
            ->setInterval($interval)
            ->setLastRun($lastRun)
            ->setIsPartialImport($isPartialImport)
            ->setServiceKey($serviceKey);
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function setInterval(int $interval): static
    {
        $this->interval = $interval;
        return $this;
    }

    public function getLastRun(): DateTimeImmutable
    {
        return $this->lastRun;
    }

    public function setLastRun(DateTimeImmutable $lastRun): static
    {
        $this->lastRun = $lastRun;
        return $this;
    }

    public function getServiceKey(): string
    {
        return $this->serviceKey;
    }

    public function setServiceKey(string $serviceKey): static
    {
        $this->serviceKey = $serviceKey;
        return $this;
    }

    public function isPartialImport(): ?bool
    {
        return $this->isPartialImport;
    }

    public function setIsPartialImport(?bool $isPartialImport): static
    {
        $this->isPartialImport = $isPartialImport;
        return $this;
    }
}