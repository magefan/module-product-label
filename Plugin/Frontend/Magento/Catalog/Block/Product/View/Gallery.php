<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Plugin\Frontend\Magento\Catalog\Block\Product\View;

use Magefan\ProductLabel\Model\Config;
use Magefan\ProductLabel\Model\Parser\Html;
use Magefan\Community\Api\HyvaThemeDetectionInterface;
use Magefan\Community\Api\BreezeThemeDetectionInterface;

class Gallery
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var HyvaThemeDetectionInterface
     */
    protected $hyvaThemeDetection;

    /**
     * @var BreezeThemeDetectionInterface
     */
    protected $breezeThemeDetection;

    /**
     * @param Config $config
     * @param HyvaThemeDetectionInterface $hyvaThemeDetection
     * @param BreezeThemeDetectionInterface $breezeThemeDetection
     */
    public function __construct(
        Config $config,
        HyvaThemeDetectionInterface $hyvaThemeDetection,
        BreezeThemeDetectionInterface $breezeThemeDetection
    ) {
        $this->config = $config;
        $this->hyvaThemeDetection = $hyvaThemeDetection;
        $this->breezeThemeDetection = $breezeThemeDetection;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed|string
     */
    public function afterToHtml($subject, $result)
    {
        if ($this->config->isEnabled() && ($product = $subject->getProduct())) {
            $mfPlComment = Html::COMMENT_PREFIX_GALLERY . $product->getId() . Html::COMMENT_SUFFIX;

            if ($this->hyvaThemeDetection->execute() || $this->breezeThemeDetection->execute()) {
                $result = $this->addMfLabelContainerToImageWrapperTag($result, (int)$product->getId());
            } else {
                if (strpos($result, '<img') !== false) {
                      $result = $result . $mfPlComment;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $html
     * @param int $productId
     * @return string
     */
    private function addMfLabelContainerToImageWrapperTag(string $html, int $productId): string
    {
        $wrapperClass = ltrim($this->config->getProductPageContainerSelector(), '.');

        $imgTagPosition = strpos($html, '<img');

        if ($imgTagPosition !== false) {
            $wrapperClassPosition = strpos($html, $wrapperClass);

            if ($wrapperClassPosition !== false) {
                $endOfTagWithWrapperClassPosition = strpos($html, '>', $wrapperClassPosition);

                if ($endOfTagWithWrapperClassPosition !== false) {
                    $mfPlContainer = Html::COMMENT_PREFIX_GALLERY . $productId . Html::COMMENT_SUFFIX;

                    $html = substr_replace($html, $mfPlContainer, $endOfTagWithWrapperClassPosition + 1, 0);
                }
            }
        }

        return $html;
    }
}
