<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Api\RuleRepositoryInterface;
use Tonyapps\ShippingRules\Model\Rule;
use Tonyapps\ShippingRules\Model\RuleFactory;

/**
 * Persists a shipping rule submitted from the edit form.
 */
class Save extends AbstractRule implements HttpPostActionInterface
{
    /**
     * Multiselect fields that must default to an empty selection when the form omits them.
     */
    private const MULTISELECT_FIELDS = [
        RuleInterface::APPLIES_TO_METHODS,
        RuleInterface::CUSTOMER_GROUP_IDS,
        RuleInterface::STORE_IDS,
    ];

    public function __construct(
        Context $context,
        RuleRepositoryInterface $ruleRepository,
        RuleFactory $ruleFactory,
        Registry $coreRegistry,
        private readonly DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context, $ruleRepository, $ruleFactory, $coreRegistry);
    }

    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $ruleId = (int)($data['rule_id'] ?? 0);

        try {
            $rule = $ruleId ? $this->ruleRepository->getById($ruleId) : $this->ruleFactory->create();
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('This shipping rule no longer exists.'));

            return $resultRedirect->setPath('*/*/');
        }

        $data = $this->prepareData($data);

        /** @var Rule $rule */
        // The native rule engine emits conditions under rule[conditions]; move them where loadPost expects.
        if (isset($data['rule']['conditions'])) {
            $data['conditions'] = $data['rule']['conditions'];
        }
        unset($data['rule']);

        $rule->loadPost($data);
        unset($data['conditions']);
        $rule->addData($data);

        try {
            $this->ruleRepository->save($rule);
            $this->messageManager->addSuccessMessage(__('The shipping rule has been saved.'));
            $this->dataPersistor->clear(self::DATA_PERSISTOR_KEY);

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['rule_id' => $rule->getId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $this->dataPersistor->set(self::DATA_PERSISTOR_KEY, $this->getRequest()->getPostValue());

            return $ruleId
                ? $resultRedirect->setPath('*/*/edit', ['rule_id' => $ruleId])
                : $resultRedirect->setPath('*/*/new');
        }
    }

    /**
     * Normalise form inputs before they reach the model.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepareData(array $data): array
    {
        if (!$ruleId = (int)($data['rule_id'] ?? 0)) {
            unset($data['rule_id']);
        } else {
            $data['rule_id'] = $ruleId;
        }

        // Empty date fields must be stored as NULL, not an empty string.
        foreach ([RuleInterface::FROM_DATE, RuleInterface::TO_DATE] as $dateField) {
            if (isset($data[$dateField]) && $data[$dateField] === '') {
                $data[$dateField] = null;
            }
        }

        // A cleared multiselect posts nothing; force an empty array so the old value is overwritten.
        foreach (self::MULTISELECT_FIELDS as $field) {
            if (!isset($data[$field]) || !is_array($data[$field])) {
                $data[$field] = [];
            }
        }

        return $data;
    }
}
