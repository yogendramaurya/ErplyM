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

namespace Estdevs\Erply\Block\System\Config\Form;
 
use Magento\Framework\App\Config\ScopeConfigInterface;
 
class CustomerButton extends \Magento\Config\Block\System\Config\Form\Field
{
     const BUTTON_TEMPLATE = 'system/config/customerimport.phtml';
 
     /**
     * Set template to itself
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }
    /**
     * Render button
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return $this->getUrl('estdevs_erply/customers/import'); //hit controller by ajax call on button click.
    }
     /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //$originalData = $element->getOriginalData();
        $this->addData(
            [
                'id'        => 'addbutton_button',
                'button_label'     => _('Import Customers'),
                'onclick'   => 'javascript:check(); return false;'
            ]
        );
        return $this->_toHtml();
    }
    public function getSummary()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $helperObj = $objectManager->get('Estdevs\Erply\Helper\Data');
        $response = $helperObj->getCustomers(array('pageNo' => 1, 'active' => 1,'recordsOnPage'=> 1));
        $result = [
                'totalRecords' => 0,
                'page' => 0
            ];
        if(is_array($response['status']) && $response['status']['responseStatus'] == "ok"){

            $recordsTotal = $response['status']['recordsTotal'];
            $totalPages = ceil($recordsTotal/2);
              $result = [
                'totalRecords' => (int)$recordsTotal,
                'page' => (int)$totalPages
            ];


            return $result;
        }

        return null;
    }

    public function getErplyCategory()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $helperObj = $objectManager->get('Estdevs\Erply\Helper\Data');
        return $helperObj->syncProductCategory();
    }

    public function getMagentoCategory()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $categoryFactory = $objectManager->create('Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
        $categories = $categoryFactory->create()                              
            ->addAttributeToSelect('*')
            ->setStore($this->_storeManager->getStore()); //categories from current store will be fetched
        return $categories;
    }

    public function getLimit(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
        $helperObj = $objectManager->get('Estdevs\Erply\Helper\Data');
        return $helperObj->getLimit();
    }

    public function getPTypes()
    {
        return json_encode(['PRODUCT', 'BUNDLE']);
    }
}