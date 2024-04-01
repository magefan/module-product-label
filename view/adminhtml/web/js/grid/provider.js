/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

define([
    'jquery',
    'Magento_Ui/js/grid/provider'
], function ($, provider) {
    'use strict';

    return provider.extend({
        reload: function (options) {
            var conditionElements = $('.mfproductlabel-what-to-display [data-form-part="mfproductlabel_rule_form"]'
            ), conditions = {};

            $.each(conditionElements, function (index, element) {
                conditions[element.name] = $(element).val();
            });

            var params = {};
            $.each(this.params, function(index, item) {
                var temp = {};
                temp[index] = item;
                $.extend(params, temp);
            });
            $.extend(this.params, conditions);
            $.extend(this.params, {
                'website_ids' : $('[name=website_ids]').val(),
                'catalog_price_rule_ids' : $('[name=catalog_price_rule_ids]').val(),
                'apply_by' : $('[name=apply_by]').val()
            });

            this._super({'refresh': true});

            this.params = params;
        }
    });
});
