<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Events\Product;

use Symfony\Contracts\EventDispatcher\Event;

class ProductDeleteInactiveEvent extends Event
{
    /** @var array<mixed> $activeProducts */
    protected array $activeProducts;

    /**
     * @param array<mixed> $activeProducts
     */
    public function __construct(array $activeProducts)
    {
        $this->activeProducts = $activeProducts;
    }

    /**
     * @return array<mixed>
     */
    public function getActiveProducts(): array
    {
        return $this->activeProducts;
    }
}