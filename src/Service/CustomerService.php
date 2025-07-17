<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Service;

use Doctrine\DBAL\Exception;
use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Kernel;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\Country\CountryEntity;
use stdClass;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TigerImport\Model\Customer\Customer;
use TigerImport\Model\Customer\CustomerAddress;
use TigerImport\Service\CustomerService as TigerImportCustomerService;
use TigerMedia\General\TigerImportUniconta\Events\Customer\CustomerCustomDataEvent;
use TigerMedia\General\TigerImportUniconta\Events\Customer\CustomerImportEvent;
use TigerMedia\General\TigerImportUniconta\Events\Customer\CustomerParameterEvent;
use TigerMedia\General\TigerImportUniconta\Helper\ConfigHelper;
use TigerMedia\General\TigerImportUniconta\Model\CustomDataModel;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;

class CustomerService extends AbstractImport
{

    /** @var ConfigHelper $configHelper */
    private ConfigHelper $configHelper;

    /** @var EntityRepository<CountryCollection> $countryRepository */
    private EntityRepository $countryRepository;

    /** @var String $customerGroup */
    protected string $customerGroup;

    /** @var string $salutationId */
    protected string $salutationId;

    /** @var EventDispatcherInterface  */
    private EventDispatcherInterface $eventDispatcher;

    /** @var TigerImportCustomerService $customerService */
    private TigerImportCustomerService $customerService;

    /** @var EntityRepository<CustomerCollection> $customerRepository */
    private EntityRepository $customerRepository;

    /**
     * @param ConfigHelper $configHelper
     * @param EntityRepository<CountryCollection> $countryRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param TigerImportCustomerService $customerService
     * @param RestApi $restApi
     * @param EntityRepository<CustomerCollection> $customerRepository
     */
    public function __construct(
        ConfigHelper               $configHelper,
        EntityRepository           $countryRepository,
        EventDispatcherInterface   $eventDispatcher,
        TigerImportCustomerService $customerService,
        RestApi                    $restApi,
        EntityRepository           $customerRepository
    )
    {
        $this->countryRepository = $countryRepository;
        $this->configHelper = $configHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->customerService = $customerService;
        $this->customerRepository = $customerRepository;
        parent::__construct($restApi, $configHelper);
    }

    /**
     * @param ImportParameterModel $params
     * @return void
     * @throws Exception
     */
    public function import(ImportParameterModel $params): void
    {
        $this->initConfig();
        $this->saveLastRun();

        if ($params->getCustomerNumber()) {
            $params->setQuery($params->getQuery() + ['filter.Account' => $params->getCustomerNumber()]);
        }

        $this->eventDispatcher->dispatch(new CustomerParameterEvent($params));
        $apiCustomers = $this->restApi->getStream('Query/Debtor', $params->getQuery());
        $customerNumbers = $this->parseData($apiCustomers);

        if (!$params->getCustomerNumber()) {
            $this->removeCustomers($customerNumbers);
        }
    }

    public function getServiceTag(): string
    {
        return 'customer';
    }

    public function webhookImport(ImportParameterModel $params): void
    {
        if ($params->getWebhookQuery()['Action'] === 'delete') {
            return;
        }

        $this->initConfig();
        $request = $params->getWebhookRequest();

        if (empty($request['Account'])) {
            $this->restApi->logger->critical("Webhook [{$this->getServiceTag()}], missing Account from request.", ['request' => $request]);;
            return;
        }

        $request['KeyStr'] = $request['Account'];
        $request['Country'] = $request['CountryName'] ?? '';
        $this->parseData([(object) $request]);
    }

    /**
     * @param stdClass[] $apiCustomers
     * @return string[]
     */
    private function parseData(iterable $apiCustomers): array
    {
        $customData = new CustomDataModel();
        $this->eventDispatcher->dispatch(new CustomerCustomDataEvent($customData));
        $customerArray = [];

        foreach($apiCustomers as $apiCustomer) {
            if ((!filter_var($apiCustomer->ContactEmail, FILTER_VALIDATE_EMAIL) &&
                    !filter_var($apiCustomer->InvoiceEmail, FILTER_VALIDATE_EMAIL)) ||
                $apiCustomer->Phone == null ||
                $apiCustomer->Name == null
            ) {
                continue;
            }

            $customerArray[$apiCustomer->KeyStr] = $apiCustomer->KeyStr;

            $customer = (new Customer())
                ->setCustomerNumber($apiCustomer->KeyStr)
                ->setName($apiCustomer->Name)
                ->setCompany($apiCustomer->Name)
                ->setActive(!$apiCustomer->Blocked)
                ->setEmail($apiCustomer->ContactEmail ?? $apiCustomer->InvoiceEmail)
                ->setLanguageName($apiCustomer->UserLanguage)
                ->setSalutationId($this->salutationId)
                ->setVatIds($apiCustomer->VatNumber ? [$apiCustomer->VatNumber] : [])
                ->setGroupId($this->customerGroup);

            $customerAddress = (new CustomerAddress())
                ->setCompany($apiCustomer->Name)
                ->setSalutationId($this->salutationId)
                ->setName($apiCustomer->Name)
                ->setCountry($this->getCountryCode($apiCustomer->Country ?? 'Denmark'))
                ->setZipcode($apiCustomer->ZipCode ?? '')
                ->setStreet($apiCustomer->Address1 ?? '?')
                ->setAdditionalAddressLine1($apiCustomer->Address2 ?? '')
                ->setAdditionalAddressLine2($apiCustomer->Address3 ?? '')
                ->setPhoneNumber($apiCustomer->Phone)
                ->setCity($apiCustomer->City ?? '?');

            $customer->setShippingAddress($customerAddress);
            $customer->setBillingAddress($customerAddress);
            $customer->setCustomFields([
                'tigerimportuniconta_customer_field_set' => [
                    'customer_discountgroup' => $apiCustomer->PriceList ?? '',
                    'from_uniconta' => 'true'
                ]
            ]);

            $this->eventDispatcher->dispatch(
                new CustomerImportEvent(
                    $customer,
                    $apiCustomer,
                    $customData,
                    $this->customerService
                )
            );

            // Option to skip a customer by implementing from CustomerImportEvent
            if ($customer->getCustomerNumber() == null) {
                continue;
            }

            $this->customerService->add($customer);
        }

        $this->customerService->flush();
        return $customerArray;
    }

    /**
     * @return void
     */
    protected function initConfig(): void
    {
        $this->customerGroup = $this->configHelper->getSystemConfig('defaultCustomerGroup');
        $this->salutationId = $this->configHelper->getSystemConfig('defaultSalutation');
    }

    /**
     * @param string $countryName
     * @return string|null
     */
    protected function getCountryCode(string $countryName): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $countryName));

        /** @var CountryEntity $country */
        $country = $this->countryRepository->search($criteria, Context::createDefaultContext())->first();

        if ($country == null) {
            return 'DK';
        }

        return $country->getIso();
    }

    /**
     * @param mixed[] $activeCustomers
     * @throws Exception
     */
    public function removeCustomers(array $activeCustomers): void
    {
        if ($this->configHelper->getSystemConfig('removeCustomersNotInUniconta')) {
            try {
                $connection = Kernel::getConnection();
                $swCustomers = $connection->executeQuery(
                    "SELECT customer_number, LOWER(HEX(id))
                    FROM `customer`
                    WHERE JSON_CONTAINS_PATH(custom_fields, 'one', '$.from_uniconta')"
                )->fetchAllKeyValue();

                if (count($swCustomers) > 0) {
                    $toDelete = array_diff_key($swCustomers, $activeCustomers);
                    if (count($toDelete) > 0) {
                        $customerIds = array_values($toDelete);
                        $this->restApi->logger->info('Removing customers: ' . implode(',', $customerIds));
                        $deletePayload = array_map(fn($id) => ['id' => $id], $customerIds);
                        $this->customerRepository->delete($deletePayload, Context::createDefaultContext());
                    }
                }
            } catch (\Exception $e) {
                $this->restApi->logger->error('Failed to remove customers: ' . $e->getMessage());
                throw $e;
            }
        }
    }
}