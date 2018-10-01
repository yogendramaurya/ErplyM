<?php
namespace Estdevs\Erply\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Erply extends Command
{

    protected $updatedProducts = array();
    protected $skipProducts = array();
    private $state;

    public function __construct(\Magento\Framework\App\State $state) {
        $this->state = $state;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("estdevs:importproduct");
        $this->setDescription("Download Products and update in magento.");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $output->writeln("Analysis of csv start .....");
        $_csvProducts = $this->readCSV();
        $_totalProducts = count($_csvProducts);
        $output->writeln("Total Number of Products :".$_totalProducts);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        

        // loop through and import or update products

       foreach ($_csvProducts as $key => $_csvproduct) {
           # code...

            // mapping
           

            if(!isset($_csvproduct[4]) || !isset($_csvproduct[5]) || !isset($_csvproduct[7])) {
                continue;
            }
            $sku = $_csvproduct[4];
            $name = $_csvproduct[5];
            $price = $_csvproduct[7];
            $data = array($sku, $name, $price);

            if ($this->isexists($objectManager, $sku)) {
                //update
               $this->updatedProducts++;

            } else {
                // import
                $this->importProduct($objectManager, $data);
            }
     
       }

       $countupdated = count($this->updatedProducts);
       $output->writeln("Updated Products : $countupdated ");
       $output->writeln("Import Process Completed Successfully.");
    }

    protected function isexists($objectManager, $sku)
    {
        $product = $objectManager->get('Magento\Catalog\Model\Product');
        if($product->getIdBySku($sku)) {
            return true;   
        } 

        return false;
    }
    
    protected function importProduct($objectManager, $importProduct)
    {
        try {  
 
            $product = $objectManager->create('\Magento\Catalog\Model\Product');
            $product->setWebsiteIds(array(1));
            $product->setAttributeSetId(4);
            $product->setTypeId('simple');
            $product->setCreatedAt(strtotime('now')); 
            $product->setName($importProduct[1]); 
            $product->setSku($importProduct[0]);
            // $product->setWeight($importProduct[16]);
            $product->setStatus(1);
            // $category_id = array(30,24);
            // $product->setCategoryIds($category_id); 
            $product->setTaxClassId(0); // (0 - none, 1 - default, 2 - taxable, 4 - shipping)
            $product->setVisibility(4); // catalog and search visibility
            // $product->setColor(24);
            $product->setPrice($importProduct[2]) ;
            $product->setUrlKey($importProduct[1].strtotime('now')); 
            // $product->setMetaTitle($importProduct[1]);
            // $product->setMetaKeyword($importProduct[26]);
            // $product->setMetaDescription($importProduct[28]);
            // $product->setDescription($importProduct[27]);
            // $product->setShortDescription($importProduct[27]);
 
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
            echo ".";
        }
        catch (\Magento\Framework\Exception\NoSuchEntityException $e)
        {
            echo 'Something failed for product import ' . $importProduct[0] . PHP_EOL;
            // print_r($e);
            return true;
        }
    }

    protected function readCSV()
    {
        $file = 'sample.csv';
        $arrResult = array();
        $headers = false;
        $handle = fopen($file, "r");
        if (empty($handle) === false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (!$headers) {
                    $headers[] = $data;
                } else {
                    $arrResult[] = $data;
                }
            }
            fclose($handle);
        }
        return $arrResult;
    }
} 