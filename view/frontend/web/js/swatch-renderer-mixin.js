/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    return function (SwatchRenderer) {
        $.widget('mage.SwatchRenderer', SwatchRenderer, {

            _OnClick: function ($this, $widget) {
                this._super($this, $widget);
                this.dispatchItemSelect();
            },

            _OnChange: function ($this, $widget) {
                this._super($this, $widget);
                this.dispatchItemSelect();
            },

            dispatchItemSelect: function () {
                console.log('---------- ' )
                console.log('mainProductId '  +  this.options.jsonConfig.productId)
                console.log('selectedProductId ' + this.getProductId())

                let previusActiveEl = document.querySelector('.mf-label-container.mf-pl-child.active');
                let newActiveEl = document.querySelector('.mf-label-container.mf-pl-child.mf-pl-' + this.getProductId());

                if (previusActiveEl) {
                    previusActiveEl.classList.remove('active');
                }

                if (newActiveEl) {
                    newActiveEl.classList.add('active');
                }

                $(document).trigger('mfChildItemSelected', {
                    mainProductId: this.options.jsonConfig.productId,
                    selectedProductId: this.getProductId()});
            }
        });

        return $.mage.SwatchRenderer;
    };
});
