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
            },
            error: function () {
                /* keep the current label, the next selection sends a new request */
                delete self.pendingRequests[maintProductID];
            }
        });
    },

    replaceLabel: function (labelEl, maintProductID, selectedProductId) {
        const labels = window.mfLabelProcessed[maintProductID];
        /* label rendered with the page, shown again when no product is selected */
        const originalLabel = labelEl.mfOriginalLabel || labelEl.cloneNode(true);
        let newLabel;

        if (!selectedProductId) {
            newLabel = originalLabel.cloneNode(true);
        } else if (labels && labels[selectedProductId]) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = labels[selectedProductId];
            newLabel = tempDiv.firstElementChild;
        } else {
            /* selected product has no labels: keep an empty container for the next selection */
            newLabel = originalLabel.cloneNode(false);
        }

        if (newLabel) {
            newLabel.mfOriginalLabel = originalLabel;
            labelEl.replaceWith(newLabel);
        }
    }
};
