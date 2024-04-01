<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Model;

use Magefan\ProductLabel\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magefan\ProductLabel\Model\CacheManager;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogRule\Model\RuleFactory as CatalogRuleFactory;
use Magento\Framework\App\ResourceConnection;
use Magefan\Community\Model\Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magefan\Community\Api\GetParentProductIdsInterface;
use Magefan\Community\Api\GetWebsitesMapInterface;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Class ProductLabelAction
 */
class ProductLabelAction
{
    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var CatalogRuleFactory
     */
    protected $catalogRuleFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var
     */
    protected $productIds;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $connection;

    /**
     * @var \Magefan\ProductLabel\Model\Config
     */
    protected $config;


    /**
     * @var SqlBuilder
     */
    protected $sqlBuilder;

    /**+
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var GetParentProductIdsInterface
     */
    private $getParentProductIds;

    /**
     * @var GetWebsitesMapInterface
     */
    private $getWebsitesMap;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var null
     */
    private $validationFilter = null;

    /**
     * ProductLabelAction constructor.
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CatalogRuleFactory $catalogRuleFactory
     * @param ResourceConnection $resourceConnection
     * @param SqlBuilder $sqlBuilder
     * @param \Magefan\ProductLabel\Model\CacheManager $cacheManager
     * @param GetParentProductIdsInterface $getParentProductIds
     * @param GetWebsitesMapInterface $getWebsitesMap
     * @param ModuleManager $moduleManager
     * @param null $validationFilter
     */
    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        CatalogRuleFactory $catalogRuleFactory,
        ResourceConnection $resourceConnection,
        SqlBuilder $sqlBuilder,
        CacheManager $cacheManager,
        GetParentProductIdsInterface $getParentProductIds,
        GetWebsitesMapInterface $getWebsitesMap,
        ModuleManager $moduleManager,
        $validationFilter = null
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->catalogRuleFactory = $catalogRuleFactory;
        $this->resourceConnection = $resourceConnection;
        $this->cacheManager = $cacheManager;
        $this->connection = $resourceConnection->getConnection();
        $this->sqlBuilder = $sqlBuilder;
        $this->getParentProductIds = $getParentProductIds;
        $this->getWebsitesMap = $getWebsitesMap;
        $this->moduleManager = $moduleManager;

        if ($this->moduleManager->isEnabled('Magefan_DynamicProductAttributes')) {
            $this->validationFilter =
                \Magento\Framework\App\ObjectManager::getInstance()->get('Magefan\DynamicProductAttributes\Api\AddCustomValidationFiltersInterface');
        }
    }

    /**
     * @return void
     */
    public function execute()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('magefan_product_label_rule_product');

        $productIdsToCleanCache = [];
        $oldProductToRuleData = [];

        $ruleCollection = $this->ruleCollectionFactory->create()
            ->addFieldToFilter('status', 1);

        if ($ruleCollection) {
            $select = $this->connection->select()
                ->from($tableName);

            $oldProductToRuleCollection = $this->connection->fetchAll($select);

            foreach ($oldProductToRuleCollection as $value) {
                $oldProductToRuleData[$value['rule_id'] . '_' . $value['product_id']] = $value['product_id'];
            }

            $connection->truncateTable($tableName);
        }

        foreach ($ruleCollection as $item) {
            if ($conditionsSerialized = $item->getData('conditions_serialized')) {
                $rule = $this->catalogRuleFactory->create();
                $rule->setData('conditions_serialized', $conditionsSerialized);
                $rule->setData('store_ids', $item->getStoreIds());

                $productsIdsFromRule = $this->getListProductIds($rule);

                $data = [];
                $ruleId = $item->getId();

                foreach ($productsIdsFromRule as $productId) {
                    $data[] = [
                        'rule_id' => $ruleId,
                        'product_id' => $productId
                    ];

                    if (!isset($oldProductToRuleData[$ruleId . '_' . $productId])) {
                        $productIdsToCleanCache[$productId] = $productId;
                    } else {
                        unset($oldProductToRuleData[$ruleId . '_' . $productId]);
                    }
                }

                if ($data) {
                    $connection->insertMultiple($tableName, $data);
                }
            }
        }

        foreach ($oldProductToRuleData as $productId) {
            $productIdsToCleanCache[$productId] = $productId;
        }

        if ($productIdsToCleanCache) {
            $this->cacheManager->cleanCacheByProductIds($productIdsToCleanCache);
        }
    }

    /**
     * @param $rule
     * @param null $params
     * @return array
     */
    public function getListProductIds($rule)
    {
        $this->productIds = [];
        $conditions = $rule->getConditions();

        if (!empty($conditions['conditions'])) {
            if ($rule->getWebsiteIds()) {
                $storeIds = [];
                $websites = $this->getWebsitesMap->execute();
                foreach ($websites as $websiteId => $defaultStoreId) {
                    if (in_array($websiteId, $rule->getWebsiteIds())) {
                        $storeIds[] = $defaultStoreId;
                    }
                }
            } else {
                $storeIds = [0];
            }

            $conditions = $rule->getConditions()->asArray();

            if ($this->validationFilter !== null) {
                $conditions = $this->validationFilter->processCustomValidator($conditions);
            }

            $rule->getConditions()->setConditions([])->loadArray($conditions);

            foreach ($storeIds as $storeId) {

                $productCollection = $this->productCollectionFactory->create();

                if ($storeId) {
                    $productCollection->setStoreId($storeId);
                }

                $conditions = $rule->getConditions();

                $conditions->collectValidatedAttributes($productCollection);
                $this->sqlBuilder->attachConditionToCollection($productCollection, $conditions);

                if ($this->validationFilter !== null) {
                    $this->validationFilter->addCustomValidationFilters($productCollection);
                }

                $productCollection->getSelect()->group('e.entity_id');

                foreach ($productCollection as $item) {
                    $this->productIds[] = (int) $item->getId();
                }
            }
        }

        $this->productIds = array_merge(
            $this->productIds,
            $this->getParentProductIds->execute($this->productIds)
        );

        return array_unique($this->productIds);
    }
}
