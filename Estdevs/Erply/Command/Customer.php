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

    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $file,
        \Estdevs\Erply\Helper\Data $helperData,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectmanager
       ) {
            $this->state = $state;
            $this->_objectManager = $objectmanager;
            $this->_helperData = $helperData;
             $this->storeManager     = $storeManager;
            $this->customerFactory  = $customerFactory;
            $this->file = $file;
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
        $output->writeln("Erply total products : $this->totalRecords");

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

    protected function isexists($_customerCode)
    {
        $product = $this->_objectManager->get('Magento\Catalog\Model\Product');
        if($productId = $product->getIdBySku($sku)) {
            return $productId;   
        } 

        return false;
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
        // $data = array(
        //     'firstName' => $record['firstName'],
        //     'lastName' => $record['lastName'],
        //     'email' => $record['email'],
        // );
        print_r($record);
        if(empty($record['firstName']) || $record['firstName'] == '' || $record['firstName'] == null){
            print_r($record);die();
        }

        try {    
             // Get Website ID
            $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();

            // Instantiate object (this is the most important part)
            $customer   = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId);
            //$customer->setData($data);
            // Preparing data for new customer
            $customer->setEmail($record['email']); 
            $name =  !empty($record['firstName']) ? $record['firstName'] :$record['fullName'];
            $customer->setFirstName($name); 
            $customer->setLastName($record['lastName']); 
            $customer->setPassword("password");


            // Save data
            $customer->save();
            // $customer->sendNewAccountEmail();
die();
            $this->success++;
            echo ".";
        }
        catch (\Magento\Framework\Exception\NoSuchEntityException $e)
        {
            $this->skip++;
            return true;
        }

        unset($customer);
    }
}
