<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Service;

use Doctrine\DBAL\Exception;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Kernel;
use stdClass;
use TigerImport\Model\Price;
use TigerImport\Model\StockUpdate;
use TigerMedia\General\TigerImportUniconta\Helper\ConfigHelper;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerImport\Service\ProductService;

class ProductPriceStockService extends AbstractImport
{
    private ProductService $productService;
    private TaxCalculator $taxCalculator;

    public function __construct(
        RestApi $restApi,
        ConfigHelper $configHelper,
        ProductService  $productService,
    )
    {
        $this->productService = $productService;
        $this->taxCalculator = new TaxCalculator();
        parent::__construct($restApi, $configHelper);
    }

    public function import(ImportParameterModel $params): void
    {
        $this->saveLastRun();
        $apiProducts = $this->restApi->getStream('Query/InvItem');
        $this->process($apiProducts);
    }

    public function webhookImport(ImportParameterModel $params): void
    {
        $request = $params->getWebhookRequest();
        $action = $params->getWebhookQuery()['Action'];

        if ($action === 'delete') {
            return;
        }

        $this->process([(object) [
            'Item'        => $request['Item'],
            'SalesPrice1' => $request['SalesPrice1'],
            'Available'   => $request['Available'],
            'ItemType'    => $request['ItemType']
        ]]);
    }

    public function getServiceTag(): string
    {
        return 'productPriceStock';
    }

    /**
     * @return array<string>
     */
    private function getCurrentData(): array
    {
        $productNumbers = [];
        $connection = Kernel::getConnection();
        $sqlResult = [];

        try {
            $sqlResult = $connection->executeQuery("SELECT product_number FROM `product`")->fetchAllAssociative();
        } catch (Exception $exception) {
            $this->restApi->logger->error($exception->getMessage(), $exception->getTrace());
        }

        foreach ($sqlResult as $res) {
            $productNumbers[] = $res['product_number'];
        }

        return $productNumbers;
    }

    /**
     * Sets the price object
     * @param float $netPrice
     * @param TaxRuleCollection $taxRuleCollection
     * @return Price
     */
    private function setPrice(float $netPrice, TaxRuleCollection $taxRuleCollection): Price
    {
        $price = new Price();
        $price->setLinked(true);
        $price->gross = $this->taxCalculator->calculateGross($netPrice, $taxRuleCollection);
        $price->net = $netPrice;
        return $price;
    }

    /**
     * @param stdClass[] $data
     * @return void
     */
    private function process(iterable $data): void
    {
        $container = [];
        $taxRuleCollection = new TaxRuleCollection();
        $taxRuleCollection->add(new TaxRule($this->productService->taxService->getDefaultTaxRate()));
        $currentData = $this->getCurrentData();

        foreach ($data as $datum) {
            if (!in_array($datum->Item, $currentData) || $datum->ItemType === 'BOM') {
                continue;
            }

            $newStock = (int)max(0, $datum->Available);
            $newPrice = $this->setPrice($datum->SalesPrice1, $taxRuleCollection);
            $container[] = StockUpdate::New($datum->Item, $newStock)->setPrice($newPrice);
        }

        foreach (array_chunk($container, 250) as $chunk) {
            $this->productService->updateStock($chunk);
        }
    }
}