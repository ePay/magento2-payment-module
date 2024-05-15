<?php

declare(strict_types=1);

namespace Epay\Payment\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class UpdateConfiguration implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * UpdateConfiguration Data Patch constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }
    /**
     * Apply the data patch.
     */
    public function apply()
    {
        $this->updateConfiguration();
    }
    /**
     * Revert the data patch.
     */
    public function revert()
    {
        return true;
    }
    /**
     * Retrieve aliases for the data patch.
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
    /**
     * Retrieve dependencies for the data patch.
     *
     * @return array
     */

    public static function getDependencies()
    {
        return [];
    }
    /**
     * Update the config Values in core_config_data table
     */
    private function updateConfiguration()
    {
        $configTable = $this->moduleDataSetup->getTable('core_config_data');
        $connection = $this->moduleDataSetup->getConnection();
        $selectOldConfig = $connection->select()->from($configTable)
            ->where('path LIKE ?', '%payment/bambora_epay%');
        $oldConfigValues = $connection->fetchAll($selectOldConfig);
        $dataToInsert = [];
        foreach ($oldConfigValues as $oldConfig) {
            if ($oldConfig['path'] === 'payment/bambora_epay/active' || $oldConfig['path'] === 'payment/bambora_epay/title') {
                continue;
            }
            $newPath = str_replace('payment/bambora_epay', 'payment/epay', $oldConfig['path']);
            $dataToInsert[] = [
                'scope' => 'default',
                'scope_id' => $oldConfig['scope_id'],
                'path' => $newPath,
                'value' => $oldConfig['value']
            ];
        }
        if (!empty($dataToInsert)) {
            $connection->insertOnDuplicate($configTable, $dataToInsert, ['scope', 'scope_id', 'path']);
        }
    }
}
