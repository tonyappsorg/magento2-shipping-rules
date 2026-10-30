<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Registry;
use Tonyapps\ShippingRules\Api\RuleRepositoryInterface;
use Tonyapps\ShippingRules\Model\Rule\Io\CsvImporter;
use Tonyapps\ShippingRules\Model\Rule\Io\JsonImporter;
use Tonyapps\ShippingRules\Model\RuleFactory;

/**
 * Handles the uploaded rule file: parses and validates every rule first, then saves them in one transaction, so a
 * bad file imports nothing instead of half the rules.
 */
class ImportPost extends AbstractRule implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        RuleRepositoryInterface $ruleRepository,
        RuleFactory $ruleFactory,
        Registry $coreRegistry,
        private readonly JsonImporter $jsonImporter,
        private readonly CsvImporter $csvImporter,
        private readonly FileDriver $fileDriver,
        private readonly ResourceConnection $resourceConnection
    ) {
        parent::__construct($context, $ruleRepository, $ruleFactory, $coreRegistry);
    }

    public function execute(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $file = $this->getRequest()->getFiles('import_file');
        if (!is_array($file) || empty($file['tmp_name']) || !empty($file['error'])) {
            $this->messageManager->addErrorMessage(__('Please select a valid file to import.'));

            return $resultRedirect->setPath('*/*/import');
        }

        try {
            $format = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
            $importer = $format === 'csv' ? $this->csvImporter : $this->jsonImporter;

            $content = $this->fileDriver->fileGetContents($file['tmp_name']);
            $rules = $importer->import($content);

            $connection = $this->resourceConnection->getConnection();
            $connection->beginTransaction();
            try {
                foreach ($rules as $rule) {
                    $this->ruleRepository->save($rule);
                }
                $connection->commit();
            } catch (\Exception $exception) {
                $connection->rollBack();
                throw $exception;
            }
            $imported = count($rules);

            $this->messageManager->addSuccessMessage(__('%1 shipping rule(s) were imported.', $imported));

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('Import failed: %1', $exception->getMessage()));
        }

        return $resultRedirect->setPath('*/*/import');
    }
}
