<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace UniPAYPaymentGateway\Unipay\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
    

        $installer = $setup;
        $installer->startSetup();
        $connection = $installer->getConnection();

        if ($connection->tableColumnExists('sales_order', 'unipay_transaction') === false) {
            $connection
            ->addColumn(
                $setup->getTable('sales_order'),
                'unipay_transaction',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 32,
                    'comment' => 'UniPAY Transaction'
                ]
            );
        }

        if ($connection->tableColumnExists('sales_order_grid', 'unipay_transaction') === false) {
            $connection
            ->addColumn(
                $setup->getTable('sales_order_grid'),
                'unipay_transaction',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 32,
                    'comment' => 'UniPAY Transaction'
                ]
            );
        }
        $installer->endSetup();
    }
}
