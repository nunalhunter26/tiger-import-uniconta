<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Service;

use TigerImport\Model\StockUpdate;
use TigerMedia\General\TigerImportUniconta\Helper\ConfigHelper;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerImport\Service\ProductService;

class BundleStockService extends AbstractImport
{

    private ProductService $productService;

    /** @var mixed[] $children */
    private array $children = [];

    public function __construct(
        RestApi $restApi,
        ConfigHelper $configHelper,
        ProductService $productService,
    )
    {
        $this->productService = $productService;
        parent::__construct($restApi, $configHelper);
    }

    public function import(ImportParameterModel $params): void
    {
        $this->saveLastRun();
        $itemParts = [];
        $itemBoms = $this->restApi->getStream('Query/InvBOM');
        $productNumbers = [];
        $container = [];

        foreach ($itemBoms as $itemBom) {
            $itemParts[$itemBom->ItemMaster][$itemBom->ItemPart] = intval($itemBom->Qty);
            $productNumbers[] = $itemBom->ItemMaster;
            $productNumbers[] = $itemBom->ItemPart;
        }

        $this->children = $this->getBomChildren(array_values(array_unique($productNumbers)));

        foreach ($itemParts as $key => $part) {
            $stock = max(0, $this->getStocks($part, array_intersect_key($this->children, $part), $itemParts));
            $this->restApi->logger->info('Inserting ' . $stock . ' stocks for BOM: ' . $key);
            $container[] = StockUpdate::New((string) $key, $stock);
        }

        foreach (array_chunk($container, 250) as $chunk) {
            $this->productService->updateStock($chunk);
        }
    }

    public function getServiceTag(): string
    {
        return 'bundleStock';
    }

    /**
     * @param mixed[]$itemParts
     * @return mixed[]
     */
    private function getBomChildren(array $itemParts): array
    {
        $bomParts = [];
        foreach (array_chunk($itemParts, 2000) as $chunk) {
            $params = [
                'filter.Item' => trim(implode(' OR ', $chunk)),
                'select[]'    => 'Available'
            ];

            $children = $this->restApi->getStream('Query/InvItem?' . http_build_query($params) . '&' . http_build_query(['select[]' => 'Item']) . '&' . http_build_query(['select[]' => 'ItemType']));
            foreach ($children as $child) {
                $bomParts[$child->Item] = $child;
            }
        }

        return $bomParts;
    }

    /**
     * @param array<mixed> $itemParts
     * @param mixed[] $children
     * @param array<mixed> $parts
     * @return int
     */
    private function getStocks(array $itemParts, array $children, array $parts): int
    {
        $stocks = [];
        foreach ($children as $child) {
            if ($child->ItemType === 'BOM') {
                if (array_key_exists($child->Item, $parts)) {
                    $stocks[] = $this->getStocks($parts[$child->Item], array_intersect_key($this->children, $parts[$child->Item]), $parts);
                }

                continue;
            }

            $stocks[] = $itemParts[$child->Item] > 0 ? intval($child->Available) / $itemParts[$child->Item] : intval($child->Available);
        }

        return max(0, intval(min($stocks)));
    }

    public function webhookImport(ImportParameterModel $params): void
    {
        // TODO: Implement webhookImport() method.
    }
}