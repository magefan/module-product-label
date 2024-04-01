<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config
 */
class Config
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Extension enabled config path
     */
    const XML_PATH_EXTENSION_ENABLED = 'mfproductlabel/general/enabled';

    const XML_PATH_GENERAL_LABELS_PER_PRODUCT = 'mfproductlabel/general/labels_per_product';

    const XML_PATH_GENERAL_CSS_CLASS_PARENT_LABEL_CONTAINER = 'mfproductlabel/general/css_class_parent_label_container';

    const XML_PATH_GENERAL_PRODUCT_PAGE_CONTAINER_SELECTOR = 'mfproductlabel/general/pp_selector';

    const XML_PATH_GENERAL_LIST_PAGE_CONTAINER_SELECTOR = 'mfproductlabel/general/pl_selector';

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve true if product label module is enabled
     *
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_EXTENSION_ENABLED, $storeId);
    }

    /**
     * @param $storeId
     * @return int
     */
    public function getLabelsPerProduct($storeId = null): int
    {
        return (int)$this->getConfig(self::XML_PATH_GENERAL_LABELS_PER_PRODUCT, $storeId);
    }


    public function getProductPageContainerSelector($storeId = null): string
    {
        return (string)$this->getConfig(self::XML_PATH_GENERAL_PRODUCT_PAGE_CONTAINER_SELECTOR, $storeId);
    }

    public function getProductListContainerSelector($storeId = null): string
    {
        return (string)$this->getConfig(self::XML_PATH_GENERAL_LIST_PAGE_CONTAINER_SELECTOR, $storeId);
    }

    /**
     * Retrieve store config value
     * @param string $path
     * @param null $storeId
     * @return mixed
     */
    public function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
