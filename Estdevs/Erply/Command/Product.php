<?php
namespace Estdevs\Erply\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Product extends Command
{

    protected $updatedProducts = array();
    protected $skipProducts = 0;
    private $state;
    protected $success = 0;
    protected $totalRecords;
    private $_objectManager;

    public function __construct(\Magento\Framework\App\State $state,
                \Magento\Framework\ObjectManagerInterface $objectmanager
    ) {
        $this->state = $state;
        $this->_objectManager = $objectmanager;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("acodesh:importproduct");
        $this->setDescription("Download Products and update in magento.");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
      
        // api object
        require "EAPI.class.php";
        $api = new EAPI();
        $api->clientCode = 501692;
        $api->username = "devopsheros@gmail.com";
        $api->password = "Admin123#";
        $api->url = "https://".$api->clientCode.".erply.com/api/";

        $output->writeln("connecting with erply apis........");
        //$output->writeln("Import Categories");

        // fetch products
        $this->summayApi($api);
        $limit = 100;
        $pages = ceil($this->totalRecords/$limit);
        $output->writeln("Erply total products $this->totalRecords .");

        for ($i=1; $i <= $pages; $i++) { 
            $this->importProducts($api, array('pageNo' => $i, 'recordsOnPage'=> $limit));
        }
    }

    protected function summayApi($api)
    {
        $result = $api->sendRequest("getProducts", array('pageNo' =>1, 'recordsOnPage'=>1));
        $summaryResult  = json_decode($result, true);
        if($summaryResult == null) return false;

        if($summaryResult['status']['responseStatus'] == "ok") {
            $this->totalRecords = $summaryResult['status']['recordsTotal'];
        }
    }

    protected function isexists($sku)
    {
        $product = $this->_objectManager->get('Magento\Catalog\Model\Product');
        if($pp=$product->getIdBySku($sku)) {
            // $y = $product->load(2065);
            // print_r($y->getData());
            return true;   
        } 

        return false;
    }
    
    protected function importProducts($api, $parameters = array())
    {
        $result = $api->sendRequest("getProducts", $parameters);
        $summaryResult  = json_decode($result, true);
        if($summaryResult == null) return false;

        if($summaryResult['status']['responseStatus'] == "ok") {
            foreach ($summaryResult['records'] as $key => $record) {
                if($record['active']) {
                    $this->createProduct($record);
                } else {
                    $this->skipProducts++;
                }               
            }
        }
    }

    public function createProduct($record)
    {
        $data = array(
            'sku' => $record['code'],
            'name' => $record['name'],
            'status'=> $record['active'],
            'length'=> $record['length'],
            'width'=> $record['width'],
            'height'=> $record['height'],
            'weight'=> $record['netWeight'],
            'description' => $record['description'],
            'shortdescription' => $record['longdesc'],
            'price' => $record['cost']
        );

        if($this->isexists($record['code'])) {
            // update product
            return;
        }

        try {
            $product = $this->_objectManager->create('\Magento\Catalog\Model\Product');
            $product->setData($data);
            $product->setWebsiteIds(array(1));
            $product->setUrlKey(strtotime('now')); 
            $product->setAttributeSetId(4);
            $product->setTypeId('simple');
            $product->setCreatedAt(strtotime('now')); 
            $product->setTaxClassId(0);
            //$product->setPrice(123) ;
            $product->setStockData(
                array(
                    'use_config_manage_stock' => 0, 
                    'manage_stock' => 1, // manage stock
                    'min_sale_qty' => 1, // Shopping Cart Minimum Qty Allowed 
                    'max_sale_qty' => 2, // Shopping Cart Maximum Qty Allowed
                    'is_in_stock' => 1, // Stock Availability of product
                    'qty' => 1000
                )
            );
            $product->save();
            $this->success++;
            echo ".";
        }
        catch (\Magento\Framework\Exception\NoSuchEntityException $e)
        {
            //echo 'Something failed for product import ' . $importProduct[0] . PHP_EOL;
            $this->skipProducts++;
            return true;
        }
    }

} 