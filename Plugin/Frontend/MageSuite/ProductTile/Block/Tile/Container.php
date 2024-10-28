<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Plugin\Frontend\MageSuite\ProductTile\Block\Tile;

use Magefan\ProductLabel\Model\Config;
use Magefan\ProductLabel\Model\Parser\Html;

class Container
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
    )
    {
        $this->config = $config;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed|string
     */
    public function afterToHtml($subject, $result)
    {
        if ($result && $this->config->isEnabled()) {
            if ($subject->getData('html_tag') == 'figure') {
                return $result. Html::COMMENT_PREFIX . $subject->getProduct()->getId() . Html::COMMENT_SUFFIX;
            }
        }

        return $result;
    }
}