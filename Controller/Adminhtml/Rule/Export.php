<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Controller\Adminhtml\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;
use Tonyapps\ShippingRules\Api\RuleRepositoryInterface;
use Tonyapps\ShippingRules\Model\ResourceModel\Rule\CollectionFactory;
use Tonyapps\ShippingRules\Model\Rule\Io\CsvExporter;
use Tonyapps\ShippingRules\Model\Rule\Io\JsonExporter;
use Tonyapps\ShippingRules\Model\RuleFactory;

/**
 * Streams every rule as a downloadable JSON/CSV file.
 */
class Export extends AbstractRule implements HttpGetActionInterface
{
    public function __construct(
        Context $context,
        RuleRepositoryInterface $ruleRepository,
        RuleFactory $ruleFactory,
        Registry $coreRegistry,
        private readonly CollectionFactory $collectionFactory,
        private readonly JsonExporter $jsonExporter,
        private readonly CsvExporter $csvExporter,
        private readonly FileFactory $fileFactory
    ) {
        parent::__construct($context, $ruleRepository, $ruleFactory, $coreRegistry);
    }

    /**
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $format = (string)$this->getRequest()->getParam('format', 'json');

        try {
            $exporter = match (strtolower($format)) {
                'json' => $this->jsonExporter,
                'csv' => $this->csvExporter,
                default => throw new LocalizedException(__('Unsupported export format "%1".', $format)),
            };
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $collection = $this->collectionFactory->create();
        $collection->setOrder(RuleInterface::SORT_ORDER, 'ASC');
        $collection->setOrder(RuleInterface::RULE_ID, 'ASC');

        $content = $exporter->export(array_values($collection->getItems()));

        return $this->fileFactory->create(
            $exporter->getFileName(),
            ['type' => 'string', 'value' => $content, 'rm' => true],
            DirectoryList::VAR_DIR,
            $exporter->getContentType()
        );
    }
}
