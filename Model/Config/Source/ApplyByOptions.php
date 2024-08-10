<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Model\Config\Source;

/**
 * Class ApplyByOptions
 */
class ApplyByOptions implements \Magento\Framework\Data\OptionSourceInterface
{
    const ALL_EVENTS = 0;
    const ON_PRODUCT_SAVE = 1;
    const CRON = 2;
    const MANUALLY = 3;

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::ALL_EVENTS,      'label' => __('All Events')],
            ['value' => self::ON_PRODUCT_SAVE, 'label' => __('On Product Save')],
            ['value' => self::CRON,            'label' => __('Cron')],
            ['value' => self::MANUALLY,        'label' => __('Manually')],
        ];
    }
}

