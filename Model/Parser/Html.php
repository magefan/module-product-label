<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Model\Parser;

use Magefan\ProductLabel\Model\GetLabels;
use Magefan\ProductLabel\Model\Config;

class Html
{
    const COMMENT_PREFIX = '<!--mf_product_label_comment_';
    const COMMENT_PREFIX_GALLERY = '<!--mf_product_label_gallery_comment_';
    const COMMENT_SUFFIX = '-->';


    /**
     * @var GetLabels
     */
    protected $getLabels;
    protected $mapProductToCustomPosition = [];

    public function __construct(
        GetLabels $getLabels
    ) {
        $this->getLabels = $getLabels;
    }

    /**
     * @param string $output
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(string $output): string
    {
        $isOutputIsJson = $this->json_validate($output);
        $productIds = $this->getProductIds($output);

        $currentPageProductId = $this->getCurrentPageProductId($output);
        $productIdsForProductPage = [];

        if ($currentPageProductId) {
            $productIds[] = $currentPageProductId;
            $productIdsForProductPage[] = $currentPageProductId;
        }

        $replaceMap = $this->getLabels->execute($productIds, $productIdsForProductPage);

        foreach ($replaceMap as $productId => $replace) {
            $replace = $isOutputIsJson ? trim(json_encode($replace),'"') : $replace;

            // should be above regular replace
            $this->replaceForCustomPosition($output, $replace, $productId);

            $output = ($currentPageProductId && $currentPageProductId == $productId)
                ? str_replace(self::COMMENT_PREFIX_GALLERY . $productId . self::COMMENT_SUFFIX, $replace, $output)
                : str_replace(self::COMMENT_PREFIX         . $productId . self::COMMENT_SUFFIX, $replace, $output);
        }

        return $output;
    }

    /**
     * @param string $output
     * @param string $replace
     * @param $productId
     * @return void
     */
    private function replaceForCustomPosition(string &$output, string &$replace, $productId)
    {
        $customPositions = $this->mapProductToCustomPosition[$productId] ?? [];

        if (strpos($replace, Config::SPLITTERS_FOR_CUSTOM_POSITIONS) !== false) {
            if ($customPositions) {
                $customPositionsLabels = explode(Config::SPLITTERS_FOR_CUSTOM_POSITIONS, $replace);
                $replace = $customPositionsLabels[0];
                unset($customPositionsLabels[0]);

                foreach ($customPositionsLabels as $label) {
                    foreach ($customPositions as $customPosition) {
                        if (strpos($label, $customPosition) !== false) {
                            $output = str_replace(self::COMMENT_PREFIX . $productId . '____' . $customPosition .  self::COMMENT_SUFFIX, $label, $output);
                        }
                    }
                }
            } else {
                // leave only labels with regular positions
                $replace = explode(Config::SPLITTERS_FOR_CUSTOM_POSITIONS, $replace);
                $replace = $replace[0];
            }
        }
    }

    /**
     * @param string $html
     * @return int
     */
    private function getCurrentPageProductId(string $html): int
    {
        $pattern = '/' . self::COMMENT_PREFIX_GALLERY . '(.*?)' . self::COMMENT_SUFFIX . '/';
        preg_match_all($pattern, $html, $matches);

        foreach ($matches[1] as $commentData) {
            $productId = (int)$commentData;

            if ($productId) {
                return $productId;
            }
        }

        return 0;
    }

    /**
     * @param string $html
     * @return array
     */
    private function getProductIds(string $html): array
    {
        $pattern = '/' . self::COMMENT_PREFIX . '(.*?)' . self::COMMENT_SUFFIX . '/';
        preg_match_all($pattern, $html, $matches);
        $productIds = [];

        foreach ($matches[1] as $commentData) {
            /* $commentData = '3____product_list' | '3'*/

            if (is_numeric($commentData)) {
                $productId = (int)$commentData;
            } else {
                [$productId, $customPositionName] = explode('____', $commentData);
                $productId = (int)$productId;
                $this->mapProductToCustomPosition[$productId][$customPositionName] = $customPositionName;
            }

            if ($productId) {
                $productIds[$productId] = $productId;
            }
        }

        return $productIds;
    }

    private function json_validate($json, $depth = 512, $flags = 0)
    {
        if (!is_string($json)) {
            return false;
        }

        $trimmedJson = ltrim($json);
        // First character check to ensure the string starts with `{` or `[` (to improve perfrormace)
        if ($trimmedJson[0] !== '{' && $trimmedJson[0] !== '[') {
            return false;
        }

        // Decode JSON and check for errors
        json_decode($json, false, $depth, $flags);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
