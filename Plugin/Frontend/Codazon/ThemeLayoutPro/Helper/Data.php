<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Plugin\Frontend\Codazon\ThemeLayoutPro\Helper;

use Magefan\ProductLabel\Model\Config;
use Magefan\ProductLabel\Model\Parser\Html;

class Data
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param $subject
     * @param $result
     * @param $product
     * @return mixed|string
     */
    public function afterGetProductImageHtml($subject, $result, $product)
    {
        return ($result && $this->config->isEnabled())
        	? $result . Html::COMMENT_PREFIX . $product->getId() . Html::COMMENT_SUFFIX
        	: $result;
    }
}