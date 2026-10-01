<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Block\Adminhtml\Rule;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;

/**
 * Backing block for the rule import upload form.
 */
class Import extends Template
{
    public function __construct(
        Context $context,
        private readonly FormKey $formKeyProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getFormKeyValue(): string
    {
        return $this->formKeyProvider->getFormKey();
    }

    public function getPostUrl(): string
    {
        return $this->getUrl('tonyapps_shippingrules/rule/importPost');
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('tonyapps_shippingrules/rule/index');
    }
}
