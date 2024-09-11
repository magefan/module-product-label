<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\ProductLabel\Block\Adminhtml\System\Config\Form;

/**
 * Class Info
 */
class Info extends \Magefan\Community\Block\Adminhtml\System\Config\Form\Info
{
    /**
     * Return extension url
     * @return string
     */
    protected function getModuleUrl()
    {
        return 'https://mage' . 'fan.com/magento-2-product-label';
    }

    /**
     * Return extension title
     * @return string
     */
    protected function getModuleTitle()
    {
        return 'Product Label Extension';
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->processHyva() . parent::render($element);
    }

    /**
     * @return string
     */
    private function processHyva()
    {
        $result = '';

        $hyvaThemeDetector = \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magefan\Community\Api\HyvaThemeDetectionInterface::class
        );

        if ($hyvaThemeDetector->execute(0)) {
            $script = 'document.body.classList.add("hyva")';
            $result = $this->mfSecureRenderer->renderTag('script', [], $script, false);
            $result .= '
                <style>
                    #mfproductlabel_general .hyva {display: block; font-weight: bold;}
                </style>
            ';
        } else {
            $result = '
                <style>
                    #mfproductlabel_general .hyva {display: none;}
                </style>';
        }

        return $result;
    }
}
