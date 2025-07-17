<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Service;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Kernel;
use TigerImport\Model\Customer\CustomerGroup;
use TigerImport\Model\Price;
use TigerImport\Service\CurrencyService;
use TigerImport\Service\CustomerGroupService;
use TigerImport\Service\ProductService;
use TigerMedia\CustomPrice\Core\Content\CustomPrice\CustomPriceCollection;
use TigerMedia\General\TigerImportUniconta\Helper\ConfigHelper;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\Model\PricelistLineModel;

class CustomPriceService extends AbstractImport
{
    /** @var EntityRepository<CustomPriceCollection> $customPriceRepository */
    private EntityRepository $customPriceRepository;
    private CustomerGroupService $customerGroupService;
    private ProductService $productService;
    private ConfigHelper $configHelper;
    private CurrencyService $currencyService;

    /**
     * @param RestApi $restApi
     * @param EntityRepository<CustomPriceCollection> $customPriceRepository
     * @param CustomerGroupService $customerGroupService
     * @param ProductService $productService
     * @param ConfigHelper $configHelper
     * @param CurrencyService $currencyService
     */
    public function __construct(
        RestApi $restApi,
        EntityRepository $customPriceRepository,
        CustomerGroupService $customerGroupService,
        ProductService $productService,
        ConfigHelper $configHelper,
        CurrencyService $currencyService
    )
    {
        $this->customPriceRepository = $customPriceRepository;
        $this->customerGroupService = $customerGroupService;
        $this->productService = $productService;
        $this->configHelper = $configHelper;
        $this->currencyService = $currencyService;
        parent::__construct($restApi, $configHelper);
    }

    /**
     * @param ImportParameterModel $params
     * @return void
     * @throws Exception
     * @throws \Exception
     */
    public function import(ImportParameterModel $params): void
    {
        $timestamp = new DateTimeImmutable();
        $this->saveLastRun();
        $priceLists = $this->restApi->getStream('Query/DebtorPriceList', ['filter.Active' => true]);

        foreach ($priceLists as $priceList) {
            foreach (array_chunk($this->getPriceListLines($priceList->PriceList, $priceList->Currency ?: 'DKK'), 100) as $chunk) {
                $this->customPriceRepository->upsert($chunk, Context::createDefaultContext());
            }
        }

        $this->clearTable($timestamp->format('Y-m-d H:i:s'));
    }

    public function getServiceTag(): string
    {
        return 'customPrice';
    }

    /**
     * @param string $priceList
     * @param string $currency
     * @return mixed[]
     * @throws \Exception
     */
    private function getPriceListLines(string $priceList, string $currency): array
    {
        $customPricesData = [];
        $priceListLines = $this->restApi->getStream('Query/InvPriceListLine', ['filter.PriceList' => $priceList]);

        foreach ($priceListLines as $listLine) {
            $plModel = new PricelistLineModel($listLine);
            $prices = $this->constructPriceModel($plModel->getPrice(), $currency);

            $priceData = [
                'id'              => Uuid::fromStringToHex($plModel->getPriceList() . $plModel->getItem() . $plModel->getItemGroup() . $plModel->getQuantity() . $plModel->getPrice() . $plModel->getDiscount() . $plModel->getPercent() . $plModel->getDiscountGroup()),
                'endingDate'      => $plModel->getToDate(),
                'startingDate'    => $plModel->getFromDate(),
                'minQuantity'     => max(1, $plModel->getQuantity()),
                'discount'        => $plModel->getPercent(),
                'flatDiscount'    => $plModel->getDiscount(),
                'price'           => null,
                'productId'       => null,
                'productGroup'    => null,
                'customerGroupId' => null,
                'discountGroup'   => null
            ];

            foreach ($prices as $price) {
                if ($price->getNet()) {
                    $priceData['price'][] = $price->getData()[0];
                }
            }

            if ($plModel->getItem()) {
                $priceData['productId'] = $this->productService->getIdFromProductNumber($plModel->getItem());
            }

            if ($plModel->getItemGroup()) {
                $priceData['productGroup'] = $plModel->getItemGroup();
            }

            if ($plModel->getDiscountGroup()) {
                $priceData['discountGroup'] = $plModel->getDiscountGroup();
            }

            if ($plModel->getPriceList()) {
                $priceData['customerGroupId'] = $this->customerGroupService->getOrCreateCustomerGroupId((new CustomerGroup($plModel->getPriceList()))->setDisplayGross(!$this->configHelper->getSystemConfig('isNet')));
            }

            $customPricesData[] = $priceData;
        }

        return $customPricesData;
    }

    /**
     * @throws Exception
     */
    private function clearTable(string $timestamp): void
    {
        $connection = Kernel::getConnection();
        try {
            $connection->beginTransaction();
            $connection->executeStatement("
                DELETE FROM `custom_price` 
                WHERE (updated_at < :timestamp OR updated_at IS NULL AND created_at < :timestamp)", [
                'timestamp'       => $timestamp
            ]);
            $connection->commit();
        } catch (Exception $exception) {
            $connection->rollBack();
            $this->restApi->logger->critical('Unable to truncate custom_price table.', ['exception' => $exception]);
            throw $exception;
        }

        $connection->close();
    }

    /**
     * @param float $price
     * @param string $currency
     * @return Price[]
     */
    private function constructPriceModel(float $price, string $currency): array
    {
        $prices = [];
        $priceModel = new Price();
        $priceModel->setGross($price * 1.25);
        $priceModel->setNet($price);
        $priceModel->setCurrencyId($this->currencyService->getCurrencyId($currency));
        $priceModel->setLinked(false);
        $prices[] = $priceModel;

        if ($currency !== 'DKK') {
            $prices[] = $this->currencyService->convertPrice(clone $priceModel);
        }

        return $prices;
    }

    public function webhookImport(ImportParameterModel $params): void
    {
        // TODO: Implement webhookImport() method.
    }
}