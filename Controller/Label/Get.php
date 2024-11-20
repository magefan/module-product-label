<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Controller\Label;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Model\Product;
use Magefan\ProductLabel\Model\GetLabels;

class Get extends \Magento\Framework\App\Action\Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var GetLabels
     */
    protected $getLabels;

    /**
     * Get constructor.
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param PageFactory $resultPageFactory
     * @param GetLabels $getLabels
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        GetLabels $getLabels
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->getLabels = $getLabels;
    }

    public function execute()
    {
        $result = $this->jsonResultFactory->create();
        $replaceMap = $this->getLabels->execute($this->getProductIds(), $this->getProductIdsForProductPage());

        $result->setData([
            'labels' => $replaceMap
        ]);

        $result->setHeader('X-Magento-Tags',  implode(',', $this->getCacheTags()));

        return $result;
    }

    /**
     * @return array
     */
    private function getCacheTags(): array
    {
        $tags = [];

        foreach ($this->getProductIds() as $productId) {
            $tags[] = Product::CACHE_TAG . '_' . $productId;
        }

        return $tags;
    }

    /**
     * Retrieve and process product IDs from the request.
     *
     * @param string $paramName
     * @return array
     */
    private function getProductIdsFromRequest(string $paramName): array
    {
        $productIds = (string) $this->getRequest()->getParam($paramName, '');

        return array_map('intval', explode(',', $productIds));
    }

    /**
     * @return array
     */
    private function getProductIds(): array
    {
        return $this->getProductIdsFromRequest('product_ids');
    }

    /**
     * @return array
     */
    private function getProductIdsForProductPage(): array
    {
        return $this->getProductIdsFromRequest('product_ids_for_product_page');
    }
}
