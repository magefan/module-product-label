<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class DynamicRow extends AbstractFieldArray
{
    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('position', ['label' => __('Position'), 'style' => 'width:350px']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
