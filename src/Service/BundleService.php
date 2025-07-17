<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Service;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use TigerImport\Service\ProductService;
use TigerMedia\General\TigerImportUniconta\Helper\ConfigHelper;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;

class BundleService extends AbstractImport
{
    private ProductService $productService;

    /** @var EntityRepository<ProductCollection> $productRepository */
    private EntityRepository $productRepository;

    /**
     * @param RestApi $restApi
     * @param ProductService $productService
     * @param EntityRepository<ProductCollection> $productRepository
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        RestApi $restApi,
        ProductService $productService,
        EntityRepository $productRepository,
        ConfigHelper $configHelper
    )
    {
        $this->productService = $productService;
        $this->productRepository = $productRepository;
        parent::__construct($restApi, $configHelper);
    }

    public function import(ImportParameterModel $params): void
    {
        $bundles = iterator_to_array($this->restApi->getStream('Query/InvBOM'));
        $parents = $this->getAssociatedProducts(array_column($bundles, '_ItemMaster'));
        $children = $this->getAssociatedProducts(array_column($bundles, '_ItemPart'));

        $structured = [];
        foreach ($bundles as $bundle) {
            if (!array_key_exists($bundle->_ItemMaster, $parents) || !array_key_exists($bundle->_ItemPart, $children)) {
                continue;
            }

            $structured[] = [
                'id' => $parents[$bundle->_ItemMaster],
                'tigerBundles' => [
                    [
                        'id' => $children[$bundle->_ItemPart]
                    ]
                ]
            ];
        }

        $this->insertBundles($structured);
    }

    public function getServiceTag(): string
    {
        return 'bundle';
    }

    /**
     * @param array<string>|null $productNumbers
     * @return array<string>|null
     */
    private function getAssociatedProducts(?array $productNumbers): ?array
    {
        if (!$productNumbers) {
            return null;
        }

        return iterator_to_array($this->productService->getIdFromProductNumbers($productNumbers));
    }

    /**
     * @param array<mixed> $bundles
     * @return void
     */
    private function insertBundles(array $bundles): void
    {
        $context = Context::createDefaultContext();

        foreach (array_chunk($bundles, 50) as $chunk) {
            $this->productRepository->upsert($chunk, $context);
        }
    }

    public function webhookImport(ImportParameterModel $params): void
    {
        // TODO: Implement webhookImport() method.
    }
}