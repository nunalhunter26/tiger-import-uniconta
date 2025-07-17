<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\Service\ProductPriceStockService;

#[AsCommand(
    name: 'tiger-import-uniconta:product-price-stock-import',
    description: 'Import product price and stock from Uniconta'
)]
class ImportProductPriceStock extends Command
{
    private ProductPriceStockService $productPriceStockService;

    public function __construct(
        ProductPriceStockService $productPriceStockService,
        ?string $name = null
    )
    {
        $this->productPriceStockService = $productPriceStockService;
        parent::__construct($name);
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Executing import...');
        $this->productPriceStockService->import(new ImportParameterModel());
        $io->success('Successfully executed import!');
        return Command::SUCCESS;
    }
}