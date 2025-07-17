<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Model;

class DocumentModel
{
    private int $tableId;
    private int $tableRowId;
    private int $rowId;
    private string $documentType;
    private string $fileName;
    private string $productNumber;
    private ?string $url;

    public function __construct(\stdClass $document)
    {
        if (!empty($document->TableId) && !empty($document->TableRowId) && !empty($document->RowId) && !empty($document->DocumentType)) {
            $this->tableId = $document->TableId;
            $this->rowId = $document->RowId;
            $this->tableRowId = $document->TableRowId;
            $this->documentType = $document->DocumentType;
            $this->fileName = $document->Text ?? 'Untitled';
            $this->productNumber = $document->KeyStr ?? '';
            $this->url = $document->Url;
        }
    }

    public function getQueryString(): string
    {
        return "Document/{$this->tableId}/{$this->tableRowId}/{$this->rowId}";
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return string
     */
    public function getProductNumber(): string
    {
        return $this->productNumber;
    }

    /**
     * @return int
     */
    public function getTableRowId(): int
    {
        return $this->tableRowId;
    }

    /**
     * @return int
     */
    public function getRowId(): int
    {
        return $this->rowId;
    }

    /**
     * @return string
     */
    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    /**
     * @return int
     */
    public function getTableId(): int
    {
        return $this->tableId;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
