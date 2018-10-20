<?php
/**
 * Ebizmarts_MailChimp Magento JS component
 *
 * @category    Acodesh
 * @package     Estdevs_Erply
 * @author      Acodesh Team <info@acodesh.com>
 * @copyright   Acodesh (http://acodesh.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Estdevs\Erply\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_ERPLY_CONFIG_ENABLE = 'estdevs_erply/configuration/enableerply';
    const XML_ERPLY_CUSTOMER_CODE = 'estdevs_erply/configuration/erply_customercode';
    const XML_ERPLY_USERNAME = 'estdevs_erply/configuration/erply_username';
    const XML_ERPLY_PASSWORD = 'estdevs_erply/configuration/erply_password';
    const XML_COLLECT_TIME = 'estdevs_erply/erplyProductImport/collect_time';

    // summary import
    public $totalRecords = 0;
    public $successRecords = 0;
    public $skipRecords = 0;
    public $updatedRecords = 0;


    protected $_scopeConfig;
    protected $_isEnabled;
    protected $erply_customercode;
    protected $erply_username;
    protected $erply_password;
    protected $estmessage;
    protected $erplyApi;
    protected $_importImageService;
    public function __construct(
        \Estdevs\Erply\Service\ImportImageService $importImageService,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_importImageService = $importImageService;
        $this->_isEnabled =  $this->_scopeConfig->getValue(self::XML_ERPLY_CONFIG_ENABLE);
        $this->erply_customercode =  $this->_scopeConfig->getValue(self::XML_ERPLY_CUSTOMER_CODE);
        $this->erply_username =  $this->_scopeConfig->getValue(self::XML_ERPLY_USERNAME);
        $this->erply_password =  $this->_scopeConfig->getValue(self::XML_ERPLY_PASSWORD);
    }
    public function getLastApplyTime()
    {
        $time = $this->_scopeConfig->getValue(self::XML_APPLY_TIME);
        $timeFormatted = $this->formatTime($time);

        return $timeFormatted;
    }

    /**
     * Return last collected changes time
     *
     * @return bool|string
     */
    public function getLastCollectTime()
    {
        $time = $this->_scopeConfig->getValue(self::XML_COLLECT_TIME);
        $timeFormatted = $this->formatTime($time);

        return $timeFormatted;
    }

    /**
     * Formats time and returns time-string or n/a (for null or eq.)
     *
     * @param null $time
     * @return bool|string
     */
    public function formatTime($time = null)
    {
        if (!$time) {
            $timeFormatted = 'n/a';
        } else {
            $timeFormatted = date('d-m-Y H:i:s', $time);
        }

        return $timeFormatted;
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
        $response = $this->getProducts(array('pageNo' => 1, 'recordsOnPage'=> 1));
        if(is_array($response['status']) && $response['status']['responseStatus'] == "ok"){
            $this->totalRecords = $response['status']['recordsTotal'];
            $limit = 100;
            $pages = ceil($this->totalRecords/$limit);
            for ($i=1; $i <= $pages; $i++) {
                $erplyProducts = $this->getProducts(array('pageNo' => $i, 'getStockInfo'=>1, 'recordsOnPage'=> $limit));
                $erplyProducts = $erplyProducts['records'];

                if(is_array($erplyProducts)){
                    foreach ($erplyProducts as $key => &$_erplyProduct) {
                        // echo "<pre>";
                        // print_r($_erplyProduct);
                        // continue;
                        if($_erplyProduct['active']) {
                             switch ($_erplyProduct['type']) {
                                case 'PRODUCT':
                                    # code...
                                    $this->createProduct($_erplyProduct);
                                    break;
                                
                                 case 'BUNDLE':
                                    # code...
                                    //$this->createProduct($record);
                                    break;

                                default:
                                    # code...
                                    // $this->createProduct($record);
                                    break;
                            }
                        } else {
                            $this->skipRecords++;
                        }               
                    }
                }
            }
        }
    }

    public function createProduct($_erplyProduct = null)
    {
        if($_erplyProduct == null) return;

        // Write Validate code for required value to create product

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // instance of object manager
        $attributeSetId = 4;//$this->getAttributeSet();

        $product = $objectManager->get('Magento\Catalog\Model\Product');
        if(!$product->getIdBySku($_erplyProduct["code"])) {
            $product = $objectManager->create('\Magento\Catalog\Model\Product');        
            $product->setSku($_erplyProduct['code']); // Set your sku here
            $product->setName($_erplyProduct['name']); // Name of Product
            $product->setAttributeSetId(4); // Attribute set id
            $product->setStatus(1); // Status on product enabled/ disabled 1/0
            $product->setWeight($_erplyProduct['netWeight']); // weight of product
            $product->setVisibility(4); // visibilty of product (catalog / search / catalog, search / Not visible individually)
            $product->setTaxClassId(0); // Tax class id
            $product->setTypeId('simple'); // type of product (simple/virtual/downloadable/configurable)
            $product->setPrice($_erplyProduct['price']); // price of product
            //$product->setSpecialPrice($elkoProduct->discountPrice); // price of product
            $product->setDescription($_erplyProduct['longdesc']);
            $product->setShortDescription($_erplyProduct['description']);

            $quantity = 0;
            if(isset($_erplyProduct["warehouses"])){
                foreach ($_erplyProduct["warehouses"] as $key => $_inventory) {
                    # code...
                    $quantity = $_inventory["totalInStock"];
                }
            }
            // Custom product start
            // Custom product end

            $product->setStockData(
                array(
                    'use_config_manage_stock' => 0,
                    'manage_stock' => 1,
                    'is_in_stock' => 1,
                    'qty' => (int)$quantity
                )
            );
            $product->save();
            $this->successRecords++;
            if(isset($_erplyProduct['images'])) { 
                foreach ($_erplyProduct['images'] as $key => $imagePath) {
                    $imagePath = $imagePath['largeURL']; 
                    $this->_importImageService->execute($product, $imagePath, $visible = true, $imageType = ['image', 'small_image', 'thumbnail']);  
                    $product->save();
                }
            }
        } else {
            $this->skipRecords++;
        }
    }


    /**
     *  Import Customer
     * @param $path
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setCustomers($customers, $storeId = null, $scope = null)
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


