<?php

$this->startSetup();

$yehhpayTransactionIdField= [
    'yehhpay_transaction_id' => 'Yehhpay Transaction Id'
];

foreach ($yehhpayTransactionIdField as $code => $label) {
    if (!$this->getConnection()->tableColumnExists($this->getTable('sales/order_payment'), $code)) {
        $this->getConnection()->addColumn(
            $this->getTable('sales/order_payment'),
            $code,
            array(
                'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
                'unsigned' => true,
                'length' => '255',
                'nullable' => true,
                'comment' => $label,
            )
        );
    }
}

$yehhpayTransactionDateField= [
    'yehhpay_transaction_date' => 'Yehhpay Resume Transaction Date'
];

foreach ($yehhpayTransactionDateField as $code => $label) {
    if (!$this->getConnection()->tableColumnExists($this->getTable('sales/order_payment'), $code)) {
        $this->getConnection()->addColumn(
            $this->getTable('sales/order_payment'),
            $code,
            array(
                'type' => Varien_Db_Ddl_Table::TYPE_DATE,
                'unsigned' => true,
                'nullable' => true,
                'comment' => $label
            )
        );
    }
}

$this->endSetup();