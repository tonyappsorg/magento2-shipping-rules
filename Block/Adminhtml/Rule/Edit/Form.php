<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Block\Adminhtml\Rule\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Rule\Block\Conditions as ConditionsRenderer;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Shipping\Model\Config\Source\Allmethods;
use Magento\Store\Model\System\Store as SystemStore;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Controller\Adminhtml\Rule\AbstractRule;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\Source\Action as ActionSource;

class Form extends Generic
{
    private const FORM_NAME = 'tonyapps_shipping_rule_form';

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        private readonly ConditionsRenderer $conditionsRenderer,
        private readonly Fieldset $rendererFieldset,
        private readonly ActionSource $actionSource,
        private readonly Yesno $yesNo,
        private readonly GroupCollectionFactory $groupCollectionFactory,
        private readonly SystemStore $systemStore,
        private readonly Allmethods $shippingMethods,
        private readonly DataPersistorInterface $dataPersistor,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var Rule $model */
        $model = $this->_coreRegistry->registry(AbstractRule::REGISTRY_KEY);

        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('*/*/save'),
                    'method' => 'post',
                ],
            ]
        );
        $form->setHtmlIdPrefix('rule_');

        $this->addGeneralFieldset($form, $model);
        $this->addActionFieldset($form);
        $this->addScopeFieldset($form);
        $this->addConditionsFieldset($form, $model);

        $form->setValues($this->resolveFormValues($model));
        $form->setUseContainer(true);
        $this->setForm($form);
        $this->setConditionFormName($model->getConditions(), self::FORM_NAME);

        return parent::_prepareForm();
    }

    private function addGeneralFieldset($form, Rule $model): void
    {
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General')]);

        if ($model->getId()) {
            $fieldset->addField(RuleInterface::RULE_ID, 'hidden', ['name' => 'rule_id']);
        }

        $fieldset->addField(
            RuleInterface::NAME,
            'text',
            ['name' => 'name', 'label' => __('Rule Name'), 'title' => __('Rule Name'), 'required' => true]
        );
        $fieldset->addField(
            RuleInterface::DESCRIPTION,
            'textarea',
            ['name' => 'description', 'label' => __('Description'), 'title' => __('Description'), 'rows' => 3]
        );
        $fieldset->addField(
            RuleInterface::IS_ACTIVE,
            'select',
            ['name' => 'is_active', 'label' => __('Active'), 'title' => __('Active'), 'values' => $this->yesNo->toOptionArray()]
        );
        $fieldset->addField(
            RuleInterface::SORT_ORDER,
            'text',
            [
                'name' => 'sort_order',
                'label' => __('Priority'),
                'title' => __('Priority'),
                'class' => 'validate-digits',
                'note' => __('Lower numbers are evaluated first.'),
            ]
        );
        $fieldset->addField(
            RuleInterface::FROM_DATE,
            'date',
            [
                'name' => 'from_date',
                'label' => __('Active From'),
                'title' => __('Active From'),
                'date_format' => 'yyyy-MM-dd',
            ]
        );
        $fieldset->addField(
            RuleInterface::TO_DATE,
            'date',
            [
                'name' => 'to_date',
                'label' => __('Active To'),
                'title' => __('Active To'),
                'date_format' => 'yyyy-MM-dd',
            ]
        );
    }

    private function addActionFieldset($form): void
    {
        $fieldset = $form->addFieldset('action_fieldset', ['legend' => __('Rule Action')]);

        $fieldset->addField(
            RuleInterface::ACTION,
            'select',
            [
                'name' => 'action',
                'label' => __('Action'),
                'title' => __('Action'),
                'required' => true,
                'values' => $this->actionSource->toOptionArray(),
            ]
        );
        $fieldset->addField(
            RuleInterface::APPLIES_TO_METHODS,
            'multiselect',
            [
                'name' => 'applies_to_methods[]',
                'label' => __('Applies to Methods'),
                'title' => __('Applies to Methods'),
                'values' => $this->shippingMethods->toOptionArray(),
                'note' => __('Used by the "Hide" action. Leave empty to target all methods.'),
            ]
        );
        $fieldset->addField(
            RuleInterface::HIDE_OTHERS,
            'select',
            [
                'name' => 'hide_others',
                'label' => __('Hide Other Methods'),
                'title' => __('Hide Other Methods'),
                'values' => $this->yesNo->toOptionArray(),
                'note' => __('For the "Contact store" action: hide every other method when this rule matches.'),
            ]
        );
    }

    private function addScopeFieldset($form): void
    {
        $fieldset = $form->addFieldset('scope_fieldset', ['legend' => __('Scope')]);

        $fieldset->addField(
            RuleInterface::CUSTOMER_GROUP_IDS,
            'multiselect',
            [
                'name' => 'customer_group_ids[]',
                'label' => __('Customer Groups'),
                'title' => __('Customer Groups'),
                'values' => $this->groupCollectionFactory->create()->toOptionArray(),
                'note' => __('Leave empty to apply to all customer groups.'),
            ]
        );
        $fieldset->addField(
            RuleInterface::STORE_IDS,
            'multiselect',
            [
                'name' => 'store_ids[]',
                'label' => __('Store Views'),
                'title' => __('Store Views'),
                'values' => $this->systemStore->getStoreValuesForForm(false, false),
                'note' => __('Leave empty to apply to all store views.'),
            ]
        );
    }

    private function addConditionsFieldset($form, Rule $model): void
    {
        $conditionsFieldSetId = $model->getConditionsFieldSetId(self::FORM_NAME);
        $newChildUrl = $this->getUrl(
            'tonyapps_shippingrules/rule/newConditionHtml/form/' . $conditionsFieldSetId,
            ['form_namespace' => self::FORM_NAME]
        );

        $renderer = $this->rendererFieldset->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
            ->setNewChildUrl($newChildUrl)
            ->setFieldSetId($conditionsFieldSetId);

        $fieldset = $form->addFieldset(
            'conditions_fieldset',
            ['legend' => __('Conditions (apply the rule only if the following match)')]
        )->setRenderer($renderer);

        $fieldset->addField(
            'conditions',
            'text',
            [
                'name' => 'conditions',
                'label' => __('Conditions'),
                'title' => __('Conditions'),
                'required' => true,
                'data-form-part' => self::FORM_NAME,
            ]
        )->setRule($model)->setRenderer($this->conditionsRenderer);
    }

    private function resolveFormValues(Rule $model): array
    {
        $data = $model->getData();

        $persisted = $this->dataPersistor->get(AbstractRule::DATA_PERSISTOR_KEY);
        if (is_array($persisted) && $persisted !== []) {
            $data = array_merge($data, $persisted);
            $this->dataPersistor->clear(AbstractRule::DATA_PERSISTOR_KEY);
        }

        foreach ([RuleInterface::APPLIES_TO_METHODS, RuleInterface::CUSTOMER_GROUP_IDS, RuleInterface::STORE_IDS] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = $data[$field] === '' ? [] : explode(',', $data[$field]);
            }
        }

        return $data;
    }

    private function setConditionFormName(AbstractCondition $conditions, string $formName): void
    {
        $conditions->setFormName($formName);
        if ($conditions->getConditions() && is_array($conditions->getConditions())) {
            foreach ($conditions->getConditions() as $condition) {
                $this->setConditionFormName($condition, $formName);
            }
        }
    }
}
