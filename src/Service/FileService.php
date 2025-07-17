<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use stdClass;
use TigerImport\Service\ImageImport;
use TigerMedia\General\TigerImportUniconta\Helper\ConfigHelper;
use TigerMedia\General\TigerImportUniconta\Helper\FileHelper;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;

class FileService extends AbstractImport
{

    /** @var EntityRepository<ProductCollection> $productsRepository */
    private EntityRepository $productsRepository;

    /** @var SystemConfigService $systemConfigService */
    private SystemConfigService $systemConfigService;

    /** @var ImageImport $imageImport */
    private ImageImport $imageImport;

    /** @var LoggerInterface $logger */
    private LoggerInterface $logger;

    /** @var object[] $attachments */
    private array $attachments;

    /**
     * @param EntityRepository<ProductCollection> $productsRepository
     * @param SystemConfigService $systemConfigService
     * @param ImageImport $imageImport
     * @param LoggerInterface $logger
     * @param RestApi $restApi
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        EntityRepository $productsRepository,
        SystemConfigService $systemConfigService,
        ImageImport $imageImport,
        LoggerInterface $logger,
        RestApi $restApi,
        ConfigHelper $configHelper
    )
    {
        $this->productsRepository = $productsRepository;
        $this->systemConfigService = $systemConfigService;
        $this->imageImport = $imageImport;
        $this->logger = $logger;
        parent::__construct($restApi, $configHelper);
    }

    /**
     * @param ImportParameterModel $params
     * @return void
     */
    public function import(ImportParameterModel $params): void
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customFields.Supplier', strtoupper($this->systemConfigService->get('TigerImportUniconta.config.Manufacturer'))));
        $swProducts = $this->productsRepository->search($criteria, $context);
        $this->getAttachments();

        if (!$this->attachments) {
            $this->logger->alert('No found attachments from Uniconta API');
            return;
        }

        foreach ($swProducts as $swProduct) {
            $this->getProductAttachment($swProduct);
        }
    }

    public function getServiceTag(): string
    {
        return 'file';
    }

    /**
     * @param object $product
     * @return void
     */
    private function getProductAttachment(object $product): void
    {
        $productAttachments = array_filter($this->attachments, function($attachment) use ($product) {
            return $attachment->KeyStr === $product->getProductNumber();
        });

        if (!$productAttachments) {
            return;
        }

        $mediaIds = $this->iterateAttachments($productAttachments, $product);
        $fileInsert = $this->productsRepository->upsert([[
            'id' => $product->getId(),
            'media' => $mediaIds,
            'customFields' => ['attachments' => $mediaIds]
        ]], Context::createDefaultContext());

        if ($fileInsert->getErrors()) {
            $this->logger->error(json_encode($fileInsert->getErrors()));
            return;
        }

        $this->logger->info('Successfully inserted media for product number: ' . $product->getProductNumber());
    }

    /**
     * @return void
     */
    private function getAttachments(): void
    {
        $this->attachments = $this->restApi->get('Query/UserDocs?tableId=23&documentType=PDF');
    }

    /**
     * @param stdClass $productAttachment
     * @return string
     */
    private function getBase64Image(stdClass $productAttachment): string
    {
        return base64_encode($this->restApi->get("Document/{$productAttachment->TableId}/{$productAttachment->TableRowId}/{$productAttachment->RowId}"));
    }

    /**
     * @param object[] $productAttachments
     * @param object $product
     * @return array<array<string,mixed>>
     */
    private function iterateAttachments(array $productAttachments, object $product): array
    {
        $mediaIds = [];
        $i = 0;

        foreach ($productAttachments as $productAttachment) {
            $fileName = $product->getProductNumber() . FileHelper::filterFileName($productAttachment->Text ?? 'Untitled') . '_' . $i;

            $mediaId = $this->imageImport->createMediaFromBlob(
                base64_decode($this->getBase64Image($productAttachment)),
                $fileName,
                $productAttachment->DocumentType,
                'application/pdf',
                Context::createDefaultContext()
            );

            $mediaIds[] = [
                'id'      => Uuid::fromStringToHex($fileName),
                'mediaId' => $mediaId
            ];
        }

        return $mediaIds;
    }

    public function webhookImport(ImportParameterModel $params): void
    {
        // TODO: Implement webhookImport() method.
    }
}