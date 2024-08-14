<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Controller\Adminhtml\Rule;

use Magefan\ProductLabel\Controller\Adminhtml\Rule;

/**
 * Class Save
 */
class Save extends Rule
{
    /**
     * After action
     * @return void
     */
    protected function _afterAction()
    {
        if ($this->getRequest()->getParam('auto_apply')) {
            $this->_redirect('*/*/apply', [$this->_idKey => $this->getRequest()->getParam($this->_idKey)]);
        }
    }

    /**
     * Before model save
     * @param  \Magefan\ProductLabel\Model\Rule $model
     * @param  \Magento\Framework\App\Request\Http $request
     * @return void
     */
    protected function _beforeSave($model, $request)
    {
        $this->prepareConditions($model);
        $this->prepareImage($model);
    }

    /**
     * @param $model
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function prepareConditions(&$model): void
    {
        if ($model->getRule('conditions')) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $rule = $objectManager->create(\Magento\CatalogRule\Model\RuleFactory::class)->create();
            $rule->loadPost(['conditions' => $model->getRule('conditions')]);
            $rule->beforeSave();

            if (!$this->getRequest()->getParam('auto_apply') && $rule->getConditionsSerialized() != $model->getConditionsSerialized()) {
                $applyRulesLink = $this->getUrl('*/*/apply', [$this->_idKey => $this->getRequest()->getParam($this->_idKey)]);

                $this->messageManager->addNotice(
                    __('You have modified product conditions, to apply new conditions <a href="%1" >click here</a>', $applyRulesLink)
                );
            }

            $model->setData(
                'conditions_serialized',
                $rule->getConditionsSerialized()
            );
        }
    }

    /**
     * @param $model
     * @return void
     */
    protected function prepareImage(&$model): void
    {
        /* Prepare images */
        $data = $model->getData();

        foreach (['image', 'pp_image'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                if (!empty($data[$key]['delete'])) {
                    $model->setData($key, null);
                } else {
                    if (isset($data[$key][0]['name']) && isset($data[$key][0]['tmp_name'])) {
                        $image = $data[$key][0]['name'];

                        $imageUploader = $this->_objectManager->get(
                            \Magefan\ProductLabel\ImageUpload::class
                        );

                        $image = $imageUploader->moveFileFromTmp($image, true);

                        $model->setData($key, $image);
                    } else {
                        if (isset($data[$key][0]['url']) && false !== strpos($data[$key][0]['url'], '/media/')) {
                            $url = $data[$key][0]['url'];

                            /**
                             *    $url may have two types of values
                             *    /media/.renditions/magefan_product_label/a.png
                             *    http://crowdin.dev.magefan.top/media/magefan_product_label/tmp/a.png
                             */

                            $keyString = strpos($url, '/.renditions/') !== false ? '/.renditions/' : '/media/';
                            $position = strpos($url, $keyString);

                            $model->setData($key, substr($url,  $position + strlen($keyString)));

                        } elseif (isset($data[$key][0]['name'])) {
                            $model->setData($key, $data[$key][0]['name']);
                        }
                    }
                }
            } else {
                $model->setData($key, null);
            }
        }
    }
}
