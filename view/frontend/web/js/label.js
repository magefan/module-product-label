/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

var MagefanPL = {
    pendingRequests: {},

    processConfigurableProductLabel: function (labelEl, maintProductID, selectedProductId, forProductPage = 0) {
        var self = this;

        if (!window.mfLabelProcessed) {
            window.mfLabelProcessed = {};
        }

        if (window.mfLabelProcessed[maintProductID]) {
            self.replaceLabel(labelEl, maintProductID, selectedProductId);
            return;
        }

        /* one request per product: while it runs only remember the latest selection */
        const isRequestRunning = !!self.pendingRequests[maintProductID];
        self.pendingRequests[maintProductID] = {labelEl: labelEl, selectedProductId: selectedProductId};

        if (isRequestRunning) {
            return;
        }

        const url = `${BASE_URL}mfpl/label/get?product_ids=${maintProductID}&get_children=1&product_page=${forProductPage}`;

        MagefanJs.ajax({'url':url, 'type': 'GET',
            success:  function(response) {
                const latest = self.pendingRequests[maintProductID];
                delete self.pendingRequests[maintProductID];

                response = JSON.parse(response);
                window.mfLabelProcessed[maintProductID] = response.labels;
                self.replaceLabel(latest.labelEl, maintProductID, latest.selectedProductId);
            }
        });
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
