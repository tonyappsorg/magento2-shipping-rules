<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Tonyapps\ShippingRules\Api\RuleRepositoryInterface;
use Tonyapps\ShippingRules\Model\RuleFactory;

/**
 * Base controller for shipping rule admin actions.
 */
abstract class AbstractRule extends Action
{
    public const ADMIN_RESOURCE = 'Tonyapps_ShippingRules::rules';

    /**
     * Registry key holding the rule currently being edited.
     */
    public const REGISTRY_KEY = 'tonyapps_shippingrules_current_rule';

    /**
     * Data persistor key for form values kept across a failed save.
     */
    public const DATA_PERSISTOR_KEY = 'tonyapps_shippingrules_rule';

    public function __construct(
        Context $context,
        protected readonly RuleRepositoryInterface $ruleRepository,
        protected readonly RuleFactory $ruleFactory,
        protected readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }
}
