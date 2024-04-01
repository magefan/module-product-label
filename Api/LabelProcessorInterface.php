<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Api;

interface LabelProcessorInterface
{
    /**
     * @param array $replaceMap
     * @param array $productIds
     * @return array
     */
    public function execute(array $replaceMap, array $productIds): array;
}
