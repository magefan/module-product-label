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
                if (this.options.jsonConfig.productId) {
                    this.processProductLabel(this.options.jsonConfig.productId, this.getProductId());
                }

                $(document).trigger('mfChildItemSelected', {
                    mainProductId: this.options.jsonConfig.productId,
                    selectedProductId: this.getProductId()});
            },

            processProductLabel: function (maintProductID, selectedProductId) {
                var self = this;
                console.log({maintProductID})
                console.log({selectedProductId})


                if (!window.mfLabelProcessed) {
                    window.mfLabelProcessed = {};
                }

                if (!window.mfLabelProcessed[maintProductID]) {
                    let url = BASE_URL + 'mfpl/label/get?product_ids=' + maintProductID + '&get_children=1&product_page=' +  (this.inProductList ? '0' : '1');

                    MagefanJs.ajax({'url':url, 'type': 'GET',
                        success:  function(response) {
                            response = JSON.parse(response)
                            console.log(response);
                            window.mfLabelProcessed[maintProductID] = response.labels;
                            self.replaceLabel(maintProductID, selectedProductId)
                        }
                    });
                } else {
                    self.replaceLabel(maintProductID, selectedProductId)
                }
            },

            replaceLabel: function (maintProductID, selectedProductId) {
                let mainLableEl = null;

                if (this.inProductList) {
                    mainLableEl = this.element.closest('li.item')
                        .find('.mf-label-container');
                } else {
                    mainLableEl = $('.mfpl-product-page');
                }

                console.log({mainLableEl});
                let labelHtml =  window.mfLabelProcessed[maintProductID] && window.mfLabelProcessed[maintProductID][selectedProductId]
                    ? window.mfLabelProcessed[maintProductID][selectedProductId] : '';
                console.log(labelHtml);

                if (labelHtml) {
                    mainLableEl.replaceWith(labelHtml);
                }
            }
        });

        return $.mage.SwatchRenderer;
    };
});
