<?php
namespace Estdevs\Erply\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Config;
use Magento\Customer\Model\Customer;

class InstallData implements InstallDataInterface
{
	private $eavSetupFactory;
	private $eavConfig;

	public function __construct(EavSetupFactory $eavSetupFactory, Config $eavConfig)
	{
		$this->eavSetupFactory = $eavSetupFactory;
		$this->eavConfig       = $eavConfig;
	}

	public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$userAttributes = ['erply_customerID', 'erply_type_id', 'erply_groupID', 'erply_companyName', 'erply_payerID','erply_phone', 'erply_mobile','erply_fax', 'erply_birthday', 'erply_code', 'erply_integrationCode', 'erply_flagStatus', 'erply_colorStatus', 'erply_credit', 'erply_salesBlocked', 'erply_referenceNumber', 'erply_customerCardNumber', 'erply_customerType', 'erply_addressTypeID', 'erply_addressTypeName', 'erply_isPOSDefaultCustomer', 'erply_euCustomerType','erply_lastModifierUsername', 'erply_lastModifierEmployeeID', 'erply_taxExempt', 'erply_paysViaFactoring', 'erply_rewardPoints','erply_twitterID', 'erply_facebookName', 'erply_creditCardLastNumbers', 'erply_deliveryTypeID', 'erply_image', 'erply_rewardPointsDisabled', 'erply_posCouponsDisabled', 'erply_emailOptOut', 'erply_signUpStoreID', 'erply_homeStoreID','erply_countryID'];
		foreach ($userAttributes as $attribute) {
			$eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
			$label = explode('_', $attribute);
			$eavSetup->addAttribute(
				\Magento\Customer\Model\Customer::ENTITY,
				'erply_customerID',
				[
					'type'         => 'varchar',
					'label'        => $label[1],
					'input'        => 'text',
					'required'     => false,
					'visible'      => true,
					'user_defined' => true,
					'position'     => 999,
					'system'       => 0,
				]
			);
			$sampleAttribute = $this->eavConfig->getAttribute(Customer::ENTITY, $attribute);

			// more used_in_forms ['adminhtml_checkout','adminhtml_customer','adminhtml_customer_address','customer_account_edit','customer_address_edit','customer_register_address']
			$sampleAttribute->setData(
				'used_in_forms',
				['adminhtml_customer', 'customer_account_edit']

			);
			$sampleAttribute->save();
		}		
	}
}
