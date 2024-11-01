<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Model\Parser;

use Magefan\ProductLabel\Model\GetProductIdsToRuleIdsMap;
use Magefan\ProductLabel\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magefan\ProductLabel\Model\Config;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\App\RequestInterface;
use Magefan\ProductLabel\Api\LabelProcessorInterface;

class Html
{
    const COMMENT_PREFIX = '<!--mf_product_label_comment_';

    const COMMENT_SUFFIX = '-->';

    /**
     * @var GetProductIdsToRuleIdsMap
     */
    protected $getProductIdsToRuleIdsMap;

    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var
     */
    protected $fan;

    protected $labelProcessor;

    /**
     * @param GetProductIdsToRuleIdsMap $getProductIdsToRuleIdsMap
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param LayoutInterface $layout
     * @param RequestInterface $request
     * @param LabelProcessorInterface|null $labelProcessor
     */
    public function __construct(
        GetProductIdsToRuleIdsMap $getProductIdsToRuleIdsMap,
        RuleCollectionFactory     $ruleCollectionFactory,
        StoreManagerInterface $storeManager,
        Config $config,
        LayoutInterface $layout,
        RequestInterface $request,
        ?LabelProcessorInterface $labelProcessor = null
    ) {
        $this->getProductIdsToRuleIdsMap = $getProductIdsToRuleIdsMap;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->layout = $layout;
        $this->request = $request;
        $this->labelProcessor = $labelProcessor;

        $this->fan = $this->request->getFullActionName();
    }

    /**
     * @param string $output
     * @return string
     */
    public function execute(string $output): string
    {
        $isOutputIsJson = $this->json_validate($output);
        
        $productIds = $this->getProductIds($output);
        [$ruleIds, $productIdRuleIds] = $this->getProductIdsToRuleIdsMap->execute($productIds);

        $rules = $this->ruleCollectionFactory->create()
            ->addActiveFilter()
            ->addFieldToFilter('id', ['in' => $ruleIds])
            ->addStoreFilter($this->storeManager->getStore()->getId())
            ->setOrder('priority', 'asc');

        $replaceMap = [];

        foreach ($productIdRuleIds as $productId => $productRuleIds) {
            $productLabels = $this->getProductLabels($rules, $productRuleIds);

            $htmlToReplace = $this->layout
                ->createBlock(\Magefan\ProductLabel\Block\Label::class)
                ->setProductLabels($productLabels)
                ->toHtml();

            if ($htmlToReplace) {
                $replaceMap[$productId] = $htmlToReplace;
            }
        }

        if (null !== $this->labelProcessor) {
            $replaceMap = $this->labelProcessor->execute($replaceMap, $productIds);
        }

        foreach ($replaceMap as $productId => $replace) {
            $replace = $isOutputIsJson ? trim(json_encode($replace),'"') : $replace;
            $output = str_replace(self::COMMENT_PREFIX . $productId . self::COMMENT_SUFFIX, $replace, $output);
        }

        return $output;
    }

    public function getProductLabels($rules, $productRuleIds): array
    {
        $forProductPage = $this->fan == 'catalog_product_view';

        $productLabelsCount = 0;
        $productLabels = [];

        $discardRulePerPosition = [];

        foreach ($rules as $rule) {
            if (in_array($rule->getId(), $productRuleIds)) {
                $productLabelsCount++;

                if ($forProductPage) {
                    $position = $rule->getPpPosition() ?: $rule->getPosition();
                } else {
                    $position = $rule->getPosition() ?: 'top-left';
                }

                if (!isset($discardRulePerPosition[$position])) {
                    $productLabels[$position][] = $rule->getLabelData($forProductPage);
                }

                if ($rule->getDiscardSubsequentRules()) {
                    $discardRulePerPosition[$position] = true;
                }
            }

            if ($productLabelsCount >= $this->config->getLabelsPerProduct()) {
                break;
            }
        }

        return $productLabels;
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
