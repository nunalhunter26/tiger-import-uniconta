<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Model;

class ImportParameterModel
{
    private ?string $productNumber = null;
    private ?string $customerNumber = null;
    private bool $isPartialImport = false;
    private ?string $priceList = null;
    private bool $isWebhook = false;

    /** @var mixed[]|null $webhookRequest */
    private ?array $webhookRequest = null;

    /** @var mixed[]|null $webhookQuery */
    private ?array $webhookQuery = null;

    /** @var mixed[] $query */
    private array $query = [];

    public function getProductNumber(): ?string
    {
        return $this->productNumber;
    }

    public function setProductNumber(?string $productNumber): static
    {
        $this->productNumber = $productNumber;
        return $this;
    }

    public function getCustomerNumber(): ?string
    {
        return $this->customerNumber;
    }

    public function setCustomerNumber(?string $customerNumber): static
    {
        $this->customerNumber = $customerNumber;
        return $this;
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

    public function getPriceList(): ?string
    {
        return $this->priceList;
    }

    public function setPriceList(?string $priceList): static
    {
        $this->priceList = $priceList;
        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @param mixed[] $query
     * @return $this
     */
    public function setQuery(array $query): static
    {
        $this->query = $query;
        return $this;
    }

    public function isWebhook(): bool
    {
        return $this->isWebhook;
    }

    public function setIsWebhook(bool $isWebhook): static
    {
        $this->isWebhook = $isWebhook;
        return $this;
    }

    /**
     * @return mixed[]|null
     */
    public function getWebhookRequest(): ?array
    {
        return $this->webhookRequest;
    }

    /**
     * @param mixed[]|null $webhookRequest
     * @return $this
     */
    public function setWebhookRequest(?array $webhookRequest): static
    {
        $this->webhookRequest = $webhookRequest;
        return $this;
    }

    /**
     * @return mixed[]|null
     */
    public function getWebhookQuery(): ?array
    {
        return $this->webhookQuery;
    }

    /**
     * @param mixed[]|null $webhookQuery
     * @return $this
     */
    public function setWebhookQuery(?array $webhookQuery): static
    {
        $this->webhookQuery = $webhookQuery;
        return $this;
    }
}