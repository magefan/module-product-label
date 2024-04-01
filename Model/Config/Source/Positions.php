<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Model\Config\Source;

class Positions implements \Magento\Framework\Data\OptionSourceInterface, \Magefan\ProductLabel\Api\PositionsInterface
{
    const TOP_CENTER ='top-center';
    const BOTTOM_CENTER ='bottom-center';

    const CENTER ='center';
    const CENTER_LEFT ='center-left';
    const CENTER_RIGHT ='center-right';

    const TOP_LEFT =  'top-left';
    const TOP_RIGHT =  'top-right';
    const BOTTOM_LEFT =  'bottom-left';
    const BOTTOM_RIGHT =  'bottom-right';

    const CUSTOM = 'custom';

    /**
     * @return array[]
     */
    public function toOptionArray():array
    {
        return [
            ['value' =>  self::TOP_LEFT, 'label' => __('Top Left')],
            ['value' =>  self::TOP_CENTER, 'label' => __('Top Center')],
            ['value' =>  self::TOP_RIGHT, 'label' => __('Top Right')],
            ['value' =>  self::CENTER_LEFT, 'label' => __('Center Left')],
            ['value' =>  self::CENTER, 'label' => __('Center')],
            ['value' =>  self::CENTER_RIGHT, 'label' => __('Center Right')],
            ['value' =>  self::BOTTOM_LEFT, 'label' => __('Bottom Left')],
            ['value' =>  self::BOTTOM_CENTER, 'label' => __('Bottom Center')],
            ['value' =>  self::BOTTOM_RIGHT, 'label' => __('Bottom Right')],
            ['value' =>  self::CUSTOM, 'label' => __('Custom')]
        ];
    }
}
