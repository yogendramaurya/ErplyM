<?php
namespace Estdevs\Erply\Plugin\Catalog\Block\Adminhtml;

use Magento\Catalog\Block\Adminhtml\Product as ProductGrid;
use Magento\Framework\UrlInterface;
use Magento\Framework\AuthorizationInterface;

class Product
{
  /** @var \Magento\Framework\UrlInterface */
  protected $_urlBuilder;

  /** @var \Magento\Framework\AuthorizationInterface */
  protected $_authorization;

  public function __construct(
    UrlInterface $url,
    AuthorizationInterface $authorization
  ) {
    $this->_urlBuilder = $url;
    $this->_authorization = $authorization;
  }

  public function beforeSetLayout(ProductGrid $grid) {
    $url = $this->_urlBuilder->getUrl('estdevs_erply/products/import');
	
	$grid->addButton(
      'erply_product_import',
      [
        'label' => __('Import Erply Products'),
        'class' => 'action- scalable action-secondary',
        'on_click' => 'var con = confirm("Are you sure to import products from erply, this may take long time"); if(con==true){location.href="'.$url.'";}else{return false;}',
      ]
    );	
	 
    
  }
}
