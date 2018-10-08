<?php
namespace Estdevs\Erply\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Customer extends Command
{

    protected $updatedProducts = array();
    protected $skipProducts = array();
    private $helper;

    public function __construct(\Estdevs\Erply\Helper\Data $helper) {
        $this->helper = $helper;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("acodesh:importcustomer");
        $this->setDescription("Download Customers and update in magento.");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
       //$this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $output->writeln("connecting with erply apis........");

        require "EAPI.class.php";
        $api = new EAPI();
        $api->clientCode = 501692;
        $api->username = "devopsheros@gmail.com";
        $api->password = "Admin123#";
        $api->url = "https://".$api->clientCode.".erply.com/api/";

        // Get client groups from API
        // No input parameters are needed
        $result = $api->sendRequest("getProducts", array('pageNo' =>2, 'recordsOnPage'=>1));

        $jresutl  = json_decode($result, true);


        //$data = $this->helper->getProductsApi();
        print_r($jresutl);
    }
} 