<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Model\Config\Source;

use Magefan\ProductLabel\Model\Config;

class CustomPosition implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    private $options;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function toOptionArray()
    {
        if (!$this->options) {
            foreach ($this->config->getCustomPositions() as $position) {
                $this->options[] = [
                    'value' => $position,
                    'label' => $position,
                ];
            }
        }

        return $this->options;
    }
}
