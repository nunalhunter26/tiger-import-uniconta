<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Events\Product;

use Symfony\Contracts\EventDispatcher\Event;
use TigerMedia\General\TigerImportUniconta\Model\CustomDataModel;

class ProductCustomDataEvent extends Event
{
    public function __construct(
        private readonly CustomDataModel $customDataModel
    )
    {
    }

    public function getCustomDataModel(): CustomDataModel
    {
        return $this->customDataModel;
    }
}