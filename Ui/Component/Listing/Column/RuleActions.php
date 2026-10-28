<?php
declare(strict_types=1);

namespace Tonyapps\ShippingRules\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Tonyapps\ShippingRules\Api\Data\RuleInterface;

/**
 * Edit / delete action links for the shipping rules grid.
 */
class RuleActions extends Column
{
    private const URL_PATH_EDIT = 'tonyapps_shippingrules/rule/edit';
    private const URL_PATH_DELETE = 'tonyapps_shippingrules/rule/delete';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item[RuleInterface::RULE_ID])) {
                continue;
            }

            $ruleId = (int)$item[RuleInterface::RULE_ID];
            $item[$name]['edit'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['rule_id' => $ruleId]),
                'label' => __('Edit'),
            ];
            $item[$name]['delete'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['rule_id' => $ruleId]),
                'label' => __('Delete'),
                'confirm' => [
                    // The confirm modal renders the title as HTML, and names can come from an imported file.
                    'title' => __('Delete "%1"', $this->escaper->escapeHtml((string)($item[RuleInterface::NAME] ?? ''))),
                    'message' => __('Are you sure you want to delete this shipping rule?'),
                ],
                'post' => true,
            ];
        }

        return $dataSource;
    }
}
