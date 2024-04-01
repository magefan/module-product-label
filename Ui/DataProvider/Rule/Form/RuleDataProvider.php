<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Ui\DataProvider\Rule\Form;

use Magefan\ProductLabel\Api\RuleCollectionInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\RequestInterface;

/**
 * Class DataProvider
 */
class RuleDataProvider extends AbstractDataProvider
{
    /**
     * @var \Magefan\ProductLabel\Api\RuleCollectionInterface
     */
    protected $collection;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        RuleCollectionInterface $ruleCollection,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->request = $request;
        $this->collection = $ruleCollection;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $rule) {
            $rule = $rule->load($rule->getId()); //temporary fix
            $data = $rule->getData();

            /* Prepare Image */
            if (isset($data['image'])) {
                $name = $data['image'];
                unset($data['image']);
                $data['image'][0] = [
                    'name' => $name,
                    'url' => $rule->getImageUrl(),
                ];
            }

            if (isset($data['pp_image'])) {
                $name = $data['pp_image'];
                unset($data['pp_image']);
                $data['pp_image'][0] = [
                    'name' => $name,
                    'url' => $rule->getPpImageUrl(),
                ];
            }

            /* Set data */
            $this->loadedData[$rule->getId()] = $data;
        }
        return $this->loadedData;
    }
}
