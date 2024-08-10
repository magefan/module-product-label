<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Model\ResourceModel\Rule;

use Magefan\ProductLabel\Api\RuleCollectionInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection  implements RuleCollectionInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';
    protected $_storeId;

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magefan\ProductLabel\Model\Rule::class,
            \Magefan\ProductLabel\Model\ResourceModel\Rule::class
        );
        $this->_map['fields']['id'] = 'main_table.id';
        $this->_map['fields']['store']   = 'store_table.store_id';
        $this->_map['fields']['group']   = 'group_table.group_id';
    }

    /**
     * Redeclare after load method for specifying collection items original data
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        $items = $this->getColumnValues('id');
        if (count($items)) {
            $connection = $this->getConnection();
            $tableName = $this->getTable('magefan_product_label_rule_store');
            $select = $connection->select()
                ->from(['cps' => $tableName])
                ->where('cps.rule_id IN (?)', $items);
            $result = [];
            foreach ($connection->fetchAll($select) as $item) {
                if (!isset($result[$item['rule_id']])) {
                    $result[$item['rule_id']] = [];
                }
                $result[$item['rule_id']][] = $item['store_id'];
            }

            if ($result) {
                foreach ($this as $item) {
                    $ruleId = $item->getData('id');
                    if (!isset($result[$ruleId])) {
                        continue;
                    }
                    if ($result[$ruleId] == 0) {
                        $stores = $this->_storeManager->getStores(false, true);
                        $storeId = current($stores)->getId();
                    } else {
                        $storeId = $result[$item->getData('id')];
                    }
                    $item->setData('_first_store_id', $storeId);
                    $item->setData('store_ids', $result[$ruleId]);
                    
                    if ($item->getData('apply_by')) {
                        $item->setData('apply_by',
                            explode(',', $item->getData('apply_by'))
                        );
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Add status filter to collection
     * @return $this
     */
    public function addActiveFilter()
    {
        return $this->addFieldToFilter('status', 1);
    }

    /**
     * Add customer group filter to collection
     * @param array|int|null $groupId
     * @return $this
     */
    public function addGroupFilter($groupId = null)
    {
        if (!$this->getFlag('group_filter_added') && $groupId !== null) {
            $this->addFilter('group', ['in' => $groupId], 'public');
            $this->setFlag('group_filter_added', true);
        }

        return $this;
    }

    /**
     * Add field filter to collection
     *
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (is_array($field)) {
            if (count($field) > 1) {
                return parent::addFieldToFilter($field, $condition);
            } elseif (count($field) === 1) {
                $field = $field[0];
                $condition = $condition[0] ?? $condition;
            }
        }

        if ($field === 'store_id' || $field === 'store_ids') {
            return $this->addStoreFilter($condition);
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Add store filter to collection
     * @param array|int|\Magento\Store\Model\Store  $store
     * @param boolean $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if ($store === null) {
            return $this;
        }

        if (!$this->getFlag('store_filter_added')) {
            if ($store instanceof \Magento\Store\Model\Store) {
                $this->_storeId = $store->getId();
                $store = [$store->getId()];
            }

            if (!is_array($store)) {
                $this->_storeId = $store;
                $store = [$store];
            }

            if (in_array(\Magento\Store\Model\Store::DEFAULT_STORE_ID, $store)) {
                return $this;
            }

            if ($withAdmin) {
                $store[] = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
            }

            $this->addFilter('store', ['in' => $store], 'public');
            $this->setFlag('store_filter_added', 1);
        }
        return $this;
    }

    /**
     * Join store relation table if there is store filter
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        foreach (['store', 'group'] as $key) {
            if ($this->getFilter($key)) {
                $joinOptions = new \Magento\Framework\DataObject();
                $joinOptions->setData([
                    'key' => $key,
                    'fields' => [],
                    'fields' => [],
                ]);

                $this->getSelect()->join(
                    [$key . '_table' => $this->getTable('magefan_product_label_rule_' . $key)],
                    'main_table.id = ' . $key . '_table.rule_id',
                    $joinOptions->getData('fields')
                )->group(
                    'main_table.id'
                );
            }
        }
        parent::_renderFiltersBefore();
    }
}
