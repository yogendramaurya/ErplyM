<?php
/**
 * mc-magento2 Magento Component
 *
 * @category ACODESH
 * @package mc-magento2
 * @author Acodesh Team <info@acodesh.com>
 * @copyright Ebizmarts (http://acodesh.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @date: 3/23/18 10:05 AM
 * @file: GetInterest.php
 */

namespace Estdevs\Erply\Controller\Adminhtml\Products;


use Magento\Framework\Controller\ResultFactory;

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

    /**
     * GetInterest constructor.
     * @param Context $context
     * @param \Estdevs\Erply\Helper\Data $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Estdevs\Erply\Helper\Data $helper
        
    ) {
		
        parent::__construct($context);
        $this->_helper = $helper;
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
		
		// $this->_helper->setProducts($products);
		
		// $this->_messageManager->addSuccessMessage('Products have been imported successfully.');
        
  //       $resultRedirect = $this->_resultFactory->create(ResultFactory::TYPE_REDIRECT);
  //       $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        // return $resultRedirect;

    }
}
