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
      //  \Magento\Customer\Model\Session $customerSession
    ) {
        $this->_scopeConfig = $scopeConfig;
        // $this->customerSession = $customerSession;
        $this->_isEnabled =  $this->_scopeConfig->getValue(self::XML_ERPLY_CONFIG_ENABLE);
        $this->erply_customercode =  $this->_scopeConfig->getValue(self::XML_ERPLY_CUSTOMER_CODE);
        $this->erply_username =  $this->_scopeConfig->getValue(self::XML_ERPLY_USERNAME);
        $this->erply_password =  $this->_scopeConfig->getValue(self::XML_ERPLY_PASSWORD);

        // $this->erplyApi = new EAPI();
    }

    public function getErply()
    {
       // $this->erplyApi = new EAPI();
    }

    public function getProductsApi()
    {
        $api = new EAPI();
        $api->clientCode = 501692;
        $api->username = "devopsheros@gmail.com";
        $api->password = "Admin123#";
        $api->url = "https://".$api->clientCode.".erply.com/api/";

        return $api;

        // Get client groups from API
        // No input parameters are needed
       // $result = $api->sendRequest("getProducts", array());

        // Default output format is JSON, so we'll decode it into a PHP array
       //return $output = json_decode($result, true);
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

}


