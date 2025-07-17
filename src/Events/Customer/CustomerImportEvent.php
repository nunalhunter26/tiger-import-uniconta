<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Events\Customer;

use stdClass;
use Symfony\Contracts\EventDispatcher\Event;
use TigerImport\Model\Customer\Customer;
use TigerImport\Service\CustomerService;
use TigerMedia\General\TigerImportUniconta\Model\CustomDataModel;

class CustomerImportEvent extends Event
{
    public function __construct(
        private readonly Customer $customer,
        private readonly stdClass $unicontaCustomer,
        private readonly CustomDataModel $customDataModel,
        private readonly CustomerService $service
    )
    {
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * @return stdClass
     */
    public function getUnicontaCustomer(): stdClass
    {
        return $this->unicontaCustomer;
    }

    public function getCustomDataModel(): CustomDataModel
    {
        return $this->customDataModel;
    }

    public function getService(): CustomerService
    {
        return $this->service;
    }
}