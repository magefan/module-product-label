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
use Magefan\ProductLabel\Model\Config\Source\ApplyByOptions;

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
     * @param array $params
     */
    public function execute(array $params = [])
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('magefan_product_label_rule_product');

        $productIdsToCleanCache = [];
        $oldProductToRuleData = [];

        $ruleCollection = $this->ruleCollectionFactory->create()
            ->addFieldToFilter('status', 1);

        if (isset($params['rule_id'])) {
            $ruleId = (int)$params['rule_id'];
            if ($ruleId) {
                $ruleCollection->addFieldToFilter('id', $ruleId);
            }
        }
        
        if ($ruleCollection) {
            if (!$this->isRuleWilBeAppliedForSpecificProduct($params)) {
                $select = $this->connection->select()
                    ->from($tableName);

                $oldProductToRuleCollection = $this->connection->fetchAll($select);

                foreach ($oldProductToRuleCollection as $value) {
                    $oldProductToRuleData[$value['rule_id'] . '_' . $value['product_id']] = $value['product_id'];
                }
            }
        }

        foreach ($ruleCollection as $item) {
            if ($conditionsSerialized = $item->getData('conditions_serialized')) {
                $rule = $this->catalogRuleFactory->create();
                $rule->setData('conditions_serialized', $conditionsSerialized);
                $rule->setData('store_ids', $item->getStoreIds());
                $rule->setData('apply_by', $item->getData('apply_by'));
                $rule->setData('display_on_parent', $item->getData('display_on_parent'));

                $ruleId = $item->getId();

                if (!$this->canApplyRule($rule, $params)) {
                    // to prevent cache clean
                    foreach ($oldProductToRuleData as $key => $value) {
                        if (0 === strpos($key, $ruleId . '_')) {
                            unset($oldProductToRuleData[$key]);
                        }
                    }

                    continue;
                }

                $data = [];
                $deleteCondition = ['rule_id = ?' => $ruleId];

                if ($this->isRuleWilBeAppliedForSpecificProduct($params)) {
                    $deleteCondition['product_id = ?'] = (int)$params['product_id'];
                    $productIdsToCleanCache[(int)$params['product_id']] = (int)$params['product_id'];
                }

                $connection->delete($tableName, $deleteCondition);

                $productsIdsFromRule = $this->getListProductIds($rule, $params);

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
     * @param array $params
     * @return array
     */
    public function getListProductIds($rule, array $params = []): array
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

                if ($this->isRuleWilBeAppliedForSpecificProduct($params)) {
                    $productCollection
                        ->addFieldToFilter('entity_id', $params['product_id']);
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

        if ($rule->getDisplayOnParent()) {
            $this->productIds = array_merge(
                $this->productIds,
                $this->getParentProductIds->execute($this->productIds)
            );
        }

        return array_unique($this->productIds);
    }

    /**
     * @param $rule
     * @param array $params
     * @return bool
     */
    private function canApplyRule($rule, array $params = []): bool
    {
        if (ApplyByOptions::MANUALLY === $params['rule_apply_type']) {
            return true;
        }

        $applyBy = (array)$rule->getData('apply_by');

        if (in_array(ApplyByOptions::ALL_EVENTS, $applyBy)) {
            return true;
        }

        if (!in_array($params['rule_apply_type'], $applyBy))  {
            return false;
        }

        return true;
    }

    /**
     * @param array $params
     * @return bool
     */
    protected function isRuleWilBeAppliedForSpecificProduct(array $params): bool 
    {
        return $params && isset($params['rule_apply_type']) && ($params['rule_apply_type'] == ApplyByOptions::ON_PRODUCT_SAVE);
    }
}
