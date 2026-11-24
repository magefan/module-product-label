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

        $replacePairs = [];

        foreach ($replaceMap as $productId => $replace) {
            $replace = $isOutputIsJson ? trim(json_encode($replace), '"') : $replace;

            // should be above regular replace
            $this->replaceForCustomPosition($output, $replace, $productId);

            $search = ($currentPageProductId && $currentPageProductId == $productId)
                ? self::COMMENT_PREFIX_GALLERY . $productId . self::COMMENT_SUFFIX
                : self::COMMENT_PREFIX . $productId . self::COMMENT_SUFFIX;

            $replacePairs[$search] = $replace;
        }

        // Single-pass replacement over the entire $output string
        if (!empty($replacePairs)) {
            $output = strtr($output, $replacePairs);
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
            // regular labels, then pairs of custom position name and its labels
            $parts = explode(Config::SPLITTERS_FOR_CUSTOM_POSITIONS, $replace);
            $replace = array_shift($parts);

            foreach (array_chunk($parts, 2) as [$customPosition, $label]) {
                if (isset($customPositions[$customPosition])) {
                    $output = str_replace(self::COMMENT_PREFIX . $productId . '____' . $customPosition .  self::COMMENT_SUFFIX, $label, $output);
                }
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
