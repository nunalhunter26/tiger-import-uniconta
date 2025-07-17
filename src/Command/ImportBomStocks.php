<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\Service\AbstractImport;
use TigerMedia\General\TigerImportUniconta\Service\BundleService;

#[AsCommand(
    name: 'tiger-import-uniconta:bom-stocks-import',
    description: 'Import BOM stocks from Uniconta',
)]
class ImportBomStocks extends Command
{
    private AbstractImport $bundleStockService;

    public function __construct(AbstractImport $bundleStockService, ?string $name = null)
    {
        $this->bundleStockService = $bundleStockService;
        parent::__construct($name);
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Executing BOM Stocks import...');
        $this->bundleStockService->import(new ImportParameterModel());
        $io->success('Successfully executed BOM Stocks import!');
        return Command::SUCCESS;
    }
}