/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

var MagefanPL = {
    processConfigurableProductLabel: function (lableEl, maintProductID, selectedProductId) {
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
                    self.replaceLabel(lableEl, maintProductID, selectedProductId)
                }
            });
        } else {
            self.replaceLabel(lableEl, maintProductID, selectedProductId)
        }
    },

    replaceLabel: function (lableEl, maintProductID, selectedProductId) {

        let labelHtml =  window.mfLabelProcessed[maintProductID] && window.mfLabelProcessed[maintProductID][selectedProductId]
            ? window.mfLabelProcessed[maintProductID][selectedProductId] : '';
        console.log(labelHtml);

        if (labelHtml) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = labelHtml;
            const newLable = tempDiv.firstElementChild;

            if (newLable) {
                lableEl.replaceWith(newLable);
            }
        }
    }
};
