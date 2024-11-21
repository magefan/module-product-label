/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

define([
    'jquery',
    'jquery-ui-modules/widget',
    'Magefan_ProductLabel/js/label'
], function ($) {
    'use strict';

    return function (SwatchRenderer) {
        $.widget('mage.SwatchRenderer', SwatchRenderer, {
            _OnClick: function ($this, $widget) {
                this._super($this, $widget);
                this.processLable();
            },

            _OnChange: function ($this, $widget) {
                this._super($this, $widget);
                this.processLable();
            },

            processLable: function () {
                if (this.options.jsonConfig.productId) {
                    let lableEl = null;
    
                    if (this.inProductList) {
                        const listItem = this.element.closest('li.item').get(0); // Convert jQuery object to a native DOM element

                        if (listItem) {
                            lableEl = listItem.querySelector('.mf-label-container');
                        }
                    } else {
                        lableEl = document.querySelector('.mfpl-product-page');
                    }
    
                    console.log({lableEl});
    
                    if (lableEl) {
                        MagefanPL.processConfigurableProductLabel(lableEl, this.options.jsonConfig.productId, this.getProductId());
                    }
                }
            }
        });

        return $.mage.SwatchRenderer;
    };
});
