require([
    'jquery'
], function ($) {
    'use strict';
    jQuery(document).ready(function(){
        jQuery(document).ajaxStop(function () {
            jQuery("input[name='product[bat_default_attribute]']").prop("disabled",true);
        });
    });
});