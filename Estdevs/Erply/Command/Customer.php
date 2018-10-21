<?php
namespace Estdevs\Erply\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Customer extends Command
{
    
    private $state;
    protected $totalRecords;
    private $_objectManager;
    private $_helperData;
    protected $directoryList;
    protected $customerFactory;
    protected $storeManager;
    // result
    protected $skip = 0;
    protected $success = 0;
    private $update = 0;

    /**
     * File interface
     *
     * @var File
     */
    protected $file;


    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $addressFactory;


    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $file,
        \Estdevs\Erply\Helper\Data $helperData,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Magento\Customer\Model\AddressFactory $addressFactory
       ) {
            $this->state = $state;
            $this->_objectManager = $objectmanager;
            $this->_helperData = $helperData;
            $this->storeManager     = $storeManager;
            $this->customerFactory  = $customerFactory;
            $this->file = $file;
            $this->addressFactory = $addressFactory;
            parent::__construct();
    }

    protected function configure()
    {
        $this->setName("acodesh:importcustomer");
        $this->setDescription("Download Customer and update in magento.");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->_helperData = $this->_objectManager->get('Estdevs\Erply\Helper\Data');
        $this->directoryList = $this->_objectManager->get('\Magento\Framework\App\Filesystem\DirectoryList');
        if ($this->_helperData->getCustomercode() == null || $this->_helperData->getUsername() == null || $this->_helperData->getPassword() == null) {
            $output->writeln("Please add correct detail on configuration ...");
            return;
        }
      
        // api object
        require "EAPI.class.php";
        $api = new EAPI();
        $api->clientCode = $this->_helperData->getCustomercode();//501692;
        $api->username = $this->_helperData->getUsername();//"devopsheros@gmail.com";
        $api->password = $this->_helperData->getPassword(); //"Admin123#";
        $api->url = "https://".$api->clientCode.".erply.com/api/";
        $output->writeln("connecting with erply apis........");

        try {
            $this->summayApi($api);
        } catch (Exception $e) {
            $output->writeln("$e->getMessage()");
        }

        // fetch products
        $limit = 100;
        $pages = ceil($this->totalRecords/$limit);
        $output->writeln("Erply total customers : $this->totalRecords");

        for ($i=1; $i <= $pages; $i++) { 
            $this->importcustomers($api, array('pageNo' => $i, 'recordsOnPage'=> $limit));
        }

        $output->writeln("Erply successfully imported customers : $this->success");
        $output->writeln("Erply skipped customers : $this->skip");

    }

    protected function summayApi($api)
    {
        $result = $api->sendRequest("getCustomers", array('pageNo' =>1, 'recordsOnPage'=>1));
        $summaryResult  = json_decode($result, true);
        if($summaryResult == null) return false;

        if($summaryResult['status']['responseStatus'] == "ok") {
            $this->totalRecords = $summaryResult['status']['recordsTotal'];
        }
    }
    
    protected function importcustomers($api, $parameters = array())
    {
        $result = $api->sendRequest("getCustomers", $parameters);
        $summaryResult  = json_decode($result, true);
        if($summaryResult == null) return false;

        if($summaryResult['status']['responseStatus'] == "ok") {
            foreach ($summaryResult['records'] as $key => $record) {
                $this->createCustomer($record);            
            }
        }
    }

    public function createCustomer($record)
    {
        try { 
            $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();
            $customer   = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $data = [
                'firstName' => $record['firstName'],
                'lastName' =>$record['lastName'],
                'email' =>$record['email'],
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
            $customer->setData($data);
            $this->setcustomerAddress($customer->getId(), $record);
            $customer->save();
            // $customer->sendNewAccountEmail();
            $this->success++;
            $output->writeln("Success : ". $record['id']);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $output->writeln("Error : ". $record['id']);
            $this->skip++;
        }
        $output->writeln(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
        $output->writeln("Totel Success : ". $this->success);
        $output->writeln("Total Error : ". $this->skip);
        $output->writeln(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");
        unset($customer);
    }

    public function setcustomerAddress($id,$record) {
        print_r($id);die;
        $street = array(
        '0' => $record['street'], 
        '1' => $record['address'] 
        );
        $customerAddress = $this->addressFactory->create();
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
}
