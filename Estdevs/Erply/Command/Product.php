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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Product extends Command
{

    private $state;
    private $_objectManager;
    private $_helper;

    public function __construct(
        \Magento\Framework\App\State $state,
        \Estdevs\Erply\Helper\Data $helperData,
        \Magento\Framework\ObjectManagerInterface $objectmanager
       ) {
            $this->state = $state;
            $this->_objectManager = $objectmanager;
            $this->_helper = $helperData;
            parent::__construct();
    }

    protected function configure()
    {

        $this->setName("acodesh:importproduct");
        $this->setDescription("Download Products and update in magento.");
        $this->addArgument('price', InputArgument::OPTIONAL, __('Type a number'));
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML); 

        if($input->getArgument('price') == "price") {
            //$output->writeln($input->getArgument('price')*2);
            $response = $this->_helper->setpriceSync("cli");
            echo "<pre>";
            print_r($response);
        } elseif($input->getArgument('price') == "category") {
             $response = $this->_helper->syncProductCategory("cli");
             echo "<pre>";
            print_r($response);
        }
        else {
            $response = $this->_helper->setProducts("cli");
        }
    }
} 