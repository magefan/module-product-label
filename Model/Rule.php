<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magefan\ProductLabel\Api\Data\RuleInterface;

/**
 * Class Rule
 */
class Rule extends \Magento\Framework\Model\AbstractModel implements RuleInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'magefan_product_label_rule';

    /**
     * Parameter name in event
     *
     * In observe method you can use $observer->getEvent()->getObject() in this case
     *
     * @var string
     */
    protected $_eventObject = 'rule';

    protected $labelData;

    /**
     * Rule constructor.
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->storeManager = $storeManager;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init(\Magefan\ProductLabel\Model\ResourceModel\Rule::class);
    }

    /**
     * @param bool $plural
     * @return string
     */
    public function getOwnTitle(bool $plural = false): string
    {
        return $plural ? 'Product Label Rules' : 'Product Label Rule';
    }

    /**
     * Retrieve image url
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getImageUrl()
    {
        if (!$this->hasData('image_url')) {
            if ($file = $this->getData('image')) {
                $image = $this->getMediaUrl($file);
            } else {
                $image = false;
            }
            $this->setData('image_url', $image);
        }

        return $this->getData('image_url');
    }

    /**
     * Retrieve media url
     * @param string $file
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaUrl(string $file): string
    {
        return $this->storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $file;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLabelData(): array
    {
        if (!isset($this->labelData)) {
            $this->labelData = [
                'image_url' => $this->getImageUrl(),
                'image_alt' => $this->getImageAlt(),
            ];
        }

        return $this->labelData;
    }
}
