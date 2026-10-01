<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Controller\Adminhtml\Rule;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\ConditionInterface;

/**
 * Returns the HTML for a newly added condition row (AJAX endpoint for the rule builder widget).
 *
 * Mirrors Magento\SalesRule\Controller\Adminhtml\Promo\Quote\NewConditionHtml, but instantiates
 * this module's rule so the reused SalesRule condition classes render against the right entity.
 */
class NewConditionHtml extends AbstractRule implements HttpPostActionInterface
{
    /**
     * @return void
     */
    public function execute()
    {
        $formName = $this->getRequest()->getParam('form_namespace');
        $id = $this->getRequest()->getParam('id');
        $typeArray = explode(
            '|',
            str_replace('-', '/', (string)$this->getRequest()->getParam('type', ''))
        );
        $type = $typeArray[0];

        if ($type && class_exists($type) && !in_array(ConditionInterface::class, class_implements($type), true)) {
            $this->getResponse()->setBody('');

            return;
        }

        $model = $this->_objectManager->create($type)
            ->setId($id)
            ->setType($type)
            ->setRule($this->ruleFactory->create())
            ->setPrefix('conditions');

        if (!empty($typeArray[1])) {
            $model->setAttribute($typeArray[1]);
        }

        if ($model instanceof AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $model->setFormName($formName);
            $this->applyJsFormObject($model);
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }

        $this->getResponse()->setBody($html);
    }

    /**
     * Reset the JS form object to the canonical name when adding a condition to a new rule.
     */
    private function applyJsFormObject(AbstractCondition $model): void
    {
        $requestJsFormName = $this->getRequest()->getParam('form');
        $actualJsFormName = $model->getFormName() . 'rule_conditions_fieldset_';
        if ($requestJsFormName === $actualJsFormName) {
            $model->setJsFormObject($actualJsFormName);
        }
    }
}
