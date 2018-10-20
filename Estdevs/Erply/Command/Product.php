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
    private $_helperData;
    protected $directoryList;
    private $updateProducts=0;

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
        \Magento\Framework\ObjectManagerInterface $objectmanager
       ) {
            $this->state = $state;
            $this->_objectManager = $objectmanager;
            $this->_helperData = $helperData;
            // $this->directoryList = $directoryList;
            $this->file = $file;
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

        //die("sadf");
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
        //$output->writeln("Import Categories");

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
            $this->importProducts($api, array('pageNo' => $i, 'recordsOnPage'=> $limit));
        }

        $output->writeln("Erply successfully imported products : $this->success");
        $output->writeln("Erply skipped products : $this->skipProducts");

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
        if($productId = $product->getIdBySku($sku)) {
            return $productId;   
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
                // print_r($record);die();
                if($record['active']) {
                     switch ($record['type']) {
                        case 'PRODUCT':
                            # code...
                            $this->createProduct($record);
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


        $productId = $this->isexists($record['code']);
        if(!$productId) {
            $product = $this->_objectManager->create('\Magento\Catalog\Model\Product');
        } else {
            // echo "new product : $productId";
            // echo $productId.",";
            if($productId > 0) {
                $product = $this->_objectManager->create('\Magento\Catalog\Model\Product')->load($productId);
            } else {
                           $this->skipProducts++;
                     return; 
            }


            // $this->skipped++;
        }
        // echo "\n";
        try {    
            $product->setData($data);
            $product->setWebsiteIds(array(1));            
            $product->setAttributeSetId(4);
            if($record['type'] == 'BUNDLE') {
                $product->setTypeId('bundle');
            } else {
                 $product->setTypeId('simple');
            }
            //$product->setUrlKey(strtotime('now')); 
            $product->setCreatedAt($record['name'].strtotime('now')); 
            //$product->setTaxClassId(0);
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
            usleep(10);
            if(isset($record['images'])) {
                //import
                $this->createImage($product, $record['images'][0]['largeURL'], true, ['image', 'small_image', 'thumbnail']);
            }

            $this->success++;
            echo ".";
        }
        catch (\Magento\Framework\Exception\NoSuchEntityException $e)
        {
            $this->skipProducts++;
            return true;
        }

        unset($product);
    }


    public function createImage($product, $imageUrl, $visible = false, $imageType = [])
    {
        if($imageUrl===''){return;}
        /** @var string $tmpDir */
        $tmpDir = $this->getMediaDirTmpDir();
        /** create folder if it is not exists */
        $this->file->checkAndCreateFolder($tmpDir);
        /** @var string $newFileName */
        $newFileName = $tmpDir . baseName($imageUrl);
        /** read file from URL and copy it to the new destination */
        $result = $this->file->read($imageUrl, $newFileName);
        if ($result) {
            /** add saved file to the $product gallery */
            $product->addImageToMediaGallery($newFileName, $imageType, true, $visible);
            $product->save();
        }

        return $result;
    }

    /**
     * Media directory name for the temporary file storage
     * pub/media/tmp
     *
     * @return string
     */
    protected function getMediaDirTmpDir()
    {
        return $this->directoryList->getPath('media') . DIRECTORY_SEPARATOR . 'tmp/';
    }

} 