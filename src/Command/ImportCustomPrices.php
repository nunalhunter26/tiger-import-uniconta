<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta\Command;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TigerMedia\General\TigerImportUniconta\Model\ImportParameterModel;
use TigerMedia\General\TigerImportUniconta\Service\AbstractImport;

#[AsCommand(
    name: 'tiger-import-uniconta:custom-prices-import',
    description: 'Import custom prices from Uniconta',
)]
class ImportCustomPrices extends Command
{
    /** @var AbstractImport $customPriceService */
    private AbstractImport $customPriceService;

    /**
     * @param AbstractImport $customPriceService
     */
    public function __construct(
        AbstractImport $customPriceService
    )
    {
        $this->customPriceService = $customPriceService;
        parent::__construct();
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Executing Custom Prices import..');
        return $this->executeCustomPricesImport($io);
    }

    private function executeCustomPricesImport(SymfonyStyle $io): int
    {
        try {
            $this->customPriceService->import(new ImportParameterModel());
            $io->success('Successfully executed Custom Prices import.');
        } catch (Exception $exception) {
            $io->error($exception->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}