<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\ProductLabel\Block\Adminhtml\System\Config\Form;

/**
 * Config options available in Plus and Extra plans
 */
class InfoPlanPlus extends InfoPlan
{
    /**
     * Get min plan
     *
     * @return string
     */
    protected function getMinPlan(): string
    {
        return 'Plus';
    }

    /**
     * Get sections json
     *
     * @return string
     */
    protected function getSectionsJson(): string
    {
        return (string)json_encode([
            'mfproductlabel_general_labels_per_product',
            'mfproductlabel_general_custom_positions',
        ]);
    }

    /**
     * Get text
     *
     * @return string
     */
    protected function getText(): string
    {
        return (string)__("This option is available in <strong>Plus or Extra</strong> plans only.");
    }
}
