<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\View\Layout\PageType\Config;

class PageType implements OptionSourceInterface
{
    /**
     * @var Config
     */
    private $pageConfig;

    /**
     * @var array
     */
    private $options;

    /**
     * @param Config $pageConfig
     */
    public function __construct(
        Config $pageConfig
    ) {
        $this->pageConfig = $pageConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        if (!$this->options) {
            $pageTypes = array_keys($this->pageConfig->getPageTypes());
            sort($pageTypes);

            foreach ($pageTypes as $action) {
                $label = explode('_', $action);
                $label = array_map('ucfirst', $label);

                $this->options[] = [
                    'value' => $action,
                    'label' => implode(' · ', $label),
                ];
            }
        }

        return $this->options;
    }
}
