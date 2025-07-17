<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Helper;

use Doctrine\DBAL\Exception;
use Shopware\Core\Kernel;

class ProductHelper
{

    /**
     * @return string[]
     * @throws Exception
     */
    public static function getShopwareProducts(): array
    {
        $connection = Kernel::getConnection();
        return $connection->executeQuery("SELECT product_number, LOWER(hex(id)) FROM `product`")->fetchAllKeyValue();
    }
}