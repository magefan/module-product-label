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
                this.processLabel();
            },

            _OnChange: function ($this, $widget) {
                this._super($this, $widget);
                this.processLabel();
            },

            processLabel: function () {
                if (this.options.jsonConfig.productId) {
                    let labelEl = null;
    
                    if (this.inProductList) {
                        const listItem = this.element.closest('li.item').get(0); // Convert jQuery object to a native DOM element

                        if (listItem) {
                            labelEl = listItem.querySelector('.mf-label-container');
                        }
                    } else {
                        labelEl = document.querySelector('.mfpl-product-page');
                    }
    
                    if (labelEl) {
                        MagefanPL.processConfigurableProductLabel(
                            labelEl,
                            this.options.jsonConfig.productId,
                            this.getProductId(),
                            this.inProductList ? 0 : 1
                        );
                    }
                }
            }
        });

        return $.mage.SwatchRenderer;
    };
});
