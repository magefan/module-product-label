/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

var MagefanPL = {
    processConfigurableProductLabel: function (labelEl, maintProductID, selectedProductId, forProductPage = 0) {
        var self = this;

        if (!window.mfLabelProcessed) {
            window.mfLabelProcessed = {};
        }

        if (!window.mfLabelProcessed[maintProductID]) {
            const url = `${BASE_URL}mfpl/label/get?product_ids=${maintProductID}&get_children=1&product_page=${forProductPage}`;

            MagefanJs.ajax({'url':url, 'type': 'GET',
                success:  function(response) {
                    response = JSON.parse(response)
                    window.mfLabelProcessed[maintProductID] = response.labels;
                    self.replaceLabel(labelEl, maintProductID, selectedProductId)
                }
            });
        } else {
            self.replaceLabel(labelEl, maintProductID, selectedProductId)
        }
    },

    replaceLabel: function (labelEl, maintProductID, selectedProductId) {

        let labelHtml =  window.mfLabelProcessed[maintProductID] && window.mfLabelProcessed[maintProductID][selectedProductId]
            ? window.mfLabelProcessed[maintProductID][selectedProductId] : '';

        if (labelHtml) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = labelHtml;
            const newLabel = tempDiv.firstElementChild;

            if (newLabel) {
                labelEl.replaceWith(newLabel);
            }
        }
    }
};
