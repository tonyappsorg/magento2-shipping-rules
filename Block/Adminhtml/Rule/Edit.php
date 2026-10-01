<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Block\Adminhtml\Rule;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;
use Tonyapps\ShippingRules\Controller\Adminhtml\Rule\AbstractRule;

class Edit extends Container
{
    public function __construct(
        Context $context,
        private readonly Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _construct(): void
    {
        $this->_objectId = 'rule_id';
        $this->_blockGroup = 'Tonyapps_ShippingRules';
        $this->_controller = 'adminhtml_rule';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Rule'));
        $this->addButton(
            'save_and_continue',
            [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                ],
            ],
            -100
        );
    }

    public function getHeaderText()
    {
        $rule = $this->coreRegistry->registry(AbstractRule::REGISTRY_KEY);
        if ($rule && $rule->getId()) {
            return __('Edit Rule "%1"', $this->escapeHtml((string)$rule->getName()));
        }

        return __('New Shipping Rule');
    }
}
