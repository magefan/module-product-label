<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Model\Config\Backend;

use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\View\Layout\PageType\Config;

class ExcludePageType extends ConfigValue
{
    /**
     * @var Config
     */
    private $pageConfig;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Config $pageConfig
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context              $context,
        Registry             $registry,
        Config               $pageConfig,
        ScopeConfigInterface $config,
        TypeListInterface    $cacheTypeList,
        AbstractResource     $resource = null,
        AbstractDb           $resourceCollection = null,
        array                $data = []
    )
    {
        $this->pageConfig = $pageConfig;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        if (!$this->getValue()) {
            $pageTypes = $this->pageConfig->getPageTypes();
            $excludePageType = ['cms_index_index', 'cms_page_view', 'contact_index_index', 'catalog_product_view', 'catalog_category_view'];

            foreach ($excludePageType as $type) {
                unset($pageTypes[$type]);
            }
            $pageTypes = implode(',', array_keys($pageTypes));

            $this->setValue($pageTypes);
        }
    }
}