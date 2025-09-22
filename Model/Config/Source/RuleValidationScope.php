<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RuleValidationScope implements OptionSourceInterface
{
    public const SCOPE_GLOBAL = 0;
    public const SCOPE_DEFAULT_STORE_VIEW_PER_WEBSITE = 1;
    public const SCOPE_SELECTED_STORE_VIEWS_PER_RULE = 2;

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::SCOPE_GLOBAL, 'label' => __('Global (Default)')],
            ['value' => self::SCOPE_DEFAULT_STORE_VIEW_PER_WEBSITE, 'label' => __('Default Store View(s) per Website')],
            ['value' => self::SCOPE_SELECTED_STORE_VIEWS_PER_RULE, 'label' => __('Selected Store View(s) per Rule')],
        ];
    }
}
