<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Events\Customer;

use Symfony\Contracts\EventDispatcher\Event;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;

class CustomerParameterEvent extends Event
{
    public function __construct(
        private readonly ImportParameterModel $importParameter
    )
    {
    }

    public function getImportParameter(): ImportParameterModel
    {
        return $this->importParameter;
    }
}