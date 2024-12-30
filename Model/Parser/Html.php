<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Model\Parser;

use Magefan\ProductLabel\Model\GetLabels;

class Html
{
    const COMMENT_PREFIX = '<!--mf_product_label_comment_';
    const COMMENT_PREFIX_GALLERY = '<!--mf_product_label_gallery_comment_';
    const COMMENT_SUFFIX = '-->';

    /**
     * @var GetLabels
     */
    protected $getLabels;

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

            $output = ($currentPageProductId && $currentPageProductId == $productId)
                ? str_replace(self::COMMENT_PREFIX_GALLERY . $productId . self::COMMENT_SUFFIX, $replace, $output)
                : str_replace(self::COMMENT_PREFIX         . $productId . self::COMMENT_SUFFIX, $replace, $output);
        }

        return $output;
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
            $productId = (int)$commentData; //for now commentData=productId
            if ($productId) {
                $productIds[] = $productId;
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
