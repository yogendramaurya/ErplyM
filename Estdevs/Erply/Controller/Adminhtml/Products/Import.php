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

namespace Estdevs\Erply\Controller\Adminhtml\Products;


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
    protected $resultJsonFactory;

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
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_resultFactory  = $context->getResultFactory();
        $this->_messageManager = $context->getmessageManager();

    }

    /**
     * @return mixed
     */
    public function execute()
    {
   
		$products = $this->_helper->getProducts();
				
		if(count($products)){
			  $products = $this->_helper->setProducts($products);	
		}	        
        $result = $this->resultJsonFactory->create();
        $lastCollectTime ="21";
        return $result->setData(['success' => true, 'time' => $lastCollectTime]);

    }
}
