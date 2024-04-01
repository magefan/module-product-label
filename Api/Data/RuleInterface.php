<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Api\Data;

/**
 * Interface RuleInterface
 * @package Magefan\ProductLabel\Api\Data
 */
interface RuleInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Get name
     * @return string|null
     */
    public function getLabelData();
}
