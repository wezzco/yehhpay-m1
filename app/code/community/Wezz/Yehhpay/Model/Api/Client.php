<?php

/**
 * Use Zend XmlRpc Client for API connection
 */
use Zend_XmlRpc_Client as XmlRpc;

/**
 * Class for working with Yehhpay Api
 *
 * Class Wezz_Yehhpay_Model_Api_Client
 */
class Wezz_Yehhpay_Model_Api_Client
{
    /**
    **
    * Test application endpoint
    */
    const TEST_APPLICATION_ENDPOINT = 'https://api-test.yehhpay.nl/xmlrpc/merchant';

    /**
     **
     * Live application endpoint
     */
    const LIVE_APPLICATION_ENDPOINT = 'https://api.yehhpay.nl/xmlrpc/merchant';

    /**
     * Method for call api
     *
     * @param $method - api method
     * @param $data - data structure for api
     * @return mixed
     */
    public function callApi($method, $data, $create = false)
    {

        if ($create) {
            $apiData['applicationKey'] = $this->getApplicationKey();
            $apiData['applicationSecret'] = $this->getApplicationSecret();
            $apiData['serviceIdentifier'] = $this->getServiceIdentifier();

            foreach ($data as $key => $dataItem) {
                $apiData[$key] = $dataItem;
            }
        } else {
            $apiData[] = $this->getApplicationKey();
            $apiData[] = $this->getApplicationSecret();

            if (is_array($data)) {
                foreach ($data as $dataItem) {
                    $apiData[] = $dataItem;
                }
            } else {
                $apiData[] = $data;
            }
        }

        try {
            $connection = new XmlRpc($this->getApplicationEndPoint());
            $result = $connection->call($method, $apiData);

            return $result;


        } catch (Mage_Core_Exception $e) {
            $response = $e->getMessage();
            return $response;
        } catch (Exception $e) {
            $response = Mage::helper('wezz_yehhpay/data')->__("Error: System error during request");
            return $response;
        }

    }

    /**
     * Get application key
     *
     * @return mixed
     */
    private function getApplicationKey()
    {
        return Mage::getStoreConfig(
            'yehhpay/yehhpay_group/application_key',
            Mage::app()->getStore());
    }

    /**
     * Get application secret
     *
     * @return mixed
     */
    private function getApplicationSecret()
    {
        return Mage::getStoreConfig(
            'yehhpay/yehhpay_group/application_secret',
            Mage::app()->getStore()
        );
    }

    private function getServiceIdentifier()
    {
        return Mage::getStoreConfig(
            'yehhpay/yehhpay_group/service_identifier',
            Mage::app()->getStore()
        );
    }

    /**
     * Get application mode
     *
     * @return mixed
     */
    private function getApplicationMode()
    {
        return Mage::getStoreConfig(
            'yehhpay/yehhpay_group/payment_mode',
            Mage::app()->getStore()
        );
    }

    /**
     * Get application endpoint
     *
     * @return string
     */
    private function getApplicationEndPoint()
    {
        if ($this->getApplicationMode()) {
            return self::LIVE_APPLICATION_ENDPOINT;
        } else {
            return self::TEST_APPLICATION_ENDPOINT;
        }
    }
}