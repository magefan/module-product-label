<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Api\Data;

/**
 * Interface RuleSearchResultsInterface
 */
interface RuleSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get Rule list.
     * @return \Magefan\ProductLabel\Api\Data\RuleInterface[]
     */
    public function getItems();

    /**
     * Set id list.
     * @param \Magefan\ProductLabel\Api\Data\RuleInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
