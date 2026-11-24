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

        let url = `${BASE_URL}mfpl/label/get?product_ids=${maintProductID}&get_children=1&product_page=${forProductPage}`;

        /* the same url as MagefanJs.ajax sends, so the response stays cacheable */
        if (window.MagefanJs && window.MagefanJs.requestOptionsModifier) {
            url = window.MagefanJs.requestOptionsModifier({url: url}).url;
        }

        fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.json();
            })
            .then(function (response) {
                const latest = self.pendingRequests[maintProductID];

                window.mfLabelProcessed[maintProductID] = response.labels;
                self.replaceLabel(latest.labelEl, maintProductID, latest.selectedProductId);
            })
            .catch(function () {
                /* keep the current label, the next selection sends a new request */
            })
            .finally(function () {
                delete self.pendingRequests[maintProductID];
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
