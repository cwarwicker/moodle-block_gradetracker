(function (factory) {
    if ( typeof define === 'function' && define.amd ) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // Node/CommonJS style for Browserify
        module.exports = factory(require('jquery'));
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function($) {

    'use strict';

    $.fn.bcNotify = function(options, params) {

        var settings = $.extend({

            content: '',
            type: 'default',
            position: 'r',
            top: 50

        }, options);

        // Variables
        var noteNum = $('.bc-notification').length + 1;
        var last = $('.bc-notification-pos-'+settings.position+':last');

        // Work out top position, based on how many are already on the page
        if ( $(last).length > 0 ){
            var l_top = parseInt($(last).css('top'));
            var l_height = parseInt($(last).css('height'));
            var top = l_top + l_height + 15;
        } else {
            var top = settings.top;
        }

        // Trim content
        settings.content = $.trim(settings.content);

        // Create div
        var div = "<div id='bc-notification-"+noteNum+"' class='bc-notification bc-notification-pos-"+settings.position+" bc-notification-"+settings.type+"' style='top:"+top+"px;'>"+settings.content+"</div>";

        // Append to body
        $('body').append(div);
        $("#bc-notification-"+noteNum).fadeIn();

        // Bindings
        $("#bc-notification-"+noteNum).on('click', function(){
            $(this).fadeOut('slow', function(){
                $(this).remove();
            } );
        });

        return this;

    };

    $.bcNotify = $.fn.bcNotify;

}));