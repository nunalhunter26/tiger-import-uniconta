<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Model;

use DateTimeImmutable;
use Exception;
use stdClass;

class PricelistLineModel
{
    private string $priceList;
    private ?string $item;
    private float $price;
    private float $percent;
    private float $discount;
    private int $quantity;
    private ?string $itemGroup;
    private ?string $discountGroup;
    private ?DateTimeImmutable $fromDate = null;
    private ?DateTimeImmutable $toDate = null;

    /**
     * @throws Exception
     */
    public function __construct(stdClass $pricelistLine)
    {
        $this
            ->setPriceList($pricelistLine->PriceList)
            ->setItem($pricelistLine->Item)
            ->setPrice($pricelistLine->Price)
            ->setPercent($pricelistLine->Pct)
            ->setDiscount($pricelistLine->Discount)
            ->setQuantity($pricelistLine->Qty)
            ->setItemGroup($pricelistLine->ItemGroup)
            ->setFromDate($pricelistLine->ValidFrom)
            ->setDiscountGroup($pricelistLine->DiscountGroup)
            ->setToDate($pricelistLine->ValidTo);
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getPriceList(): string
    {
        return $this->priceList;
    }

    public function setPriceList(string $priceList): static
    {
        $this->priceList = $priceList;
        return $this;
    }

    public function getItem(): ?string
    {
        return $this->item;
    }

    public function setItem(?string $item): static
    {
        $this->item = $item;
        return $this;
    }

    public function getPercent(): float
    {
        return $this->percent;
    }

    public function setPercent(float $percent): static
    {
        $this->percent = $percent;
        return $this;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function setDiscount(float $discount): static
    {
        $this->discount = $discount;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getItemGroup(): ?string
    {
        return $this->itemGroup;
    }

    public function setItemGroup(?string $itemGroup): static
    {
        $this->itemGroup = $itemGroup;
        return $this;
    }

    public function getFromDate(): ?DateTimeImmutable
    {
        return $this->fromDate;
    }

    /**
     * @throws Exception
     */
    public function setFromDate(?string $fromDate): static
    {
        if ($fromDate != null) {
            $date = new DateTimeImmutable($fromDate);
            if ($date > new DateTimeImmutable('2000-01-01')) {
                $this->fromDate = $date;
            }
        }

        return $this;
    }

    public function getToDate(): ?DateTimeImmutable
    {
        return $this->toDate;
    }

    /**
     * @throws Exception
     */
    public function setToDate(?string $toDate): static
    {
        if ($toDate != null) {
            $date = new DateTimeImmutable($toDate);
            if ($date > new DateTimeImmutable('2000-01-01')) {
                $this->toDate = $date;
            }
        }

        return $this;
    }

    public function getDiscountGroup(): ?string
    {
        return $this->discountGroup;
    }

    public function setDiscountGroup(?string $discountGroup): static
    {
        $this->discountGroup = $discountGroup;
        return $this;
    }
}