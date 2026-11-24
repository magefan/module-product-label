<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Model;

use Magefan\ProductLabel\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magefan\ProductLabel\Model\CacheManager;
use Magefan\Community\Model\Magento\Product\CollectionOptimizedForSqlValidatorFactory as ProductCollectionFactory;
use Magento\CatalogRule\Model\RuleFactory as CatalogRuleFactory;
use Magento\Framework\App\ResourceConnection;
use Magefan\Community\Model\Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magefan\Community\Api\GetParentProductIdsInterface;
use Magefan\Community\Api\GetWebsitesMapInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magefan\ProductLabel\Model\Config\Source\ApplyByOptions;
use Magefan\ProductLabel\Model\Config\Source\RuleValidationScope;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var Config
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var null
     */
    private $validationFilter = null;

    private $allStoreIds = null;

    /**
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CatalogRuleFactory $catalogRuleFactory
     * @param ResourceConnection $resourceConnection
     * @param SqlBuilder $sqlBuilder
     * @param \Magefan\ProductLabel\Model\CacheManager $cacheManager
     * @param GetParentProductIdsInterface $getParentProductIds
     * @param GetWebsitesMapInterface $getWebsitesMap
     * @param ModuleManager $moduleManager
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param $validationFilter
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
        Config $config,
        StoreManagerInterface $storeManager,
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
        $this->config = $config;
        $this->storeManager = $storeManager;
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
        $ruleTableName = $this->resourceConnection->getTableName('magefan_product_label_rule');

        $productIdsToCleanCache = [];
        $oldProductToRuleData = [];

        $ruleCollection = $this->ruleCollectionFactory->create()
            ->addFieldToFilter('status', 1);

        if (isset($params['rule_id'])) {
            $ruleIds = (array)$params['rule_id'];
            $ruleCollection->addFieldToFilter('id', ['in' => $ruleIds]);
        }

        if ($ruleCollection) {
            if (!$this->isRuleWilBeAppliedForSpecificProduct($params)) {
                $select = $this->connection->select()
                    ->from($tableName);

                $oldProductToRuleCollection = $this->connection->fetchAll($select);

                // Remove rows of rules that are no longer active. Their products were
                // already fetched above, so the cache is cleaned for them once at the
                // end of this method and not again on every future cron run.
                $activeRuleIdsSelect = $this->connection->select()
                    ->from($ruleTableName, 'id')
                    ->where('status = ?', 1);
                $connection->delete($tableName, 'rule_id NOT IN (' . $activeRuleIdsSelect . ')');

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
                $ruleParams = $params;
                // Product ids whose rows of the rule are replaced; null - all rows of the rule
                $productIdsToUpdate = null;

                if ($this->isRuleWilBeAppliedForSpecificProduct($params)) {
                    [$productIdsToUpdate, $productIdsToValidate] =
                        $this->getProductIdsForProductSave($rule, (int)$params['product_id']);

                    $ruleParams['product_id'] = $productIdsToValidate;
                    $deleteCondition['product_id IN (?)'] = $productIdsToUpdate;
                    $productIdsToCleanCache += array_combine($productIdsToUpdate, $productIdsToUpdate);
                }

                $productsIdsFromRule = $this->getListProductIds($rule, $ruleParams);

                if (null !== $productIdsToUpdate) {
                    // Other children were validated only to decide on the parent label, keep their rows as is
                    $productsIdsFromRule = array_intersect($productsIdsFromRule, $productIdsToUpdate);
                }

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

                // Replace rule rows atomically, so the frontend never sees the rule without products
                $connection->beginTransaction();
                try {
                    $connection->delete($tableName, $deleteCondition);
                    if ($data) {
                        $connection->insertMultiple($tableName, $data);
                    }
                    $connection->commit();
                } catch (\Exception $e) {
                    $connection->rollBack();
                    throw $e;
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
            $storeIds = $this->getStoreIdsToValidate($rule);

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
                        ->addFieldToFilter('entity_id', ['in' => (array)$params['product_id']]);
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
     * Product ids for applying the rule on the product save.
     *
     * Without "Display on parent": update and validate only the saved product.
     * With "Display on parent": update the saved product and its parents. A parent gets the label
     * if it or any of its children matches the rule, so validate all children of these products too.
     *
     * @param $rule
     * @param int $productId saved product id
     * @return array [int[] $productIdsToUpdate, int[] $productIdsToValidate]
     */
    private function getProductIdsForProductSave($rule, int $productId): array
    {
        if (!$rule->getDisplayOnParent()) {
            return [[$productId], [$productId]];
        }

        $parentIds = array_map('intval', $this->getParentProductIds->execute([$productId]));
        $productIdsToUpdate = array_values(array_unique(array_merge([$productId], $parentIds)));

        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $linkField = $this->connection->tableColumnExists($productTable, 'row_id') ? 'row_id' : 'entity_id';

        $childIds = array_map('intval', $this->connection->fetchCol(
            $this->connection->select()
                ->from(['r' => $this->resourceConnection->getTableName('catalog_product_relation')], ['child_id'])
                ->join(['e' => $productTable], 'e.' . $linkField . ' = r.parent_id', [])
                ->where('e.entity_id IN (?)', $productIdsToUpdate)
        ));

        $productIdsToValidate = array_values(array_unique(array_merge($productIdsToUpdate, $childIds)));

        return [$productIdsToUpdate, $productIdsToValidate];
    }

    /**
     * @param array $params
     * @return bool
     */
    protected function isRuleWilBeAppliedForSpecificProduct(array $params): bool
    {
        return $params && isset($params['rule_apply_type']) && ($params['rule_apply_type'] == ApplyByOptions::ON_PRODUCT_SAVE);
    }

    /**
     * @param $rule
     * @return array|int[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStoreIdsToValidate($rule): array
    {
        $storeIds = [0];

        if ($this->config->getRuleValidationScope() === RuleValidationScope::SCOPE_DEFAULT_STORE_VIEWS_PER_WEBSITE) {
            $storeIds = $this->getDefaultStoreIdsPerWebsite($rule);
        }

        if ($this->config->getRuleValidationScope() === RuleValidationScope::SCOPE_SELECTED_STORE_VIEWS_PER_RULE) {
            $storeIds = (array)$rule->getStoreIds();

            if (empty($storeIds) || in_array(0, $storeIds)) {
                $storeIds = $this->getAllStoreIds();
            } else {
                // Skip store views that were deleted after the rule was saved
                $storeIds = array_intersect($storeIds, $this->getAllStoreIds());
            }
        }

        return $storeIds;
    }


    /**
     * @param $rule
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getDefaultStoreIdsPerWebsite($rule): array
    {
        $defaultStoreIds = [];
        $websiteIds = [];
        $storeIds = (array)$rule->getStoreIds();

        // [website_id => default_store_id]
        $websiteToDefaultStoreMap = $this->getWebsitesMap->execute();

        if (in_array(0, $storeIds)) {
            $defaultStoreIds = $websiteToDefaultStoreMap;
        } else {
            // Skip store views that were deleted after the rule was saved
            foreach (array_intersect($storeIds, $this->getAllStoreIds()) as $id) {
                $websiteId = $this->storeManager->getStore($id)->getWebsiteId();
                $websiteIds[$websiteId] = $websiteId;
            }

            foreach ($websiteIds as $websiteId) {
                if (isset($websiteToDefaultStoreMap[$websiteId])) {
                    $defaultStoreIds[] = $websiteToDefaultStoreMap[$websiteId];
                }
            }
        }

        return $defaultStoreIds;
    }

    /**
     * @return array
     */
    private function getAllStoreIds()
    {
        if (null === $this->allStoreIds) {
            $this->allStoreIds = [];

            foreach ($this->storeManager->getStores() as $store) {
                $this->allStoreIds[] = $store->getId();
            }
        }
        return $this->allStoreIds;
    }
}
