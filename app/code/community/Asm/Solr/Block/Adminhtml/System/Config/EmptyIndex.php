<?php

class Asm_Solr_Block_Adminhtml_System_Config_EmptyIndex extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	/**
	 * Set template to itself
	 */
	protected function _prepareLayout()
	{
		parent::_prepareLayout();

		if (!$this->getTemplate()) {
			$this->setTemplate('solr/system/config/emptyindex.phtml');
		}

		return $this;
	}

	/**
	 * Unset some non-related element parameters
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string
	 */
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
		$element->unsScope()
				->unsCanUseWebsiteValue()
				->unsCanUseDefaultValue();

		return parent::render($element);
	}

	/**
	 * Get the button and scripts contents
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string
	 */
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$originalData = $element->getOriginalData();
		$this->addData(array(
			'button_label' => Mage::helper('solr')->__($originalData['button_label']),
			'html_id' => $element->getHtmlId(),
			'ajax_url' => Mage::getSingleton('adminhtml/url')->getUrl('*/solr_system_config_utility/emptyindex')
		));

		return $this->_toHtml();
	}
}

?>