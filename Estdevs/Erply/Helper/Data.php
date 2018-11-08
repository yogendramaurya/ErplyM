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
    protected $customerFactory;
    protected $storeManager;
    protected $addressFactory;
    protected $_configWriter;
    protected $_storeManager;
    public $mapcat = array();

    public function __construct(
        \Estdevs\Erply\Service\ImportImageService $importImageService,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        //\Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_importImageService = $importImageService;
        $this->storeManager     = $storeManager;
        $this->customerFactory  = $customerFactory;
        $this->_isEnabled =  $this->_scopeConfig->getValue(self::XML_ERPLY_CONFIG_ENABLE);
        $this->erply_customercode =  $this->_scopeConfig->getValue(self::XML_ERPLY_CUSTOMER_CODE);
        $this->erply_username =  $this->_scopeConfig->getValue(self::XML_ERPLY_USERNAME);
        $this->erply_password =  $this->_scopeConfig->getValue(self::XML_ERPLY_PASSWORD);
        $this->_configWriter = $configWriter;
        //$this->_storeManager = $storeManager;
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

     /***** Import Data **********/
    /**
     * @param $path
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importProducts($page = 1, $type = 0)
    {
        if($type > 0) {
            $erplyProducts = $this->getProducts(array('pageNo' => $page, 'type' => "BUNDLE", 'getStockInfo'=>1, 'recordsOnPage'=> $this->getLimit()));
        } else {
            $erplyProducts = $this->getProducts(array('pageNo' => $page, 'type' => "PRODUCT", 'getStockInfo'=>1, 'recordsOnPage'=> $this->getLimit()));
            
        }
        $this->mapcat =  $this->getMappedCategory();       
        $erplyProducts = $erplyProducts['records'];

        if(is_array($erplyProducts)){
            foreach ($erplyProducts as $key => &$_erplyProduct) {
                if($_erplyProduct['active']) {
                    $this->createProduct($_erplyProduct);
                } else {
                    $this->skipRecords++;
                }              
            }
        }

        $response['totalRecords'] = $this->totalRecords;
        $response['successRecords'] = $this->successRecords;
        $response['skipRecords'] = $this->skipRecords + $this->updatedRecords;

        return $response;
    }

    /***** Import Data **********/
    /**
     * @param $path
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setProducts($cli = null)
    {
        $this->mapcat =  $this->getMappedCategory();
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
                        if($cli !== null) { echo ".";}
                        if($_erplyProduct['active']) {
                            echo "<pre>";
                            print_r($_erplyProduct);
                            $this->createProduct($_erplyProduct);
                        } else {
                            $this->skipRecords++;
                        }              
                    }
                }
            }
        }

        $response['totalRecords'] = $this->totalRecords;
        $response['successRecords'] = $this->successRecords;
        $response['skipRecords'] = $this->skipRecords + $this->updatedRecords;
        if($cli !== null) {
            echo "\n-----------------------------------------\n";
            echo "Total Records : $this->totalRecords \n";
            echo "Successfully imported : $this->successRecords \n";
            echo "Skip Records due to already exists : $this->skipRecords \n";
            echo "-----------------------------------------\n";
        }

        return $response;
    }

    public function createProduct($_erplyProduct = null)
    {
        if($_erplyProduct == null) return;

        // Write Validate code for required value to create product

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // instance of object manager
        $attributeSetId = 4;//$this->getAttributeSet();
        $websiteId = $this->getWebsite();
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
            $product->setWebsiteIds($websiteId);
            if($_erplyProduct['type'] == "BUNDLE") {
                $product->setTypeId('bundle');
            } else {
                $product->setTypeId('simple'); // type of product (simple/virtual/downloadable/configurable)     
            }
            $product->setPrice($_erplyProduct['priceWithVat']); // price of product
            $product->setUrlKey($_erplyProduct['code'].strtotime('now')); 
            $product->setDescription($_erplyProduct['longdesc']);
            $product->setShortDescription($_erplyProduct['description']);

            #get catgory ID
            $categoriesIds = array(2);
            if(isset($_erplyProduct['categoryID']) && $_erplyProduct['categoryID'] > 0){
                $cId = $_erplyProduct['categoryID'];
                if(array_key_exists($cId, $this->mapcat))
                {
                    //$this->mapcat[$cId];
                    array_push($categoriesIds, $this->mapcat[$cId]);
                }
            }
            $product->setCategoryIds($categoriesIds);



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

    public function getCustomers($parameters = array())
    {
        $customers = [];
        $api = $this->erplyApi();
        $response = $api->sendRequest("getCustomers", $parameters);
        if($response) {
            $customers  = json_decode($response, true);
        }

        return $customers;
    }

    /**
     *  Import Customer
     * @param $path
     * @param null $storeId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setCustomers($cli = null)
    {
        $response = $this->getCustomers(array('pageNo' => 1, 'recordsOnPage'=> 1));
        if(is_array($response['status']) && $response['status']['responseStatus'] == "ok"){
            $this->totalRecords = $response['status']['recordsTotal'];
            $limit = 100;
            $pages = ceil($this->totalRecords/$limit);
            for ($i=1; $i <= $pages; $i++) {
                $erplyCustomers = $this->getCustomers(array('pageNo' => $i, 'recordsOnPage'=> $limit));
                $erplyCustomers = $erplyCustomers['records'];

                if(is_array($erplyCustomers)){
                    foreach ($erplyCustomers as $key => &$_erplyCustomer) {
                        if($cli !== null) { echo ".";}
                        if(!isset($_erplyCustomer['email'])||empty($_erplyCustomer['email'])||empty($_erplyCustomer['firstName'])||empty($_erplyCustomer['lastName']) || $_erplyCustomer['email'] ==' '){
                            return;
                        }
                         $this->createCustomer($_erplyCustomer);   
                         break;           
                    }
                }
            }
        }

        $response['totalRecords'] = $this->totalRecords;
        $response['successRecords'] = $this->successRecords;
        $response['skipRecords'] = $this->skipRecords + $this->updatedRecords;
        if($cli !== null) {
            echo "\n-----------------------------------------\n";
            echo "Total Records : $this->totalRecords \n";
            echo "Successfully imported : $this->successRecords \n";
            echo "Skip Records due to already exists : $this->skipRecords \n";
            echo "-----------------------------------------\n";
        }

        return $response;
    }

    public function createCustomer($record)
    {
        $email = $record['email'];
        $firstName = $record['firstName'] == "" ? $record['fullName'] : $record['firstName'];
        $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();
        $customer = $customerFactory->setWebsiteId($websiteId)->loadByEmail($email);
        if ($customer->getId()) {
             $this->skipRecords++;
                return; 
        } else {
            $data = [
                'firstname' => $firstName,
                'lastname' => $record['lastName'],
                'email' => $record['email'],
                'password' =>'123456789',
                'erply_customerID' =>$record['customerID'],
                'erply_type_id' =>$record['type_id'],
                'erply_companyName' =>$record['companyName'],
                'erply_groupID' =>$record['groupID'],
                'erply_countryID' =>$record['countryID'],
                'erply_payerID' =>$record['payerID'],
                'erply_phone' =>$record['phone'],
                'erply_mobile' =>$record['mobile'],
                'erply_fax' =>$record['fax'],
                'erply_code' =>$record['code'],
                'erply_birthday' =>$record['birthday'],
                'erply_integrationCode' =>$record['integrationCode'],
                'erply_flagStatus' =>$record['flagStatus'],
                'erply_colorStatus' =>$record['colorStatus'],
                'erply_credit' =>$record['credit'],
                'erply_salesBlocked' =>$record['salesBlocked'],
                'erply_referenceNumber' =>$record['referenceNumber'],
                'erply_customerCardNumber' =>$record['customerCardNumber'],
                'erply_customerType' =>$record['customerType'],
                'erply_addressTypeID' =>$record['addressTypeID'],
                'erply_addressTypeName' =>$record['addressTypeName'],
                'erply_isPOSDefaultCustomer' =>$record['isPOSDefaultCustomer'],
                'erply_euCustomerType' =>$record['euCustomerType'],
                'erply_lastModifierUsername' =>$record['lastModifierUsername'],
                'erply_lastModifierUsername' =>$record['lastModifierUsername'],
                'erply_lastModifierEmployeeID' =>$record['lastModifierEmployeeID'],
                'erply_paysViaFactoring' =>$record['paysViaFactoring'],
                'erply_rewardPoints' =>$record['rewardPoints'],
                'erply_twitterID' =>$record['twitterID'],
                'erply_facebookName' =>$record['facebookName'],
                'erply_creditCardLastNumbers' =>$record['creditCardLastNumbers'],
                'erply_deliveryTypeID' =>$record['deliveryTypeID'],
                'erply_image' =>$record['image'],
                'erply_rewardPointsDisabled' =>$record['rewardPointsDisabled'],
                'erply_posCouponsDisabled' =>$record['posCouponsDisabled'],
                'erply_emailOptOut' =>$record['emailOptOut'],
                'erply_signUpStoreID' =>$record['signUpStoreID'],
                'erply_homeStoreID' =>$record['homeStoreID'],
            ];

            
            $customer->setFirstname($firstName);
            $customer->setLastname($record['lastName']);
            $customer->setEmail($record['email']);

            try {
                $customer->save();
                $this->setcustomerAddress($customer->getId(), $record, $objectManager);
                $this->successRecords++;
            } catch (Exception $e) {
                $this->skipRecords++;
                return;
            }
        }
        unset($customer);
    }

    public function setcustomerAddress($id,$record, $objectManager) {
      
        $street = array(
            '0' => $record['street'], 
            '1' => $record['address'] 
        );
        $customerAddress =  $objectManager->get('\Magento\Customer\Model\AddressFactory')->create();
        $customerAddress->setCustomerId($id, $record)
            ->setFirstname($record['firstName'])
            ->setLastname($record['lastName'])
            ->setCountryId('US')
            ->setPostcode($record['postalCode'])
            ->setCity($record['city'])
            ->setTelephone($record['phone']?:'12345712')
            ->setFax($record['fax'])
            ->setCompany($record['companyName'])
            ->setStreet($street)
            ->setIsDefaultBilling('1')
            ->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1');

        $customerAddress->save();
        return true;
    }

    public function syncProductCategory()
    {
        $customers = [];
        $api = $this->erplyApi();
        $response = $api->sendRequest("getProductCategories");
        if($response) {
            $customers  = json_decode($response, true);
            $customers = $customers["records"];
        }

        return $customers;
    }

    public function saveErplyCateoryMap($value)
    {
        
        $this->_configWriter->save('erply/mapcategory/mapcategory', $value, "default", 0);
        
        return $this;
    }
    public function getMappedCategory()
    {
        $value = $this->_scopeConfig->getValue("erply/mapcategory/mapcategory");
       
        $json = json_decode($value);
        $category = [];
        foreach($json as $jsonObj){
            $k = $jsonObj->id;
            $category["$k"] = $jsonObj->mcat;
        }
        // print_r($category);
        return $category;
    }
    public function getLimit(){
        return 2;
    }
    public function getWebsite()
    {
      // $stores = $this->_storeManager->getWebsites(true, false);
        $websiteIds = array(1);
        // foreach ($stores as $store) {
        //     $websiteId = $store["website_id"];
        //     array_push($websiteIds, $websiteId);
        // }
        // print_r($stores);
        return $websiteIds; 
    }
}


