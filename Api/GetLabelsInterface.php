<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Api;

interface GetLabelsInterface
{
    /**
     * @param array $productIds
     * @param $idsUseForProductPage
     * @return array
     */
    public function execute(array $productIds, array $productIdsForProductPage = []): array;

    /**
     * @param $rules
     * @param $productRuleIds
     * @param bool $forProductPage
     * @return array
     */
    public function getAvailableProductLabels($rules, $productRuleIds, bool $forProductPage = false): array;
}
