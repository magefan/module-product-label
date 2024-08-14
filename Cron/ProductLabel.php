<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Cron;

use Magefan\ProductLabel\Model\Config;
use Magefan\ProductLabel\Model\ProductLabelAction;
use Magefan\ProductLabel\Model\Config\Source\ApplyByOptions;
use Magefan\Community\Api\GetModuleVersionInterface;

/**
 * Apply Product Label rules
 */
class ProductLabel
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ProductLabelAction
     */
    protected $productLabelAction;

    /**
     * @var GetModuleVersionInterface
     */
    protected $getModuleVersion;

    /**
     * @param ProductLabelAction $productLabelAction
     * @param Config $config
     * @param GetModuleVersionInterface $getModuleVersion
     */
    public function __construct(
        ProductLabelAction $productLabelAction,
        Config $config,
        GetModuleVersionInterface $getModuleVersion
    ) {
        $this->config = $config;
        $this->productLabelAction = $productLabelAction;
        $this->getModuleVersion = $getModuleVersion;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        if ($this->config->isEnabled()) {
            // to allow execute cron in basic and plus versions
            $rule_apply_type = ApplyByOptions::MANUALLY;

            if ($this->getModuleVersion->execute('Magefan_ProductLabelExtra')) {
                $rule_apply_type = ApplyByOptions::CRON;
            }

            $this->productLabelAction->execute(['rule_apply_type' => $rule_apply_type]);
        }
    }
}
