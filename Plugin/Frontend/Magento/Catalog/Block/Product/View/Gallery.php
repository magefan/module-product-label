<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Plugin\Frontend\Magento\Catalog\Block\Product\View;

use Magefan\ProductLabel\Model\Config;
use Magefan\ProductLabel\Model\Parser\Html;

class Gallery
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
     * @return mixed|string
     */
    public function afterToHtml($subject, $result)
    {
        if ($this->config->isEnabled()
            && ($product = $subject->getProduct())
            && (false !== strpos($result, 'gallery-placeholder__image')))
        {
           $result = $result . Html::COMMENT_PREFIX . $product->getId() . Html::COMMENT_SUFFIX;
        }

        return $result;
    }
}
