<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Events\Product;

use Symfony\Contracts\EventDispatcher\Event;
use TigerImport\Model\Product;
use TigerMedia\General\TigerImportUniconta\Model\CustomDataModel;

class ProductImportEvent extends Event
{
    protected Product $product;
    protected \stdClass $unicontaProduct;
    protected CustomDataModel $customDataModel;

    /** @var array<mixed> $userFields */
    protected array $userFields;

    /**
     * @param Product $product
     * @param \stdClass $unicontaProduct
     * @param array<mixed> $userFields
     * @param CustomDataModel $customDataModel
     */
    public function __construct(Product $product, \stdClass $unicontaProduct, array $userFields, CustomDataModel $customDataModel)
    {
        $this->product = $product;
        $this->unicontaProduct = $unicontaProduct;
        $this->userFields = $userFields;
        $this->customDataModel = $customDataModel;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return \stdClass
     */
    public function getUnicontaProduct(): \stdClass
    {
        return $this->unicontaProduct;
    }

    /**
     * @return array<mixed>
     */
    public function getUserFields(): array
    {
        return $this->userFields;
    }

    public function getCustomDataModel(): CustomDataModel
    {
        return $this->customDataModel;
    }
}