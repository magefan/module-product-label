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
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

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
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Get constructor.
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param GetLabels $getLabels
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        GetLabels $getLabels,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->getLabels = $getLabels;
        $this->productRepository = $productRepository;
    }

    public function execute()
    {
        $result = $this->jsonResultFactory->create();

        $productIds = $this->getProductIds();
        $productIdsForProductPage = $this->getProductIdsForProductPage();

        if ($this->getRequest()->getParam('get_children') && isset($this->getProductIds()[0])) {
            $productIds = $this->getChildProductIds($this->getProductIds()[0]);

            if ($this->getRequest()->getParam('product_page')) {
                $productIdsForProductPage = $productIds;
            }
        }

        $replaceMap = $this->getLabels->execute($productIds, $productIdsForProductPage);

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

    /**
     * @param int $parentProductId
     * @return array
     */
    private function getChildProductIds(int $parentProductId): array
    {
        $childProductIds = [];

        $currentProduct = $this->productRepository->getById($parentProductId);

        if ('configurable' != $currentProduct->getTypeId()) {
            return $childProductIds;
        }

        $childProducts = $currentProduct->getTypeInstance()->getUsedProducts($currentProduct, null);

        foreach ($childProducts as $childProduct) {
            if ((int) $childProduct->getStatus() === Status::STATUS_ENABLED) {
                $childProductIds[] = $childProduct->getId();
            }
        }

        return $childProductIds;
    }
}
