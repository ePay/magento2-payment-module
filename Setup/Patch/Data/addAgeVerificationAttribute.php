<?php
 
namespace Epay\Payment\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class addAgeVerificationAttribute implements DataPatchInterface
{
    // @var ModuleDataSetupInterface
    
    private $moduleDataSetup;
    
    // @var EavSetupFactory
    
    private $eavSetupFactory;

    // @param ModuleDataSetupInterface $moduleDataSetup
    // @param EavSetupFactory $eavSetupFactory
        
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) 
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    //Add eav attributes
    public function apply()
    {
        // @var EavSetup $eavSetup
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

            $eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                'ageVerification',
                [
                    'type'         => 'int',
                    'label'        => 'Aldersvalidering',
                    'input'        => 'select',
                    'required'     => false,
                    'visible'      => true,
                    'user_defined' => false,
                    'used_in_product_listing' => true,
                    'default'      => '',
                    'source'       => 'Epay\Payment\Model\Config\Source\AgeVerificationOptions',
                    'global'       => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'group'        => 'General',
                ]
            );    
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}

?>
