<?php declare(strict_types=1);

namespace TigerMedia\General\TigerImportUniconta;

use Exception;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetCollection;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TigerMedia\Base\Core\Framework\Log\TigerLoggerCompilerPass;
use TigerMedia\Base\Core\Framework\TigerPlugin;

class TigerImportUniconta extends TigerPlugin
{
    const CONFIG_TASK_LAST_RUN_SUFFIX = 'TaskLastRun';
    const CONFIG_TASK_INTERVAL_SUFFIX = 'TaskInterval';
    const CONFIG_TASK_IS_PARTIAL = 'TaskIsPartial';
    const CONFIG_TASK_IS_ENABLED_SUFFIX = 'TaskIsEnabled';
    const CONFIG_IMPORT_BLOCK_START_TIME = 'importBlockStartTime';
    const CONFIG_IMPORT_BLOCK_END_TIME = 'importBlockEndTime';
    const ROOT_CONFIG_PREFIX = 'TigerImportUniconta.config.';

    /**
     * @throws Exception
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new TigerLoggerCompilerPass('tigermedia.tigeruniconta', [
            'tigeruniconta.webhook',
            'tigeruniconta.api',
            'tigeruniconta.product'
        ]));
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->createCustomFieldSets($this->container->get('custom_field_set.repository'), $activateContext);
        parent::activate($activateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $this->removeCustomFieldSets(
            $this->container->get('custom_field_set.repository'),
            Uuid::fromStringToHex('TigerImportUnicontaProductCustomFieldSet'),
            Uuid::fromStringToHex('TigerImportUnicontaCustomerCustomFieldSet'),
            $uninstallContext->getContext()
        );

        parent::uninstall($uninstallContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $this->removeCustomFieldSets(
            $this->container->get('custom_field_set.repository'),
            Uuid::fromStringToHex('TigerImportUnicontaProductCustomFieldSet'),
            Uuid::fromStringToHex('TigerImportUnicontaCustomerCustomFieldSet'),
            $deactivateContext->getContext()
        );

        parent::deactivate($deactivateContext);
    }

    /**
     * @param EntityRepository<CustomFieldSetCollection> $customFieldSetRepository
     * @param ActivateContext $activateContext
     * @return void
     */
    private function createCustomFieldSets(EntityRepository $customFieldSetRepository, ActivateContext $activateContext): void
    {
        $this->removeCustomFieldSets(
            $customFieldSetRepository,
            Uuid::fromStringToHex('TigerImportUnicontaProductCustomFieldSet'),
            Uuid::fromStringToHex('TigerImportUnicontaCustomerCustomFieldSet'),
            $activateContext->getContext()
        );

        $customFieldSetRepository->upsert([
            [
                'id'     => Uuid::fromStringToHex('TigerImportUnicontaProductCustomFieldSet'),
                'name'   => 'tigerimportuniconta_product_field_set',
                'active' => true,
                'config' => [
                    'label' => [
                        'en-GB' => 'TigerImportUniconta Product Fields'
                    ]
                ],
                'customFields' => $this->getProductCustomFields(),
                'relations'    => [
                    [
                        'id'         => Uuid::randomHex(),
                        'entityName' => ProductDefinition::ENTITY_NAME
                    ]
                ]
            ],
            [
                'id'     => Uuid::fromStringToHex('TigerImportUnicontaCustomerCustomFieldSet'),
                'name'   => 'tigerimportuniconta_customer_field_set',
                'active' => true,
                'config' => [
                    'label' => [
                        'en-GB' => 'TigerImportUniconta Customer Fields'
                    ]
                ],
                'customFields' => $this->getCustomerCustomFields(),
                'relations'    => [
                    [
                        'id'         => Uuid::randomHex(),
                        'entityName' => CustomerDefinition::ENTITY_NAME
                    ]
                ]
            ]
        ], $activateContext->getContext());
    }

    /**
     * @param EntityRepository<CustomFieldSetCollection> $customFieldSetRepository
     * @param string $productSetId
     * @param string $customerSetId
     * @param Context $context
     * @return void
     */
    private function removeCustomFieldSets(EntityRepository $customFieldSetRepository, string $productSetId, string $customerSetId, Context $context): void
    {
        $customFieldSetRepository->delete([
            ['id' => $productSetId],
            ['id' => $customerSetId]
        ], $context);
    }

    /**
     * @return mixed[]
     */
    private function getProductCustomFields(): array
    {
        return [
            [
                'id'     => Uuid::fromStringToHex('TigerImportUnicontaProductSupplierName'),
                'name'   => 'supplierName',
                'type'   => CustomFieldTypes::TEXT,
                'config' => [
                    'label'    => [
                        'en-GB' => 'Supplier Name'
                    ],
                    'customFieldPosition' => 0
                ]
            ],
            [
                'id'     => Uuid::fromStringToHex('TigerImportUnicontaProductProductGroup'),
                'name'   => 'productgroup',
                'type'   => CustomFieldTypes::TEXT,
                'config' => [
                    'label' => [
                        'en-GB' => 'Product Group'
                    ],
                    'customFieldPosition' => 1
                ]
            ],
            [
                'id'     => Uuid::fromStringToHex('TigerImportUnicontaProductDiscountGroup'),
                'name'   => 'product_discountgroup',
                'type'   => CustomFieldTypes::TEXT,
                'config' => [
                    'label' => [
                        'en-GB' => 'Discount Group'
                    ],
                    'customFieldPosition' => 2
                ]
            ]
        ];
    }

    /**
     * @return mixed[]
     */
    private function getCustomerCustomFields(): array
    {
        return [
            [
                'id'     => Uuid::fromStringToHex('TigerImportUnicontaCustomerDiscountGroup'),
                'name'   => 'customer_discountgroup',
                'type'   => CustomFieldTypes::TEXT,
                'config' => [
                    'label' => [
                        'en-GB' => 'Discount Group'
                    ],
                    'customFieldPosition' => 0
                ]
            ]
        ];
    }
}