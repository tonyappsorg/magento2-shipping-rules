<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Block\Adminhtml\Rule;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;

class Edit extends Container
{
    public function __construct(
        Context $context,
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

}
