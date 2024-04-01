<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class GetProductIdsToRuleIdsMap
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->connection = $resourceConnection->getConnection();
    }

    public function execute(array $productIds): array
    {
        $ruleIds = [];
        $tableName = $this->resourceConnection->getTableName('magefan_product_label_rule_product');

        $select = $this->connection->select()
            ->from($tableName)
            ->where('product_id IN (?)', $productIds);

        $data = $this->connection->fetchAll($select);
        $productIdsToRuleIdsMap = [];

        foreach ($data as $item) {
            $productIdsToRuleIdsMap[$item['product_id']][] = $item['rule_id'];
            $ruleIds[$item['rule_id']] = $item['rule_id'];
        }

        return [$ruleIds, $productIdsToRuleIdsMap];
    }
}
