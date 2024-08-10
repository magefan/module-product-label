<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Cron;

use Magefan\ProductLabel\Model\Config\Source\ApplyByOptions;

/**
 * Apply Product Label rules
 */
class ProductLabel
{
    /**
     * @var \Magefan\ProductLabel\Model\Config
     */
    protected $config;

    /**
     * @var \Magefan\ProductLabel\Model\ProductLabelAction
     */
    protected $productLabelAction;

    /**
     * ProductLabel constructor.
     * @param \Magefan\ProductLabel\Model\ProductLabelAction $productLabelAction
     * @param \Magefan\ProductLabel\Model\Config $config
     */
    public function __construct(
        \Magefan\ProductLabel\Model\ProductLabelAction $productLabelAction,
        \Magefan\ProductLabel\Model\Config $config
    ) {
        $this->config = $config;
        $this->productLabelAction = $productLabelAction;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        if ($this->config->isEnabled()) {
            $this->productLabelAction->execute(['rule_apply_type' => ApplyByOptions::CRON]);
        }
    }
}
