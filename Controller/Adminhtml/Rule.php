<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\ProductLabel\Controller\Adminhtml;

/**
 * Class Rule
 */
class Rule extends Actions
{
    /**
     * Form session key
     * @var string
     */
    protected $_formSessionKey  = 'mfproductlabel_rule_form_data';
    /**
     * Allowed Key
     * @var string
     */
    protected $_allowedKey      = 'Magefan_ProductLabel::rule';
    /**
     * Model class name
     * @var string
     */
    protected $_modelClass      = 'Magefan\ProductLabel\Model\Rule';
    /**
     * Active menu key
     * @var string
     */
    protected $_activeMenu      = 'Magefan_ProductLabel::rule';
    /**
     * Status field name
     * @var string
     */
    protected $_statusField     = 'is_active';
}
