<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Block\Adminhtml\Rule\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Eav\Api\AttributeRepositoryInterface;

class AvailableAttributes extends \Magento\Backend\Block\Template
{

    protected $_template = 'Magefan_ProductLabel::form/available_attributes_list.phtml';

    /**
     * @var
     */
    protected $context;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    public function __construct(
        Context $context,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository,
        array $data = []
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;

        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getProductAttributesList(): array
    {
        $list = [
            'final_price' => 'final_price',
            'save_discount_amount' => 'save_discount_amount',
            'save_discount_percent' => 'save_discount_percent',
            'mfdc_is_new' => 'mfdc_is_new',
            'mfdc_is_on_sale' => 'mfdc_is_on_sale'

        ];

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $productAttributeRepository = $this->attributeRepository->getList('catalog_product', $searchCriteria);

        foreach ($productAttributeRepository->getItems() as $items) {
            // if ($items->getIsVisibleOnFront()) {
            if ($items->getIsVisible()) {
                $list[$items->getAttributeCode()] = $items->getFrontendLabel();
            }
        }

        return $list;
    }
}
