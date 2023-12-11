<?php
$installer = $this;
$installer = new Mage_Sales_Model_Resource_Setup('core_setup');
$connection = $installer->getConnection();

$table = $installer->getTable('review/review_detail');
if (!$connection->tableColumnExists($table, 'order_number')) {
    $connection->addColumn($table, 'order_number', array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'nullable' => false,
        'length' => 15,
        'default' => 0,
        'after' => 'customer_id',
        'comment' => 'Order Number'
    ));
}

$installer->endSetup();