<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\Service\AbstractImport;

#[AsCommand(
    name: 'tiger-import-uniconta:product-import',
    description: 'Import products from Uniconta',
)]
class ImportProduct extends Command
{

    /** @var AbstractImport $productService */
    private AbstractImport $productService;

    /**
     * @param AbstractImport $productService
     */
    public function __construct(
        AbstractImport $productService
    )
    {
        $this->productService = $productService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('productNumber', InputArgument::OPTIONAL, 'Specify single product to import.');
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Executing product import...');

        $this->productService->import(
            (new ImportParameterModel())->setProductNumber($input->getArgument('productNumber'))
        );

        $io->success('Successfully executed product import.');
        return Command::SUCCESS;
    }
}