<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\AbstractModel;
use Magefan\ProductLabel\Model\CacheManager;

/**
 * Class Rule
 */
class Rule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb implements \Magefan\ProductLabel\Api\RuleResourceModelInterface
{
    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * Rule constructor.
     * @param Context $context
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     */
    public function __construct(
        Context $context,
        \Magento\CatalogRule\Model\RuleFactory $ruleFactory,
        CacheManager $cacheManager
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->cacheManager = $cacheManager;
        parent::__construct($context);
    }

    /**
     * @param $ruleId
     * @param $tableName
     * @param $field
     * @return array
     */
    public function lookupIds($ruleId, $tableName, $field)
    {
        return $this->_lookupIds($ruleId, $tableName, $field);
    }


    /**
     * @param AbstractModel $object
     * @param array $newRelatedIds
     * @param array $oldRelatedIds
     * @param $tableName
     * @param $field
     * @param $rowData
     * @return void
     */
    public function updateLinks(AbstractModel $object, array $newRelatedIds, array $oldRelatedIds, $tableName, $field, $rowData = [])
    {
        $this->_updateLinks($object, $newRelatedIds, $oldRelatedIds, $tableName, $field, $rowData);
    }

    /**
     * Get store ids to which specified item is assigned
     *
     * @param int $postId
     * @return array
     */
    public function lookupStoreIds($ruleId)
    {
        return $this->_lookupIds($ruleId, 'magefan_product_label_rule_store', 'store_id');
    }

    /**
     * Get ids to which specified item is assigned
     * @param  int $postId
     * @param  string $tableName
     * @param  string $field
     * @return array
     */
    protected function _lookupIds($ruleId, $tableName, $field)
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()->from(
            $this->getTable($tableName),
            $field
        )->where(
            'rule_id = ?',
            (int)$ruleId
        );

        return $adapter->fetchCol($select);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magefan_product_label_rule', 'id');
    }

     /**
      * @param AbstractModel $object
      * @return $this
      */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /* Store View IDs */
        if (is_array($object->getStoreIds())) {
            $object->setStoreIds(
                implode(',', $object->getStoreIds())
            );
        }

        return parent::_beforeSave($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getId()) {
            $storeIds = $this->lookupStoreIds($object->getId());
            $object->setData('store_ids', $storeIds);
        }

        return parent::_afterLoad($object);
    }

    /**
     * Assign products to store views, etc.
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $oldIds = (array)$this->lookupStoreIds($object->getId());
        $newIds =explode(',', $object->getStoreIds());

        if (!$newIds || in_array(0, $newIds)) {
            $newIds = [0];
        }

        $this->_updateLinks($object, $newIds, $oldIds, 'magefan_product_label_rule_store', 'store_id');

        $this->cacheManager->cleanCacheByLabelRuleId((int)$object->getId());

        return parent::_afterSave($object);
    }

    /**
     * Update post connections
     * @param  AbstractModel $object
     * @param  Array $newRelatedIds
     * @param  Array $oldRelatedIds
     * @param  String $tableName
     * @param  String  $field
     * @param  Array  $rowData
     * @return void
     */
    protected function _updateLinks(AbstractModel $object, array $newRelatedIds, array $oldRelatedIds, $tableName, $field, $rowData = [])
    {
        $table = $this->getTable($tableName);

        if ($object->getId() && empty($rowData)) {
            $currentData = $this->_lookupAll($object->getId(), $tableName, '*');
            foreach ($currentData as $item) {
                $rowData[$item[$field]] = $item;
            }
        }

        $insert = $newRelatedIds;
        $delete = $oldRelatedIds;

        if ($delete) {
            $where = ['rule_id = ?' => (int)$object->getId(), $field.' IN (?)' => $delete];

            $this->getConnection()->delete($table, $where);
        }

        if ($insert) {
            $data = [];
            foreach ($insert as $id) {
                $id = (int)$id;
                $data[] = array_merge(
                    ['rule_id' => (int)$object->getId(), $field => $id],
                    (isset($rowData[$id]) && is_array($rowData[$id])) ? $rowData[$id] : []
                );
            }
            /* Fix if some rows have extra data */
            $allFields = [];

            foreach ($data as $i => $row) {
                foreach ($row as $key => $value) {
                    $allFields[$key] = $key;
                }
            }

            foreach ($data as $i => $row) {
                foreach ($allFields as $key) {
                    if (!array_key_exists($key, $row)) {
                        $data[$i][$key] = null;
                    }
                }
            }
            /* End fix */
            $this->getConnection()->insertMultiple($table, $data);
        }
    }

    /**
     * Get rows to which specified item is assigned
     * @param  int $postId
     * @param  string $tableName
     * @param  string $field
     * @return array
     */
    protected function _lookupAll($postId, $tableName, $field)
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()->from(
            $this->getTable($tableName),
            $field
        )->where(
            'rule_id = ?',
            (int)$postId
        );

        return $adapter->fetchAll($select);
    }
}
