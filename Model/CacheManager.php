<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;

class CacheManager
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ResourceConnection $resourceConnection,
        EventManagerInterface $eventManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->eventManager = $eventManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * @param int $labelRuleId
     * @return void
     */
    public function cleanCacheByLabelRuleId(int $labelRuleId): void
    {
        $tableName = $this->resourceConnection->getTableName('magefan_product_label_rule_product');

        $select = $this->connection->select()
            ->from($tableName, 'product_id')
            ->where('rule_id = ?', $labelRuleId);

        $productIds = $this->connection->fetchCol($select);

        $this->cleanCache($productIds);
    }

    /**
     * @param array $productIds
     * @return void
     */
    public function cleanCacheByProductIds(array $productIds): void
    {
        $this->cleanCache($productIds);
    }

    /**
     * @param array $productIds
     * @return void
     */
    private function cleanCache(array $productIds): void
    {
        $productCollection = $this->productCollectionFactory->create()
            ->addAttributeToFilter('entity_id', ['in' => $productIds]);

        foreach ($productCollection as $product) {
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $product]);
        }
    }
}
