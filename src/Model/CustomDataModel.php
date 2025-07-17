<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Model;

class CustomDataModel
{
    private mixed $data = [];

    public function getData(): mixed
    {
        return $this->data;
    }

    public function add(string $key, mixed $data): void
    {
        $this->data[$key] = $data;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }
}