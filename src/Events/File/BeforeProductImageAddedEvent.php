<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Events\File;

use Symfony\Contracts\EventDispatcher\Event;
use TigerMedia\General\TigerImportUniconta\Model\DocumentModel;

class BeforeProductImageAddedEvent extends Event
{
    /** @var DocumentModel[] $productImages */
    protected array $productImages;
    protected ?int $coverPhoto;

    /**
     * @param DocumentModel[] $productImages
     */
    public function __construct(
        array $productImages,
        ?int $coverPhoto
    )
    {
        $this->productImages = $productImages;
        $this->coverPhoto = $coverPhoto;
    }

    /**
     * @return DocumentModel[]
     */
    public function getProductImages(): array
    {
        return $this->productImages;
    }

    /**
     * @param DocumentModel[] $productImages
     * @return void
     */
    public function setProductImages(array $productImages): void
    {
        $this->productImages = $productImages;
    }

    public function getCoverPhoto(): ?int
    {
        return $this->coverPhoto;
    }
}