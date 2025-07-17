<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Events\Customer;

use Symfony\Contracts\EventDispatcher\Event;
use TigerMedia\General\TigerImportUniconta\Model\CustomDataModel;

class CustomerCustomDataEvent extends Event
{
    public function __construct(
        private readonly CustomDataModel $dataModel
    )
    {
    }

    public function getDataModel(): CustomDataModel
    {
        return $this->dataModel;
    }
}