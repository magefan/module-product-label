<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Block;

use Magento\Framework\View\Element\Template;

class Label extends Template
{
    /**
     * @var string
     */
    public $_template = 'Magefan_ProductLabel::label-container.phtml';
}
