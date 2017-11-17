<?php

/**
 * Class Wezz_Yehhpay_Helper_Mode
 */
class Wezz_Yehhpay_Helper_Mode
{
    public function toOptionArray()
    {
        return array(
            array('value' => 1, 'label'=> Mage::helper('wezz_yehhpay/data')->__("Live")),
            array('value' => 0, 'label' => Mage::helper('wezz_yehhpay/data')->__("Test"))
        );
    }
}