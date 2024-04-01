/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'jquery'
], function (Abstract, $) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'Magefan_ProductLabel/form/element/label-type',
            visible: true,
            value:0,
            labelTypes: [
                {'id': 0, 'label': $.mage.__('Image')},
                {'id': 1, 'label': $.mage.__('Shape')},
                {'id': 2, 'label': $.mage.__('Text/HTML')},
            ]
        },

        /**
         * @inheritDoc
         * @return {object}
         */
        initialize: function () {
            this._super();

            // this.index =  this.index = label_type|pp_label_type
            document.body.setAttribute('data_' + this.index, this.value());

            return this;
        },

        isActive: function (data) {
            return parseInt(this.value()) === data.id ? 1 : 0;
        },

        /**
         * @param {object} data
         */
        selectType: function (data) {
            this.value(data.id);

            document.body.setAttribute('data_' + this.index, this.value());

            $('.CodeMirror').each(function(i, el){
                el.CodeMirror.refresh();
            });
        },
    });
});
