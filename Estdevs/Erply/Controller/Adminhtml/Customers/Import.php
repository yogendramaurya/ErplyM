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

namespace Estdevs\Erply\Controller\Adminhtml\Customers;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;

class Import extends \Magento\Backend\App\Action
{
    /**
     * @var \Estdevs\Erply\Helper\Data
     */
    protected $_helper;
    /**
     * @var ResultFactory
     */
    protected $_resultFactory;
    
    protected $_messageManager;
    protected $totalRecords;

    /**
     * GetInterest constructor.
     * @param Context $context
     * @param \Estdevs\Erply\Helper\Data $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \Estdevs\Erply\Helper\Data $helper
        
    ) {
		
        parent::__construct($context);
        $this->_helper = $helper;
        $this->_resultFactory  = $context->getResultFactory();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_messageManager = $context->getmessageManager();

    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $param = $this->getRequest()->getPostValue();
        $page = $param["page"];
        $lastCollectTime ="21";     
        try {
            $response = $this->_helper->importCustomers();     
            $lastCollectTime ="21";
            $message = "Customer imported successfully.";
            return $result->setData(['success' => true, 'data'=>$response, 'message'=> $message, 'time' => $lastCollectTime]);
        } catch (Exception $e) {
            return $result->setData(['success' => true, 'message'=> $e->getMessage(),'time' => $lastCollectTime]);
        }

    }
}
