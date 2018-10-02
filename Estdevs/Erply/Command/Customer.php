<?php
namespace Estdevs\Erply\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Customer extends Command
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
        $this->setName("acodesh:importcustomer");
        $this->setDescription("Download Customers and update in magento.");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $output->writeln("connecting with erply apis........");

    }
} 