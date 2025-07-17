<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Service;

use Doctrine\DBAL\Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Kernel;
use stdClass;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TigerImport\Model\AdvancedPrice;
use TigerImport\Model\MediaFile;
use TigerImport\Model\Price;
use TigerImport\Model\Product;
use TigerImport\Service\CategoryService;
use TigerImport\Service\ManufacturerService;
use TigerImport\Service\ProductService as TigerProductService;
use TigerMedia\General\TigerImportUniconta\Events\File\BeforeProductImageAddedEvent;
use TigerMedia\General\TigerImportUniconta\Events\Product\ProductCustomDataEvent;
use TigerMedia\General\TigerImportUniconta\Events\Product\ProductImportEvent;
use TigerMedia\General\TigerImportUniconta\Events\Product\ProductParameterEvent;
use TigerMedia\General\TigerImportUniconta\Events\Product\ProductWebhookDeleteActionEvent;
use TigerMedia\General\TigerImportUniconta\Helper\ConfigHelper;
use TigerMedia\General\TigerImportUniconta\Helper\FileHelper;
use TigerMedia\General\TigerImportUniconta\Model\CustomDataModel;
use TigerMedia\General\TigerImportUniconta\Model\DocumentModel;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;

class ProductService extends AbstractImport
{
    CONST IMAGE_TYPES = [
        'BMP',
        'IEF',
        'IEF',
        'JPEG',
        'JFIF',
        'SVG',
        'TIFF',
        'PNG',
        'WWW'
    ];

    /** @var TigerProductService $productService */
    private TigerProductService $productService;

    /** @var CategoryService $categoryService */
    private CategoryService $categoryService;

    /** @var ManufacturerService $manufacturerService */
    private ManufacturerService $manufacturerService;

    /** @var TaxCalculator $taxCalculator */
    private TaxCalculator $taxCalculator;

    /** @var ConfigHelper $configHelper */
    private ConfigHelper $configHelper;

    /** @var String $topCategoryId */
    private String $topCategoryId;

    /** @var String|null $cmsPageId */
    private ?String $cmsPageId;

    /** @var array<string> $category */
    private array $category;

    /** @var array<DocumentModel> $photos */
    private array $photos;

    /** @var string|null $manufacturer */
    private ?string $manufacturerName;

    /** @var EventDispatcherInterface $eventDispatcher */
    private EventDispatcherInterface $eventDispatcher;

    /** @var mixed $priceList */
    private mixed $priceList = [];

    /** @var EntityRepository<ProductCollection> $productsRepository */
    private EntityRepository $productsRepository;

    /** @var LoggerInterface $logger */
    private LoggerInterface $logger;

    /**
     * @param TigerProductService $productService
     * @param CategoryService $categoryService
     * @param ManufacturerService $manufacturerService
     * @param ConfigHelper $configHelper
     * @param EventDispatcherInterface $eventDispatcher
     * @param RestApi $restApi
     * @param EntityRepository<ProductCollection> $productsRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        TigerProductService      $productService,
        CategoryService          $categoryService,
        ManufacturerService      $manufacturerService,
        ConfigHelper             $configHelper,
        EventDispatcherInterface $eventDispatcher,
        RestApi                  $restApi,
        EntityRepository         $productsRepository,
        LoggerInterface          $logger
    )
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
        $this->manufacturerService = $manufacturerService;
        $this->taxCalculator = new TaxCalculator();
        $this->configHelper = $configHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->productsRepository = $productsRepository;
        $this->logger = $logger;
        parent::__construct($restApi, $configHelper);
    }

    /**
     * @param ImportParameterModel $params
     * @return void
     * @throws GuzzleException|Exception
     */
    public function import(ImportParameterModel $params): void
    {
        $this->initVariables($params->getPriceList());
        $this->saveLastRun();
        $this->eventDispatcher->dispatch(new ProductParameterEvent($params));
        $this->loadProducts($params->getProductNumber(), $params->getQuery());
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function webhookImport(ImportParameterModel $params): void
    {
        $request = $params->getWebhookRequest();
        $query = $params->getWebhookQuery();
        $action = $params->getWebhookQuery()['Action'];

        if ($action === 'delete') {
            $this->eventDispatcher->dispatch(new ProductWebhookDeleteActionEvent($request, $query));
            return;
        }

        $this->initVariables();
        $this->parseProducts([(object) $this->transformUserFields($request)]);
    }

    public function getServiceTag(): string
    {
        return 'product';
    }

    /**
     * Instantiate class variables
     * @param string|null $priceList
     * @return void
     */
    private function initVariables(string $priceList = null): void
    {
        $this->manufacturerName = $this->configHelper->getSystemConfig('Manufacturer');
        $this->topCategoryId = $this->configHelper->getSystemConfig('parentCategory');
        $this->cmsPageId = $this->configHelper->getSystemConfig('productCmsPageId');
        $this->category = $this->loadCategories();
        $this->photos = $this->loadDocuments();

        if ($priceList) {
            $this->priceList = $this->restApi->get('Query/InvPriceListLine?' . http_build_query(['filter._PriceList' => $priceList]));
        }
    }

    /**
     * @param string|null $productId
     * @param array<mixed> $query
     * @return void
     * @throws GuzzleException
     * @throws Exception
     */
    private function loadProducts(?string $productId, array $query): void
    {
        if ($productId) {
            $query += ['filter.Item' => $productId];
        }

        /**
         * Dynamic field selection fra Uniconta
         * You can select which fields that you want to use to minimize load in the config
         * Default is all fields
         */
        $productFields = $this->configHelper->getSystemConfig('productFields');
        $fields = $productFields != null ? array_merge(['Item', 'Name', 'DiscountGroup', 'Group', 'ItemType', 'RowId'], $productFields) : null;
        $url = $fields === null ? 'Query/InvItem?' . http_build_query($query) : 'Query/InvItem?select[]=' . implode('&select[]=', $fields) . '&' . http_build_query($query);
        $this->parseProducts($this->restApi->getStream($url));;
    }

    /**
     * @param stdClass[] $apiProducts
     * @return void
     * @throws GuzzleException|Exception
     */
    private function parseProducts(iterable $apiProducts): void
    {
        $taxId = $this->productService->taxService->GetDefaultTaxId();
        $taxRuleCollection = new TaxRuleCollection();
        $taxRuleCollection->add(new TaxRule($this->productService->taxService->getDefaultTaxRate()));
        $customData = new CustomDataModel();
        $this->eventDispatcher->dispatch(new ProductCustomDataEvent($customData));
        $productArray = [];

        /** @var stdClass $apiProduct */
        foreach ($apiProducts as $apiProduct) {
            if ($apiProduct->Name == null || $apiProduct->Item == null) {
                continue;
            }

            $productArray[$apiProduct->Item] = $apiProduct->Item;

            $userFields = [];

            if (isset($apiProduct->UserFields) && isset($apiProduct->UserField)) {
                $userFields = $this->getUserFields($apiProduct);
            }

            $product = new Product();

            if ($this->cmsPageId) {
                $product->cmsPageId = $this->cmsPageId;
            }

            $product->name = $apiProduct->Name;
            $product->productNumber = $apiProduct->Item;
            $product->isCloseout = true;
            $product->taxId = $taxId;
            $product->advancedPrice = $this->getPriceList($apiProduct->Item);

            if ($this->configHelper->getSystemConfig('enableCategoryHandling') != null) {
                $product->categories = [array('id' => $this->getCategoryId($apiProduct->Group))];
            }

            if (isset($apiProduct->Available)) {
                $product->stock = (int) max(0, $apiProduct->Available);
            }

            if (isset($apiProduct->Unit)) {
                $product->unit = $apiProduct->Unit;
            }

            if (isset($apiProduct->EAN)) {
                $product->ean = $apiProduct->EAN;
            }

            if (isset($apiProduct->Weight)) {
                $product->weight = $apiProduct->Weight;
            }

            if (isset($apiProduct->SalesPrice1)) {
                $product->price = $this->setPrice($apiProduct->SalesPrice1, $taxRuleCollection);
            }

            if (isset($apiProduct->PurchasePrice)) {
                $product->purchasePrices = $this->setPrice($apiProduct->PurchasePrice, $taxRuleCollection);
            }

            if (isset($apiProduct->CostPrice)) {
                $product->costPrices = $this->setPrice($apiProduct->CostPrice, $taxRuleCollection);
            }

            if (isset($apiProduct->Photo)) {
                $product->mediaFiles = $this->getMedia($apiProduct->RowId, $apiProduct->Photo);
            }

            if (isset($apiProduct->SalesQty)) {
                $salesQty = intval($apiProduct->SalesQty);
                $product->purchaseSteps = max(1, $salesQty);
                $product->minPurchase = max(1, $salesQty);
            }

            if ($apiProduct->ItemType === 'BOM' && $this->productService->getIdFromProductNumber($product->productNumber)) {
                unset($product->stock);
            }

            if ($this->manufacturerName != null) {
                $product->manufacturerId = $this->manufacturerService->getOrCreateManufacturerGuid($this->manufacturerName);
            }

            $product->customFields['tigerimportuniconta_product_field_set'] = [
                'supplierName'              => strtoupper($this->manufacturerName ?? ''),
                'productgroup'              => $apiProduct->Group,
                'product_discountgroup'     => $apiProduct->DiscountGroup
            ];

            $this->eventDispatcher->dispatch(new ProductImportEvent($product, $apiProduct, $userFields, $customData));
            /**
             * Approach to skip a product that can be implemented
             * through custom implementation from ProductImportEvent
             */
            if (empty($product->productNumber)) {
                continue;
            }

            $this->productService->addToQueue($product);
        }

        if (count($productArray) > 10 && $this->configHelper->getSystemConfig('deactivateProductsNotInUniconta')) {
            $this->deactivateProducts($productArray);
        }

        $this->productService->queueFlush();
    }

    /**
     * @param stdClass $apiProduct
     * @return array<mixed>
     */
    private function getUserFields(stdClass $apiProduct): array
    {
        $properties = [];
        foreach ($apiProduct->UserFields as $userFields) {
            if (isset($apiProduct->UserField->_data[$userFields->Index])) {
                $properties[$userFields->PropName] = $apiProduct->UserField->_data[$userFields->Index] ?? null;
            }
            if (is_array($apiProduct->UserField) && isset($apiProduct->UserField[$userFields->Index])) {
                $properties[$userFields->PropName] = $apiProduct->UserField[$userFields->Index] ?? null;
            }
        }

        return $properties;
    }

    /**
     * Transforms the response into userFields as userFields are
     * custom-made and in order to replicate the current handling of Products
     * @param mixed[] $request
     * @return mixed[]
     */
    private function transformUserFields(array $request): array
    {
        $userFields = [];
        $userField = [];
        $index = 0;

        foreach ($request as $key => $value) {
            $userFields[$index] = (object)[
                'PropName' => $key,
                'Index'    => $index
            ];
            $userField[$index] = $value;
            $index++;
        }

        $request['UserFields'] = $userFields;
        $request['UserField'] = $userField;
        return $request;
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
     * @param int $rowId
     * @param int|null $coverPhoto
     * @return array<MediaFile>
     * @throws GuzzleException
     */
    private function getMedia(int $rowId, ?int $coverPhoto): array
    {
        $mediaFiles = [];
        $i = 0;

        $photos = array_values(array_filter($this->photos, function(DocumentModel $photo) use ($rowId) {
            return $photo->getTableRowId() === $rowId;
        }));

        $this->eventDispatcher->dispatch(new BeforeProductImageAddedEvent($photos, $coverPhoto));

        foreach ($photos as $photo) {
            if ($photo->getUrl() != null) {
                $image = MediaFile::fromURL($photo->getUrl());
            } else {
                $image = $this->getBase64Image($photo);

                if ($image === null) {
                    continue;
                }

                $image = MediaFile::fromBlob(FileHelper::filterFileName($photo->getProductNumber()) . FileHelper::filterFileName($photo->getFileName()) . '_' . $i, $image);
            }

            if ($photo->getRowId() == $coverPhoto) {
                array_unshift($mediaFiles, $image);
            } else {
                $mediaFiles[] = $image;
            }

            $i++;
        }

        return $mediaFiles;
    }

    /**
     * @param DocumentModel $documentModel
     * @return string|null
     * @throws GuzzleException
     */
    private function getBase64Image(DocumentModel $documentModel): ?string
    {
        try {
            $response = $this->restApi->getPlainClient()->get($documentModel->getQueryString());
        } catch (RequestException $exception) {
            $this->logger->critical('Failed to download image from ' . $documentModel->getQueryString(), ['exception' => $exception]);
            return null;
        }

        return base64_encode($response->getBody()->getContents());
    }

    /**
     * Get category id
     * @param string $categoryGroup
     * @return string|null
     */
    private function getCategoryId(string $categoryGroup): ?string
    {
        if (isset($this->category[$categoryGroup])) {
            return $this->categoryService->getOrCreateCategory($this->category[$categoryGroup], $this->topCategoryId, false, active: false);
        }

        return null;
    }

    /**
     * Load Uniconta categories
     * @return array<string>
     */
    private function loadCategories(): array
    {
        if ($this->configHelper->getSystemConfig('enableCategoryHandling') == null) {
            return [];
        }

        $apiCategories = $this->restApi->getStream('Query/InvGroup');
        $categories = [];

        foreach ($apiCategories as $category) {
            if (!empty($category->Name && isset($category->Group))) {
                $categories[$category->Group] = $category->Name;
            }
        }

        return $categories;
    }

    /**
     * Load Uniconta documents
     * @return array<DocumentModel>
     */
    private function loadDocuments(): array
    {
        $documents = [];

        if (!$this->configHelper->getSystemConfig('importDocumentsFromUniconta')) {
            return $documents;
        }

        $images = $this->restApi->get('Query/UserDocs?tableId=23');

        foreach ($images as $image) {
            if ($image->Group !== 'Photo' && !in_array($image->DocumentType, self::IMAGE_TYPES)) {
                continue;
            }

            $documents[] = new DocumentModel($image);
        }

        usort($documents, fn($a, $b) => strcmp($a->getFilename(), $b->getFilename()));
        return $documents;
    }

    /**
     * @param string $productNumber
     * @return AdvancedPrice[]
     */
    private function getPriceList(string $productNumber): array
    {
        if (!$this->priceList) {
            return [];
        }

        $prices = array_filter($this->priceList, function($price) use ($productNumber) {
            return $price->_Item === $productNumber;
        });

        if (!$prices) {
            return [];
        }

        $advancedPrices = [];
        foreach ($prices as $price) {
            $next = next($prices);
            $advPrice = new AdvancedPrice();
            $advPrice->ruleName = 'All customers';
            $advPrice->quantityStart = (int) $price->Qty;
            $advPrice->quantityEnd = $next ? (int) $next->Qty - 1 : null;
            $discountedNetPrice = floatval($price->_Price) * ((100 - floatval($price->_Pct)) / 100);
            $priceModel = new Price();
            $priceModel->net = $discountedNetPrice;
            $priceModel->gross = $discountedNetPrice * 1.25;
            $priceModel->listPrice = new Price();
            $priceModel->listPrice->gross = $price->_Price * 1.25;
            $priceModel->listPrice->net = $price->_Price;
            $advPrice->price = $priceModel->getData();
            $advancedPrices[] = $advPrice;
        }

        return $advancedPrices;
    }

    /**
     * @param array<mixed> $activeProducts
     * @throws Exception
     */
    private function deactivateProducts(array $activeProducts): void
    {
        $connection = Kernel::getConnection();
        $swProducts = $connection->executeQuery("SELECT product_number, LOWER(hex(id)) FROM `product`")->fetchAllKeyValue();

        if (count($activeProducts) > 0) {
            $result = array_diff_key($swProducts, $activeProducts);
            $productIds = array_values($result);

            $payload = array_map(fn($id) => ['id' => $id, 'active' => false], $productIds);
            $this->logger->info('Deactivating products: ' . implode(',', $productIds));
            $this->productsRepository->update($payload, Context::createDefaultContext());
        }
    }
}