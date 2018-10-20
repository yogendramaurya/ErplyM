<?php
namespace Estdevs\Erply\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_ERPLY_CONFIG_ENABLE = 'estdevs_erply/configuration/enableerply';
    const XML_ERPLY_CUSTOMER_CODE = 'estdevs_erply/configuration/erply_customercode';
    const XML_ERPLY_USERNAME = 'estdevs_erply/configuration/erply_username';
    const XML_ERPLY_PASSWORD = 'estdevs_erply/configuration/erply_password';

    protected $_scopeConfig;
    protected $_isEnabled;
    protected $erply_customercode;
    protected $erply_username;
    protected $erply_password;
    protected $estmessage;
    protected $erplyApi;
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_isEnabled =  $this->_scopeConfig->getValue(self::XML_ERPLY_CONFIG_ENABLE);
        $this->erply_customercode =  $this->_scopeConfig->getValue(self::XML_ERPLY_CUSTOMER_CODE);
        $this->erply_username =  $this->_scopeConfig->getValue(self::XML_ERPLY_USERNAME);
        $this->erply_password =  $this->_scopeConfig->getValue(self::XML_ERPLY_PASSWORD);
    }

    public function isenable()
    {
        return  $this->_isEnabled;
    }

    public function getCustomercode()
    {
        return $this->erply_customercode;
    }

    public function getUsername()
    {
        return $this->erply_username;
    }
        public function getPassword()
    {
        return $this->erply_password;
    }
    public function erplyApi()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $api     = $objectManager->create("Estdevs\Erply\Service\EapiService");
        $api->clientCode = $this->getCustomercode();//501692;
        $api->username = $this->getUsername();//"devopsheros@gmail.com";
        $api->password = $this->getPassword(); //"Admin123#";
        $api->url = "https://".$api->clientCode.".erply.com/api/";

        return $api;
    }

    
    public function getProducts($parameters = array())
    {
        $products = [];
        $api = $this->erplyApi();
        $response = $api->sendRequest("getProducts", $parameters);
        if($response) {
            $products  = json_decode($response, true);
        }

        return $products;
    }

    public function getCustomers($parameters = array())
    {
        $customers = [];
        $response = $api->sendRequest("getCustomers", $parameters);
        if($response) {
            $customers  = json_decode($response, true);
        }

        return $customers;
    }


    /***** Import Data **********/
    /**
     * @param $path
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setProducts($products, $storeId = null, $scope = null)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // instance of object manager
        //$attributeSetId = $this->getAttributeSet();

        if(is_array($products)){
            foreach ($products as $erplyProduct){
                print_r($erplyProduct); 
                // if($erplyProduct->code) {
                //     print_r($erplyProduct); 
                // } 
            }
        } 
    }

    /**
     *  Import Customer
     * @param $path
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setProdCustomers($customers, $storeId = null, $scope = null)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // instance of object manager
        //$attributeSetId = $this->getAttributeSet();

        if(is_array($products)){
            foreach ($products as $erplyProduct){
                print_r($erplyProduct); 
                // if($erplyProduct->code) {
                //     print_r($erplyProduct); 
                // } 
            }
        } 
    }
}


