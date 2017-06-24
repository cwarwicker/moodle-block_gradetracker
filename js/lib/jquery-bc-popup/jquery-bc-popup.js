var escBind = false;

(function($) {


    $.fn.bcPopUp = function(options, params) {

        // Methods

        // Show the overlay
        var _showOverlay = function(n, z, el) {

            if (settings.showOverlay === true) {
                if (el !== undefined) {
                    el.fadeIn(500);
                } else {
                    $('body').append("<div id='bc-overlay-" + n + "' class='bc-overlay' style='z-index:" + z + ";' bc-layer='" + n + "'></div>");
                    $('#bc-overlay-' + n).fadeIn(500);
                }

            }

        };

        // Show the popup
        var _showPopUp = function(el, p, appendTo) {

            el.removeClass('bc-hidden');
            el.show();
            el.appendTo(appendTo);
            el.animate({
                top: parseInt(p)
            }, 500, 'easeOutBack');

        };

        // Close all open popups
        var _closeAll = function() {

            $('.bc-modal').each(function() {
                $(this).bcPopUp('close', {
                    animate: false
                });
            });

        };
        
        // Get the highest z-index
        var _getHighestZIndex = function() {
          
            var h = 0;
            
            $('.bc-modal').each( function(){
                
                var z = $(this).css('z-index');
                if (z > h){
                    h = z;
                }
                
            } );
            
            return h;
            
        };
        
        // Centre the popup
        var _centre = function(el){
            
            var w = $(el).width();
            var posX = ($(window).width() - w) / 2;
            $(el).css('left', posX + 'px');
            
        };



        // Close
        if (options === 'close') {

            var t = this;

            if (!$(t).hasClass('bc-hidden')) {

                var l = this.attr('bc-layer');
                $(t).addClass('bc-hidden');

                // Remove this
                this.animate({
                    top: '-50%'
                }, 500, function() {
                    if ($(t).attr('bc-element') == 0) {
                        $(this).remove();
                    } else {
                        $(this).hide();
                    }
                });

                // Remove the overlay for this layer as well
                $('.bc-overlay[bc-layer="' + l + '"]').fadeOut(500, function() {
                    if ($(t).attr('bc-element') == 0) {
                        $(this).remove();
                    } else {
                        $(this).hide();
                    }
                });

            } else {
                // Something might have gone wrong, so just hide the damn thing
                $(t).hide();
            }

            return false;

        }
        
        else if (options == 'destroy'){
            
            var t = this;
            var l = this.attr('bc-layer');
            $(this).remove();
            $('#bc-overlay-'+l).remove();
            
            return false;
            
        }


        var settings = $.extend({

            title: '',
            content: '',
            open: function(){},
            afterLoad: function(){},
            url: '',
            urlType: 'html',
            buttons: {},
            follow: [{
                x: true,
                y: true
            }],
            allowMultiple: true,
            showHeader: true,
            showFooter: true,
            showCloseButton: true,
            showOverlay: true,
            clickOverlayToClose: true,
            draggable: true,
            expandable: true,
            overrideWidth: false,
            forceRefresh: true,
            appendTo: 'body'

        }, options);


        // Trim the title
        settings.title = $.trim(settings.title);
        
        // Default variables
        var overlayZIndex = 100;
        var zIndex = 101;
        var layerNum = 1;
        var posX = ( $(window).scrollLeft() + 150 ) + 'px';
        var posY = ( $(window).scrollTop() + 50 ) + 'px';
        var buttonArray = new Array();
        
        // Get the height and width of the window
        var wHeight = $(window).height();
        var wWidth = $(window).width();
        
        var isStatic = (settings.allowMultiple === true) ? 1 : 0;

        if (settings.allowMultiple === true) {

            // Find latest one
            var latest = $('.bc-modal:last');
            if ($(latest).length > 0) {

                // Last z-index
                overlayZIndex = parseInt($(latest).css('z-index')) + 1;
                zIndex = parseInt($(latest).css('z-index')) + 2;

                // Count number of modals
                layerNum = $('.bc-modal').length + 1;

                // If we have one already showing, the new one should have a slightly random position
                // to stop it going right on top of the previous one
                if ( $('.bc-modal:not(.bc-hidden)').length > 0 ){
                      var latestVisible = $('.bc-modal:not(.bc-hidden):last');
                      var adjustX = (Math.random() * 100) - 25;
                      var adjustY = (Math.random() * 100) - 25;
                      posX = ( parseInt( $(latestVisible).css('left') ) + adjustX ).toFixed() + 'px';
                      posY = ( parseInt( $(latestVisible).css('top') ) + adjustY ).toFixed() + 'px';
                }

            }

        }
        // Otherwise delete any existing
        else {
            if (settings.forceRefresh === true){
                $('.bc-modal').remove();
                $('.bc-overlay').remove();
            }
        }



        // If it's already been instantiated, just show it
        if ($(this).hasClass('bc-modal')) {
            var el = '.bc-modal-' + $(this).attr('bc-layer');
            var oEl = $('#bc-overlay-' + $(this).attr('bc-layer'));
            // Reset to default of 150px from the left
            posX = 150 + 'px';
            
            // Recalculate Y position, as we may have scrolled since we last opened this one
            posY = ( $(window).scrollTop() + 50 ) + 'px';
            
        } else {

            var oEl = undefined;

            // If we are applying this to an actual element instead of $(document), get the content and the title from the element
            if ($(this).prop('tagName') !== undefined) {

                // If we didn't specify a title, use the one from the element
                if (settings.title === ''){
                    
                    if( $(this).attr('title') !== undefined ){
                        settings.title = $(this).attr('title');
                    } else {
                        settings.title = '';
                    }
                    
                }
                
                // If no content specified, use the content in the element
                if (settings.content === ''){
                    settings.content = $(this).html();
                }
                
                // Apply classes to this element
                $(this).addClass('bc-modal').addClass('bc-modal-' + layerNum);
                $(this).css('left', posX).css('z-index', zIndex);
                $(this).attr('bc-layer', layerNum);
                $(this).attr('bc-static', isStatic);
                
                if (settings.overrideWidth !== false){
                    $(this).css('width', settings.overrideWidth);
                    $(this).css('max-width', settings.overrideWidth);
                }

                // Add the divs
                var currentContent = settings.content;
                var d = "";


            } else {

                // Create a div
                var style = "left:" + posX + ";z-index:" + zIndex + ";";
                if (settings.overrideWidth !== false){
                    style += "width:"+settings.overrideWidth+";max-width:"+settings.overrideWidth+";";
                }
                
                var d = "<div id='' class='bc-modal bc-modal-" + layerNum + "' style='"+style+"' bc-layer='" + layerNum + "' bc-element='0' bc-static='"+isStatic+"'>";
                var currentContent = settings.content;

            }

            // Close link
            d += "<span class='bc-close'>X</span>";

            // Show header if there is a title
            if (settings.title.length > 0 || settings.showHeader === true) {
                d += "<div class='bc-modal-header'><h1>" + settings.title + "</h1></div>";
            }

            // Main content
            d += "<div class='bc-modal-body'>";

            // If we are bringing in from a URL
            if (settings.url.length > 0) {
                if (settings.urlType === 'html') {
                    d += "<iframe class='bc-iframe' scrolling='no' frameborder='0' src='" + settings.url + "'></iframe>";
                } else if (settings.urlType === 'image') {
                    d += "<img src='" + settings.url + "' />";
                } else if (settings.urlType === 'ajax') {
                    d += 'loading...';
                }
            } else {
                d += currentContent;
            }


            d += "</div>";

            // Show footer
            if (settings.showFooter === true) {
                d += "<div class='bc-modal-footer'></div>";
            }

            // Add buttons to footer
            if (settings.showFooter === true) {

                var b = 0;

                for (var indx in settings.buttons) {

                    b++;
                    buttonArray[b] = settings.buttons[indx];
                    var node = $('<div>' + d + '</div>');
                    node.find('.bc-modal-footer').append("<button class='bc-button' bc-button-number='" + b + "'>" + indx + "</button>");
                    d = node.html();

                }

                // Close button
                if (settings.showCloseButton === true) {

                    var node = $('<div>' + d + '</div>');
                    node.find('.bc-modal-footer').append("<button class='bc-close'>Close</button>");
                    d = node.html();

                }


            }

            if ($(this).prop('tagName') !== undefined) {

                $(this).html(d);

            } else {

                d += "</div>";

                // Create the modal
                $('body').append(d);

            }

            var el = '.bc-modal-' + layerNum;

        }



        // Call any open callback
        if (typeof settings.open === "function") {
            settings.open();
        }

        // Load AJAX content
        if (settings.urlType === 'ajax') {
            $(el + ' .bc-modal-body').load(settings.url, {
                cache: false
            }, function(){
                // Call any post-load callbacks
                if (typeof settings.afterLoad === "function") {
                    settings.afterLoad();
                }
            });
        }

        // Show the modal
        _showPopUp($(el), posY, settings.appendTo);
        
        // Call any post-load callbacks
        if (typeof settings.afterLoad === "function" && settings.urlType !== 'ajax') {
            settings.afterLoad();
        }
        
        // Show the overlay
        _showOverlay(layerNum, overlayZIndex, oEl);

        // If we don't have any others yet, centre this one
        if ($('.bc-modal').length === 1) {
            _centre(el);
        }


        // Apply draggable
        if (settings.draggable === true) {
            $(el).draggable({
                handle: '.bc-modal-header',
                containment: 'document'
            });
            $(el + ' .bc-modal-header').css('cursor', 'move');
        }




        // If we are not having multiple ones, then let this one follow our scroll around
        if (settings.allowMultiple === false) {

            var padding = 50;

            $(window).scroll(function() {

                var offset = $(el).offset();
                var w = $(el).width();
                var posX = ($(window).width() - w) / 2;

                // Don't do it if we are dragging as it will act weird
                if (!$(el).hasClass('ui-draggable-dragging') && !$(el).hasClass('bc-hidden') && $(el).attr('bc-static') != 1) {

                    // Follow the scroll vertically				
                    if (settings.follow[0].y === true && settings.follow[0].x === true) {
                        $(el).stop().animate({
                            top: $(window).scrollTop() + padding,
                            left: $(window).scrollLeft() + posX
                        });
                    } else if (settings.follow[0].y === true) {
                        $(el).stop().animate({
                            left: $(window).scrollLeft() + posX
                        });
                    } else if (settings.follow[0].x === true) {
                        $(el).stop().animate({
                            left: $(window).scrollLeft() + posX
                        });
                    }

                }


            });

        }




        // Bind events

        // Close button
        $('.bc-close').unbind('click');
        $('.bc-close').bind('click', function(e) {
            $($(this).parents('.bc-modal')[0]).bcPopUp('close');
            e.preventDefault();
        });

        // Buttons and their actions
        $(el + ' button.bc-button').each(function() {

            var n = $(this).attr('bc-button-number');
            var a = buttonArray[n];
            if (a !== undefined) {
                $(this).unbind('click');
                $(this).bind('click', a);
            }

        });

        // Double click on header to expand/contract
        if (settings.expandable === true) {
            $(el + ' .bc-modal-header').unbind('dblclick');
            $(el + ' .bc-modal-header').dblclick(function() {

                // Return to original size
                if ($(el).hasClass('bc-expanded')) {

                    $(el).removeClass('bc-expanded');

                    $(el).css('top', $(el).attr('bc-old-top'));
                    $(el).css('left', $(el).attr('bc-old-left'));
                    $(el).css('max-width', $(el).attr('bc-old-max-width'));
                    $(el).css('width', $(el).attr('bc-old-width'));
                    $(el).css('max-height', $(el).attr('bc-old-max-height'));
                    $(el).css('height', $(el).attr('bc-old-height'));

                    $(el + ' .bc-modal-body').css('max-height', $(el + ' .bc-modal-body').attr('bc-old-max-height'));
                    $(el + ' .bc-modal-body').css('height', $(el + ' .bc-modal-body').attr('bc-old-height'));

                } else {

                    var l = $(el).css('left');
                    var t = $(el).css('top');
                    var width = $(el).width();
                    var maxWidth = $(el).css('max-width');
                    var diff = $(window).width() - width - 20;
                    var newWidth = width + diff;

                    var height = $(el).height();
                    var maxHeight = $(el).css('max-width');
                    var diff = $(window).height() - height - 20;
                    var newHeight = height + diff;

                    var bodyHeight = $(el + ' .bc-modal-body').height();
                    var bodyMaxHeight = $(el + ' .bc-modal-body').css('max-height');
                    var newBodyHeight = newHeight - 100;

                    $(el).css('max-width', newWidth).css('width', newWidth).css('max-height', newHeight).css('height', newHeight).css('left', 5).css('top', 5);
                    $(el).addClass('bc-expanded');
                    $(el).attr('bc-old-width', width).attr('bc-old-height', height).attr('bc-old-max-width', maxWidth).attr('bc-old-max-height', maxHeight).attr('bc-old-left', l).attr('bc-old-top', t);

                    $(el + ' .bc-modal-body').css('height', newBodyHeight).css('max-height', newBodyHeight);
                    $(el + ' .bc-modal-body').attr('bc-old-height', bodyHeight).attr('bc-old-max-height', bodyMaxHeight);

                }

            });
        }

        // Close this on overlay click
        if (settings.showOverlay === true && settings.clickOverlayToClose === true) {
            $('#bc-overlay-' + layerNum).unbind('click');
            $('#bc-overlay-' + layerNum).bind('click', function() {
                $(el).bcPopUp('close');
            });
        }

        // Clear all on Escape
        if (escBind === false) {
            
            escBind = $(document).keyup(function(e) {

                if (e.which === 27) {
                    _closeAll();
                }

            });
            
        }

        return this;

    };

}(jQuery));