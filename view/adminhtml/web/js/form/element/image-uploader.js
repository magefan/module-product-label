/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

define([
    'jquery',
    'Magento_Ui/js/form/element/image-uploader'
], function ($, Element) {
    'use strict';

    return Element.extend({
        onElementRender: function (fileInput) {
            this.updateImageInPreview();
            this._super(fileInput);
        },

        onUpdate: function () {
            this.updateImageInPreview();
            this._super();
        },

        updateImageInPreview() {
            if (typeof LabelHelper === 'undefined') {
                return;
            }

            var self = this,
                section = LabelHelper.getSectionWrapper(self)

            LabelHelper.updateImageInPreview(section);

            setTimeout(function () {
                LabelHelper.updateImageInPreview(section);
            }, 2000)
        }
    });
});
