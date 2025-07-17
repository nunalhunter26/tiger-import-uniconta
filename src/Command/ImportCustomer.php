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
    name: 'tiger-import-uniconta:customer-import',
    description: 'Import customers from Uniconta',
)]
class ImportCustomer extends Command
{

    /** @var AbstractImport $customerService */
    private AbstractImport $customerService;

    /**
     * @param AbstractImport $customerService
     */
    public function __construct(
        AbstractImport $customerService
    )
    {
        $this->customerService = $customerService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('customerNumber', InputArgument::OPTIONAL, 'Specify single customer to import.');
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Executing customer import...');

        $this->customerService->import(
            (new ImportParameterModel())->setCustomerNumber($input->getArgument('customerNumber'))
        );

        $io->success('Successfully executed customer import.');
        return Command::SUCCESS;
    }
}