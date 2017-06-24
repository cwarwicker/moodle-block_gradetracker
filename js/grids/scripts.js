var o;
var isDebugging = false;
var tmpDate;


$(document).ready( function(){
    
    // Menus
    if ( $('ul.slimmenu').length > 0 )
    {
        $('ul.slimmenu').slimmenu(
        {
            resizeWidth: '850',
            collapserTitle: M.util.get_string('mainmenu', 'block_gradetracker'),
            easingEffect:'easeInOutQuint',
            animSpeed:'medium',
            indentChildren: true
        });
    }
} );



function grid_bindings(){

    $(document).ready( function(){

        // Date pickers
        $('.gt_criterion_date').datepicker( {
        
            dateFormat: "dd-mm-yy",
            showButtonPanel: true,
            
            beforeShow: function(){
                
                tmpDate = $(this).val();
                
                var old_fn = $.datepicker._updateDatepicker;

                $.datepicker._updateDatepicker = function(inst) {
                    
                   old_fn.call(this, inst);

                   var buttonPane = $(this).datepicker("widget").find(".ui-datepicker-buttonpane");
                   
                   // Clear existing buttons
                   $(buttonPane).html('');

                   // Append our button
                   $("<button type='button' class='ui-datepicker-clean ui-state-default ui-priority-primary ui-corner-all'>"+M.util.get_string('clear', 'block_gradetracker')+"</button>").appendTo(buttonPane).click(function(ev) {
                       $.datepicker._clearDate(inst.input);
                   }) ;
                   
                };
                
            },
            
            onClose: function(date){

                // If the date hasn't change from what it was when we opened the datepicker, stop
                if (date === tmpDate){
                    return false;
                }

                var TD = $($(this).parents('td')[0]);
                var sID = $(TD).attr('sID');
                var qID = $(TD).attr('qID');
                var uID = $(TD).attr('uID');
                var cID = $(TD).attr('cID');
                
                var rID = ( $(TD).attr('rID') != undefined ) ? $(TD).attr('rID') : 0;
                var obNum = ($(this).attr('observationNum') != undefined) ? $(this).attr('observationNum') : 0;

                var params = { studentID: sID, qualID: qID, unitID: uID, critID: cID, rID: rID, date: date, obNum: obNum };

                $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_criterion', params: params}, function(data){
                                
                    // If empty data, must have been an error
                    if (data.length === 0){

                        // Highlight cell red
                        $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                    } else {

                        // Was ok, so let's do stuff
                        $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                        applyAwardUpdates(data, sID, qID, uID);

                    }

                });

            }
        } );
        
        
        // Criterion Award Date only
        $('.gt_criterion_award_date').datepicker({
        
            dateFormat: "dd-mm-yy",
            showButtonPanel: true,
            
            beforeShow: function(){
                
                tmpDate = $(this).val();
                
                var old_fn = $.datepicker._updateDatepicker;

                $.datepicker._updateDatepicker = function(inst) {
                    
                   old_fn.call(this, inst);

                   var buttonPane = $(this).datepicker("widget").find(".ui-datepicker-buttonpane");
                   
                   // Clear existing buttons
                   $(buttonPane).html('');

                   // Append our button
                   $("<button type='button' class='ui-datepicker-clean ui-state-default ui-priority-primary ui-corner-all'>"+M.util.get_string('clear', 'block_gradetracker')+"</button>").appendTo(buttonPane).click(function(ev) {
                       $.datepicker._clearDate(inst.input);
                   }) ;
                   
                };
                
            },
            
            onClose: function(date){

                // If the date hasn't change from what it was when we opened the datepicker, stop
                if (date === tmpDate){
                    return false;
                }

                var TD = $($(this).parents('td')[0]);
                var sID = $(TD).attr('sID');
                var qID = $(TD).attr('qID');
                var uID = $(TD).attr('uID');
                var cID = $(TD).attr('cID');

                var params = { studentID: sID, qualID: qID, unitID: uID, critID: cID, date: date };

                $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_criterion_award_date', params: params}, function(data){
                                
                    // If empty data, must have been an error
                    if (data.length === 0){

                        // Highlight cell red
                        $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                    } else {

                        // Was ok, so let's do stuff
                        $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );

                    }

                });

            }
            
        } );
        
        
        
        // Range Observation Award Date
        $('.gt_range_observation_award_date').datepicker({
        
            dateFormat: "dd-mm-yy",
            showButtonPanel: true,
            
            beforeShow: function(){
                
                tmpDate = $(this).val();
                
                var old_fn = $.datepicker._updateDatepicker;

                $.datepicker._updateDatepicker = function(inst) {
                    
                   old_fn.call(this, inst);

                   var buttonPane = $(this).datepicker("widget").find(".ui-datepicker-buttonpane");
                   
                   // Clear existing buttons
                   $(buttonPane).html('');

                   // Append our button
                   $("<button type='button' class='ui-datepicker-clean ui-state-default ui-priority-primary ui-corner-all'>"+M.util.get_string('clear', 'block_gradetracker')+"</button>").appendTo(buttonPane).click(function(ev) {
                       $.datepicker._clearDate(inst.input);
                   }) ;
                   
                };
                
            },
            
            onClose: function(date){

                // If the date hasn't change from what it was when we opened the datepicker, stop
                if (date === tmpDate){
                    return false;
                }

                var TD = $($(this).parents('td')[0]);
                var sID = $(this).attr('sID');
                var qID = $(this).attr('qID');
                var uID = $(this).attr('uID');
                var rID = $(this).attr('rID');
                var obNum = $(this).attr('observationNum');

                var params = { studentID: sID, qualID: qID, unitID: uID, rangeID: rID, obNum: obNum, date: date };

                $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_range_observation_award_date', params: params}, function(data){
                                
                    // If empty data, must have been an error
                    if (data.length === 0){

                        // Highlight cell red
                        $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                    } else {

                        // Was ok, so let's do stuff
                        $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                        applyAwardUpdates(data, sID, qID, uID);

                    }

                });

            }
        } );
        
        
        
        
        // Tick criterion checkboxes
        $('.gt_criterion_checkbox').unbind('click');
        $('.gt_criterion_checkbox').bind('click', function(){
            
            var TD = $($(this).parents('td')[0]);
            var cell = $(this);
            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var uID = $(TD).attr('uID');
            var cID = $(TD).attr('cID');
            var met = ($(this).prop('checked')) ? 1 : 0;
            
            var rID = ( $(TD).attr('rID') != undefined ) ? $(TD).attr('rID') : 0;
            var obNum = ($(this).attr('observationNum') != undefined) ? $(this).attr('observationNum') : 0;

            var params = { studentID: sID, qualID: qID, unitID: uID, critID: cID, rID: rID, met: met, obNum: obNum };
            
            $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_criterion', params: params}, function(data){
                                
                // If empty data, must have been an error
                if (data.length === 0){
                    
                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                    
                    // Set checked property back to what it was
                    $(cell).prop('checked', !met);
                    
                } else {
                    
                    // Was ok, so let's do stuff
                    $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    applyAwardUpdates(data, sID, qID, uID);
                    
                }
                
            });
            
            
        });
        
        
        
        // Select menu criterion
        $('.gt_criterion_select').unbind('change');
        $('.gt_criterion_select').bind('change', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var uID = $(TD).attr('uID');
            var cID = $(TD).attr('cID');
            var value = $(this).val();
            
            // Ranged Criteria have a few extra bits
            var rID = ( $(TD).attr('rID') != undefined ) ? $(TD).attr('rID') : 0;
            var obNum = ($(this).attr('observationNum') != undefined) ? $(this).attr('observationNum') : 0;
            
            var params = { studentID: sID, qualID: qID, unitID: uID, critID: cID, rID: rID, obNum: obNum, value: value };
            
            $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_criterion', params: params}, function(data){
                                
                // If empty data, must have been an error
                if (data.length === 0){
                    
                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                                        
                } else {
                    
                    // Was ok, so let's do stuff
                    $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    
                    applyAwardUpdates(data, sID, qID, uID);
                    
                }
                
            });
            
        });
        
        
        // Standard Criterion - sub criteria popup
        $('.gt_open_criterion_window').unbind('click');
        $('.gt_open_criterion_window').bind('click', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var uID = $(TD).attr('uID');
            var cID = $(TD).attr('cID');
            var cName = $(TD).attr('cName');
            var access = $('#gt-access').val();
            
            // Load content from AJAX?
            
            $(document).bcPopUp( {
                title: cName,
                buttons: {
                    'Save': function(){
                        
                        $('#gt_popup_loader').show();
                        $('#gt_popup_error').hide();
                        $('#gt_popup_success').hide();
                        $($('.bc-modal-body')[0]).scrollTop(0);
                        
                        var params = new Array();
                        
                        // Find all the wrappers 
                        $('.gt_criterion_wrapper .gt_update_comments').each( function(){
                            
                            var qID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('qID');
                            var uID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('uID');
                            var cID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('cID');
                            var sID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('sID');
                            var val = $(this).val();
                            
                            params.push( { studentID: sID, qualID: qID, unitID: uID, critID: cID, value: val } );
                            
                        } );
                        
                        
                        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_sub_criterion_comments', params: params}, function(data){
                            
                            $('#gt_popup_loader').hide();
                            
                            // If empty data, must have been an error
                            if (data.length === 0){

                                $('#gt_popup_error').fadeIn();
                                
                            } else {

                                $('#gt_popup_success').fadeIn();
                                
                            }
                            
                        });
                        
                    }
                },
                open: function(){
                    $('.bc-modal-body').html('<img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/ajax-loader.gif" />');
                    $('.bc-modal-body').load(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_criterion_popup', params: {studentID: sID, qualID: qID, unitID: uID, critID: cID, access: access}}, function(){
                        grid_bindings();
                    });
                },
                allowMultiple: false
            } );
            
            
        });
        
        // Detail Criterion - sub criteria popup
        $('.gt_open_detail_criterion_window').unbind('click');
        $('.gt_open_detail_criterion_window').bind('click', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var uID = $(TD).attr('uID');
            var cID = $(TD).attr('cID');
            var cName = $(TD).attr('cName');
            var access = $('#gt-access').val();
                        
            $(document).bcPopUp( {
                title: cName,
                buttons: {
                    'Save': function(){
                        
                        $('#gt_detail_popup_loader').show();
                        $('#gt_detail_popup_error').hide();
                        $('#gt_detail_popup_success').hide();
                        $($('.bc-modal-body')[0]).scrollTop(0);
                        
                        var params = new Array();
                        
                        // Find all the wrappers 
                        $('.gt_detail_criterion_wrapper .gt_update_comments, .gt_detail_criterion_wrapper .gt_update_custom_value').each( function(){
                            
                            var qID = $($(this).parents('.gt_detail_criterion_wrapper')[0]).attr('qID');
                            var uID = $($(this).parents('.gt_detail_criterion_wrapper')[0]).attr('uID');
                            var cID = $($(this).parents('.gt_detail_criterion_wrapper')[0]).attr('cID');
                            var sID = $($(this).parents('.gt_detail_criterion_wrapper')[0]).attr('sID');
                            var type = $(this).attr('type');
                            var val = $(this).val();
                            
                            params.push( { studentID: sID, qualID: qID, unitID: uID, critID: cID, type: type, value: val } );
                            
                        } );
                        
                        
                        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_detail_criterion', params: params}, function(data){
                            
                            $('#gt_detail_popup_loader').hide();
                            
                            // If empty data, must have been an error
                            if (data.length === 0){

                                $('#gt_detail_popup_error').fadeIn();
                                
                            } else {

                                $('#gt_detail_popup_success').fadeIn();
                                
                            }
                            
                        });
                        
                    }
                },
                open: function(){
                    $('.bc-modal-body').html('<img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/ajax-loader.gif" />');
                    $('.bc-modal-body').load(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_criterion_popup', params: {studentID: sID, qualID: qID, unitID: uID, critID: cID, access: access}}, function(){
                        grid_bindings();
                    });
                },
                allowMultiple: false
            } );
            
            
        });
        
        
        
        // Numeric Criterion - sub criteria popup
        $('.gt_open_numeric_criterion_window').unbind('click');
        $('.gt_open_numeric_criterion_window').bind('click', function(e){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var uID = $(TD).attr('uID');
            var cID = $(TD).attr('cID');
            var cName = $(TD).attr('cName');
            var access = $('#gt-access').val();
                        
            $(document).bcPopUp( {
                title: cName,
                buttons: {
                    'Save': function(){
                        
                        $('#gt_popup_loader').show();
                        $('#gt_popup_error').hide();
                        $('#gt_popup_success').hide();
                        $($('.bc-modal-body')[0]).scrollTop(0);
                        
                        var params = new Array();
                        
                        // Find all the wrappers 
                        $('.gt_criterion_wrapper .gt_update_comments').each( function(){
                            
                            var qID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('qID');
                            var uID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('uID');
                            var cID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('cID');
                            var sID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('sID');
                            var val = $(this).val();
                            
                            params.push( { studentID: sID, qualID: qID, unitID: uID, critID: cID, value: val } );
                            
                        } );
                        
                        
                        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_sub_criterion_comments', params: params}, function(data){
                            
                            $('#gt_popup_loader').hide();
                            
                            // If empty data, must have been an error
                            if (data.length === 0){

                                $('#gt_popup_error').fadeIn();
                                
                            } else {

                                $('#gt_popup_success').fadeIn();
                                
                            }
                            
                        });
                        
                    }
                },
                open: function(){
                    $('.bc-modal-body').html('<img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/ajax-loader.gif" />');
                    $('.bc-modal-body').load(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_criterion_popup', params: {studentID: sID, qualID: qID, unitID: uID, critID: cID, access: access}}, function(){
                        grid_bindings();
                        gtCentreElement( $('.bc-modal') );
                    });
                },
                allowMultiple: false
            } );
            
            e.preventDefault();
            
        });
        
        $('.gt_update_numeric_point').unbind('click');
        $('.gt_update_numeric_point').bind('click', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(this).attr('sID');
            var qID = $(this).attr('qID');
            var uID = $(this).attr('uID');
            var cID = $(this).attr('cID');
            var rID = ( $(this).attr('rID') !== undefined ) ? $(this).attr('rID') : 0;
            var value = $(this).val();
            
            var params = { studentID: sID, qualID: qID, unitID: uID, critID: cID, rangeID: rID, value: value };
            
            $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_numeric_point', params: params}, function(data){
                                
                var response = $.parseJSON(data);
                                
                // If empty data, must have been an error
                if (data.length === 0){
                    
                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                                        
                } else {
                    
                    // Total points
                    $('#gt_total_points').text(response.points);
                    
                    // Criterion award
                    if (response.awardID > 0 && response.awardCriterion > 0){
                        $('#gt_criterion_value_' + response.awardCriterion + ' select').val(response.awardID);
                        $('#gt_criterion_value_' + response.awardCriterion).effect( 'highlight', {color: '#ccff66'}, 1000 );
                    } else if (response.awardID === false && response.awardCriterion > 0){
                        $('#gt_criterion_value_' + response.awardCriterion + ' select').val('0');
                        $('#gt_criterion_value_' + response.awardCriterion).effect( 'highlight', {color: '#ccff66'}, 1000 );
                    }
                                        
                    // Parent (if in range)
                    if (response.parentAwardID > 0 && response.parentCriterion > 0){
                        $('#gt_criterion_value_' + response.parentCriterion + ' select').val(response.parentAwardID);
                        $('#gt_criterion_value_' + response.parentCriterion).effect( 'highlight', {color: '#ccff66'}, 1000 );
                    } else if (response.parentAwardID === false && response.parentCriterion > 0){
                        $('#gt_criterion_value_' + response.parentCriterion + ' select').val('0');
                        $('#gt_criterion_value_' + response.parentCriterion).effect( 'highlight', {color: '#ccff66'}, 1000 );
                    }
                    
                    // Highlight cell
                    $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    
                }
                
            });
            
        });
        
        
        
        // Ranged Criterion - sub criteria popup
        $('.gt_open_ranged_criterion_window').unbind('click');
        $('.gt_open_ranged_criterion_window').bind('click', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var uID = $(TD).attr('uID');
            var cID = $(TD).attr('cID');
            var cName = $(TD).attr('cName');
            var access = $('#gt-access').val();
                        
            $(document).bcPopUp( {
                title: cName,
                buttons: {
                    'Save': function(){
                        
                        $('#gt_popup_loader').show();
                        $('#gt_popup_error').hide();
                        $('#gt_popup_success').hide();
                        $($('.bc-modal-body')[0]).scrollTop(0);
                        
                        var params = new Array();
                        
                        // Find all the wrappers 
                        $('.gt_criterion_wrapper .gt_update_comments').each( function(){
                            
                            var qID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('qID');
                            var uID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('uID');
                            var cID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('cID');
                            var sID = $($(this).parents('.gt_criterion_wrapper')[0]).attr('sID');
                            var val = $(this).val();
                            
                            params.push( { studentID: sID, qualID: qID, unitID: uID, critID: cID, value: val } );
                            
                        } );
                        
                        
                        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_sub_criterion_comments', params: params}, function(data){
                            
                            $('#gt_popup_loader').hide();
                            
                            // If empty data, must have been an error
                            if (data.length === 0){

                                $('#gt_popup_error').fadeIn();
                                
                            } else {

                                $('#gt_popup_success').fadeIn();
                                
                            }
                            
                        });
                        
                    }
                },
                open: function(){
                    $('.bc-modal-body').html('<img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/ajax-loader.gif" />');
                    $('.bc-modal-body').load(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_criterion_popup', params: {studentID: sID, qualID: qID, unitID: uID, critID: cID, access: access}}, function(){
                        grid_bindings();
                    });
                },
                allowMultiple: false
            } );
            
            
        });
        
        
        
        
        
        // OPen comments popup for normal criteria
        $('.gt_comment_icon').unbind('click');
        $('.gt_comment_icon').bind('click', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var uID = $(TD).attr('uID');
            var cID = $(TD).attr('cID');
            var cName = $(TD).attr('cName');
                        
            // Load popup
            $(document).bcPopUp( {
                title: M.util.get_string('comments', 'block_gradetracker') + ' - ' + cName,
                buttons: {
                    
                    'Save': function(){
                        
                        $($('.bc-modal-body')[0]).scrollTop(0);
                        
                        var TB = $($('.gt_criterion_comments_textbox')[0]);
                        var TBDIV = $($(TB).parents('div')[0]);   
                        var qID = $(TB).attr('qID');
                        var uID = $(TB).attr('uID');
                        var sID = $(TB).attr('sID');
                        var cID = $(TB).attr('cID');
                        var value = $.trim($(TB).val());
                        
                        var gridTD = $('#CRITERION_Q_'+qID+'U_'+uID+'C_'+cID+'S_'+sID);
                        
                        var params = { studentID: sID, qualID: qID, unitID: uID, critID: cID, value: value };
                        
                        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_criterion_comments', params: params}, function(data){
                            
                            // If empty data, must have been an error
                            if (data.length === 0){

                                // Highlight cell red
                                $(TBDIV).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                            } else {

                                // Brief highlight
                                $(TBDIV).effect( 'highlight', {color: '#ccff66'}, 3000 );
                                
                                // Add class to cell
                                if ( value.length > 0 && !$(gridTD).hasClass('gt_has_comments') ){
                                    $(gridTD).addClass('gt_has_comments');
                                } else if(value.length === 0) {
                                    $(gridTD).removeClass('gt_has_comments');
                                }
                                
                                // Change icon to edit 
                                var icon = (value.length > 0) ? 'comment_edit.png' : 'comment_add.png';
                                $($(gridTD).find('img.gt_comment_icon')[0]).attr('src', M.cfg.wwwroot + '/blocks/gradetracker/pix/'+icon);
                                
                                // Close popup
                                $('.bc-modal').each(function() {
                                    $(this).bcPopUp('close');
                                });
                                
                            }
                            
                        });


                        
                    }
                },
                open: function(){
                    $('.bc-modal-body').html('<img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/ajax-loader.gif" />');
                    $('.bc-modal-body').load(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_criterion_comment_popup', params: {studentID: sID, qualID: qID, unitID: uID, critID: cID}}, function(){
                        grid_bindings();
                    });
                },
                allowMultiple: false
            } );
            
            
        });
        
               
               
        // Open comments popup for formal assessment
        $('.gt_assessment_comment_edit').unbind('click');
        $('.gt_assessment_comment_edit').bind('click', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var aID = $(TD).attr('aID');
            var qName = $(TD).attr('qName');
            var aName = $(TD).attr('aName');
                        
            // Load popup
            $(document).bcPopUp( {
                title: M.util.get_string('comments', 'block_gradetracker') + ' - ' + qName + ' - ' + aName,
                buttons: {
                    
                    'Save': function(){
                        
                        $($('.bc-modal-body')[0]).scrollTop(0);
                        
                        // Start loading gif
                        $('#gt_comment_loading').show();
                        
                        var TB = $($('.gt_assessment_comments_textbox')[0]);
                        var TBDIV = $($(TB).parents('div')[0]);   
                        var qID = $(TB).attr('qID');
                        var aID = $(TB).attr('aID');
                        var sID = $(TB).attr('sID');
                        var value = $.trim($(TB).val());
                                                
                        var params = { studentID: sID, qualID: qID, assID: aID, value: value };
                        
                        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_assessment_comments', params: params}, function(data){
                            
                            // If empty data, must have been an error
                            if (data.length === 0){

                                // Highlight cell red
                                $(TBDIV).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                                $('#gt_comment_loading').hide();
                                alert( M.util.get_string('error') );

                            } else {

                                // Hide loading gif
                                $('#gt_comment_loading').hide();
                                
                                // Add class to cell
                                if ( value.length > 0 && !$(TD).hasClass('gt_has_comments') ){
                                    $(TD).addClass('gt_has_comments');
                                } else if(value.length === 0) {
                                    $(TD).removeClass('gt_has_comments');
                                }
                                
                                // Change icon to edit 
                                var icon = (value.length > 0) ? 'comment_edit.png' : 'comment_add.png';
                                $($(TD).find('img.gt_assessment_comment_edit')[0]).attr('src', M.cfg.wwwroot + '/blocks/gradetracker/pix/'+icon);
                                
                                // Close popup
                                $('.bc-modal').each(function() {
                                    $(this).bcPopUp('close');
                                });
                                
                            }
                            
                        });
                        
                    }
                    
                },
                open: function(){
                    $('.bc-modal-body').html('<img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/ajax-loader.gif" />');
                    $('.bc-modal-body').load(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_assessment_comment_popup', params: {studentID: sID, qualID: qID, assID: aID}}, function(){
                        grid_bindings();
                    });
                },
                allowMultiple: false
            } );
            
            
        });
        
               
               
               
        
        
        // Unit Award Select Menu
        $('.gt_grid_unit_award').unbind('change');
        $('.gt_grid_unit_award').bind('change', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var uID = $(TD).attr('uID');
            var value = $(this).val();
            
            if ( $(TD).length == 0 ) return false;
            
            
            var params = { studentID: sID, qualID: qID, unitID: uID, value: value };
            
            $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_unit', params: params}, function(data){
                                
                // If empty data, must have been an error
                if (data.length === 0){
                    
                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                                        
                } else {
                    
                    // Was ok, so let's do stuff
                    applyAwardUpdates(data, sID, qID, uID);
                    $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    
                }
                
            });
            
        });
        
        
        // Unit info popup
        $('a.gt_unit_info').unbind('click');
        $('a.gt_unit_info').bind('click', function(e){
            
            var uID = $(this).attr('uID');
            var uName = $(this).attr('uName');
            
            // Check for external session, e.g. if we are viewing from Parent Portal
            var external = ($('#gt-external').length > 0) ? 1 : 0;
            var extSsn = ($('#gt-ext-sid').length === 1) ? $('#gt-ext-sid').val() : 0;
            
            $('#pUUI_'+uID).bcPopUp( {
                title: uName,
                open: function(){
                    $('#pUUI_'+uID+' .bc-modal-body').html('<img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/ajax-loader.gif" />');
                    $('#pUUI_'+uID+' .bc-modal-body').load(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_unit_info_popup', params: {unitID: uID, external: external, extSsn: extSsn}}, function(){
                        grid_bindings();
                    });
                },
                allowMultiple: true,
                showOverlay: false
            } );
            
            e.preventDefault();
            
        });
        
        
        
        // Criterion Info Popup
        $('td.gt_grid_cell_v').unbind('click');
        $('td.gt_grid_cell_v').bind('click', function(){
            
            var sID = $(this).attr('sID');
            var qID = $(this).attr('qID');
            var uID = $(this).attr('uID');
            var cID = $(this).attr('cID');
            var cName = $(this).attr('cName');
            
            // Check for external session, e.g. if we are viewing from Parent Portal
            var external = ($('#gt-external').length > 0) ? 1 : 0;
            var extSsn = ($('#gt-ext-sid').length === 1) ? $('#gt-ext-sid').val() : 0;
                        
            $('#pU_'+sID+'_'+qID+'_'+uID+'_'+cID).bcPopUp( {
                title: cName,
                open: function(){
                    $('#pU_'+sID+'_'+qID+'_'+uID+'_'+cID+' .bc-modal-body').html('<img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/ajax-loader.gif" />');
                    $('#pU_'+sID+'_'+qID+'_'+uID+'_'+cID+' .bc-modal-body').load(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_criterion_info_popup', params: {studentID: sID, qualID: qID, unitID: uID, critID: cID, external: external, extSsn: extSsn}}, function(){
                        grid_bindings();
                    });
                },
                allowMultiple: true,
                showOverlay: false
            } );
            
        });
        
        
        // Assessment Info Popup
        $('td.gt_assessment_grid_cell_v').unbind('click');
        $('td.gt_assessment_grid_cell_v').bind('click', function(){
            
            var sID = $(this).attr('sID');
            var qID = $(this).attr('qID');
            var aID = $(this).attr('aID');
            var aName = $(this).attr('aName');
            
            // Check for external session, e.g. if we are viewing from Parent Portal
            var external = ($('#gt-external').length > 0) ? 1 : 0;
            var extSsn = ($('#gt-ext-sid').length === 1) ? $('#gt-ext-sid').val() : 0;
            
                        
            $('#pU_'+sID+'_'+qID+'_'+aID).bcPopUp( {
                title: aName,
                open: function(){
                    $('#pU_'+sID+'_'+qID+'_'+aID+' .bc-modal-body').html('<img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/ajax-loader.gif" />');
                    $('#pU_'+sID+'_'+qID+'_'+aID+' .bc-modal-body').load(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_assessment_info_popup', params: {studentID: sID, qualID: qID, assID: aID, external: external, extSsn: extSsn}}, function(){
                        grid_bindings();
                    });
                },
                allowMultiple: true,
                showOverlay: false
            } );
            
        });
        
        
        
        // Load range info into the popup
        $('.gt_load_range').unbind('click');
        $('.gt_load_range').bind('click', function(e){
            
            $(this).parents('ul.gt_tabbed_list').find('li').removeClass('active');
            $(this).parent().addClass('active');
            var infoDiv = $($(this).parents('div.bc-modal-body').find('div#gt_popup_range_info')[0]);
            
            $(infoDiv).html('<img src="'+M.util.image_url('i/loading_small')+'" alt="'+M.util.get_string('loading', 'block_gradetracker')+'" />');
            
            var sID = $(this).attr('sID');
            var qID = $(this).attr('qID');
            var uID = $(this).attr('uID');
            var cID = $(this).attr('cID');
            var editing = $(this).attr('editing');
            
            var params = { studentID: sID, qualID: qID, unitID: uID, critID: cID, editing: editing };
            
            $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_range_info', params: params}, function(data){
                                
                $(infoDiv).html(data);
                grid_bindings();

            });
            
            e.preventDefault();
            
            
        });
        
        
        $('.gt_add_ranged_observation').unbind('click');
        $('.gt_add_ranged_observation').bind('click', function(){
            
            var TBL = $('#gt_ranged_observations_table');
            var rows = $(TBL).find('tr');
            var cnt = $(TBL).find('th.gt_obnum').length;
            var num = cnt + 1;
            
            $.each(rows, function(){
                
                var th = $(this).find('th:nth-last-child(2)');
                var td = $(this).find('td:nth-last-child(1)');

                // Header row
                if (th.length > 0){
                    $(th).after('<th class="gt_obnum gt_c">'+num+'</th>');
                } else if (td.length > 0){
                    
                    var newCell = $(td).clone();
                    $(newCell).find('select').attr('observationNum', num).val(0).prop('checked', false);
                    $(newCell).find('input').attr('observationNum', num).val('').prop('checked', false);
                    $(newCell).find('input').removeClass('gt_hasDatepicker');
                    $(newCell).find('input').attr('id', $(newCell).find('input').attr('id') + '_' + num);
                    $(td).after(newCell);
                    
                }
                
            });
            
            grid_bindings();
            
        });
        
        
        
        
        // Update target grades from grid
        $('.gt_target_grade_award').unbind('change');
        $('.gt_target_grade_award').bind('change', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(this).attr('sID');
            var qID = $(this).attr('qID');
            var val = $(this).val();
            var params = {sID: sID, qID: qID, type: 'target', awardID: val};
            
            $.post( M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', { action: 'update_user_grade', params: params }, function(data){
                
                // If empty data, must have been an error
                if (data.length === 0){
                    
                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                                        
                } else {
                    
                    data = $.parseJSON(data);
                    
                    // Was ok, so let's do stuff
                    $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    
                    $('.gt_tg_Q'+qID+'_S'+sID+'_view').text( data.grade );
                    
                }
                
            } );
            
        });
        
        
        // Update aspirational grades from grid
        $('.gt_asp_grade_award').unbind('change');
        $('.gt_asp_grade_award').bind('change', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(this).attr('sID');
            var qID = $(this).attr('qID');
            var val = $(this).val();
            var params = {sID: sID, qID: qID, type: 'aspirational', awardID: val};
            
            $.post( M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', { action: 'update_user_grade', params: params }, function(data){
                
                // If empty data, must have been an error
                if (data.length === 0){
                    
                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                                        
                } else {
                    
                    data = $.parseJSON(data);
                    
                    // Was ok, so let's do stuff
                    $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    
                    $('.gt_asp_Q'+qID+'_S'+sID+'_view').text( data.grade );
                    
                }
                
            } );
            
        });
        
        
        
        
        // Select menu for assessment grids
        $('.gt_assessment_select').unbind('change');
        $('.gt_assessment_select').bind('change', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var aID = $(TD).attr('aID');
            var type = $(TD).attr('type');
            var value = $(this).val();
            
            var gradingMethod = $(this).attr('gradingMethod');
            
            var params = { studentID: sID, qualID: qID, assessmentID: aID, type: type, value: value, gradingMethod: gradingMethod };
            
            $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_assessment', params: params}, function(data){
                                
                // If empty data, must have been an error
                if (data.length === 0){
                    
                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                                        
                } else {
                    
                    // Was ok, so let's do stuff
                    $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    
                    //applyAwardUpdates(data, sID, qID, uID);
                    
                }
                
            });
            
        });
        
        
        // Internal Verification (IV) - Who
        $('.gt_stud_unit_IV_who').off('change');
        $('.gt_stud_unit_IV_who').on('change', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var uID = $(TD).attr('uID');
            var value = $(this).val();
            
            var params = { type: 'unit', attribute: 'IV_who', studentID: sID, qualID: qID, unitID: uID, value: value };
            
            $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_user_attribute', params: params}, function(data){
                                
                // If empty data, must have been an error
                if (data.length === 0){
                    
                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                                        
                } else {
                    
                    // Was ok, so let's do stuff
                    $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    
                }
                
            });
            
        });
        
        
        // Internal Verification (IV) - Date
        $('.gt_stud_unit_IV_date').datepicker( {
        
            dateFormat: "dd-mm-yy",
            showButtonPanel: true,
            
            beforeShow: function(){
                                
                var old_fn = $.datepicker._updateDatepicker;

                $.datepicker._updateDatepicker = function(inst) {
                    
                   old_fn.call(this, inst);

                   var buttonPane = $(this).datepicker("widget").find(".ui-datepicker-buttonpane");
                   
                   // Clear existing buttons
                   $(buttonPane).html('');

                   // Append our button
                   $("<button type='button' class='ui-datepicker-clean ui-state-default ui-priority-primary ui-corner-all'>"+M.util.get_string('clear', 'block_gradetracker')+"</button>").appendTo(buttonPane).click(function(ev) {
                       $.datepicker._clearDate(inst.input);
                   }) ;
                   
                };
                
            },
            
            onClose: function(date){

                var TD = $($(this).parents('td')[0]);            
                var sID = $(TD).attr('sID');
                var qID = $(TD).attr('qID');
                var uID = $(TD).attr('uID');

                var params = { type: 'unit', attribute: 'IV_date', studentID: sID, qualID: qID, unitID: uID, value: date };

                $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_user_attribute', params: params}, function(data){

                    // If empty data, must have been an error
                    if (data.length === 0){

                        // Highlight cell red
                        $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                    } else {

                        // Was ok, so let's do stuff
                        $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );

                    }

                });

            }
        } );
        
        
        
        
        // Custom Assessment Fields
        var customBoundFunc = function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var aID = $(TD).attr('aID');
            var fID = $(TD).attr('fID');
            var value = $(this).val();
            
            // If checkbox our value is whether or not it is checked
            if ($(this).attr('type') == 'checkbox'){
                var chk = $(this).prop('checked');
                value = chk | 0; // 1 for true, 0 for false
            }
                        
            var params = { studentID: sID, qualID: qID, assessmentID: aID, fieldID: fID, value: value };
            
            $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_assessment_custom_field', params: params}, function(data){
                                
                // If empty data, must have been an error
                if (data.length === 0){
                    
                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                                        
                } else {
                    
                    // Was ok, so let's do stuff
                    $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    
                    // Remove the little warning if there was one
                    $('#q_'+qID+'s_'+sID+'a_'+aID+'f_'+fID+'_msg').remove();
                    
                }
                
            });
            
        };
        
        var tmpLastCustomField = '';
        
        $('select.gt_assessment_custom_field').off('change');
        $('input.gt_assessment_custom_field').off('change').off('click').off('blur');
        $('textbox.gt_assessment_custom_field').off('change').off('click').off('blur');
        
        // Select menus, tetx inputs and textboxes are onChange
        $('select.gt_assessment_custom_field, input[type!="checkbox"].gt_assessment_custom_field, textarea.gt_assessment_custom_field').on('change', customBoundFunc);
        
        // When you blur it, remove the little Unsaved notification, if nothing has changed since we Focussed on it
        $('select.gt_assessment_custom_field, input[type!="checkbox"].gt_assessment_custom_field, textarea.gt_assessment_custom_field').on('blur', function(){
            
            var val = $(this).val();
            if (val == tmpLastCustomField){
                
                var TD = $($(this).parents('td')[0]);            
                var sID = $(TD).attr('sID');
                var qID = $(TD).attr('qID');
                var aID = $(TD).attr('aID');
                var fID = $(TD).attr('fID');
                
                tmpLastCustomField = '';
                
                // Remove the little warning if there was one
                $('#q_'+qID+'s_'+sID+'a_'+aID+'f_'+fID+'_msg').remove();
                
            }
                        
        });
        
        // Checkbox is onClick
        $('input[type="checkbox"].gt_assessment_custom_field').on('click', customBoundFunc);
        
        // Write a little message for these ones reminding people it's not saved until they click away
        $('input[type!="checkbox"].gt_assessment_custom_field, textarea.gt_assessment_custom_field').off('focus');
        $('input[type!="checkbox"].gt_assessment_custom_field, textarea.gt_assessment_custom_field').on('focus', function(){
            
            var TD = $($(this).parents('td')[0]);            
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var aID = $(TD).attr('aID');
            var fID = $(TD).attr('fID');
            
            tmpLastCustomField = $(this).val();
            
            // Remove the little warning if there was one
            $('#q_'+qID+'s_'+sID+'a_'+aID+'f_'+fID+'_msg').remove();
            
            $(this).after('<div id="q_'+qID+'s_'+sID+'a_'+aID+'f_'+fID+'_msg"><small class="gt_label">'+M.util.get_string('unsaved', 'block_gradetracker')+'</small></div>');
            
        });
        
        
        
        
        
        
        

    } );

}


function applyAwardUpdates(result, sID, qID, uID){
    
    
    result = $.parseJSON(result);
    
    // Criteria awards
    if (result.awards !== undefined){
        
        $.each(result.awards, function(i, v) {
            
//            var el = $('#S'+sID+'_Q'+qID+'_U'+uID+'_C'+i);
            var el = $('.gt_grid_cell[sid='+sID+'][cid='+i+'] :input');
            var curVal = el.val();
            
            if (el.length > 0){
                
                if (el.attr('type') == 'checkbox'){
                    
                    if (v > 0){
                        el.prop('checked', true);
                    } else {
                        el.prop('checked', false);
                    }
                    
                } else {
                    el.val(v);
                }
                
                // Highlight parent cell
//                if (curVal != v){
                    $(el.parents('td')[0]).effect( 'highlight', {color: '#ccff66'}, 3000 );
//                }
                
            }
            
        });
        
    }
    
    
    
    // Unit awards
    if (result.unitawards !== undefined){
        
        $.each(result.unitawards, function(i, v) {
            
            var el = $('.S'+sID+'_Q'+qID+'_U'+uID);
            var curVal = el.val();
            
            if (el.length > 0){
                
                el.val(v);
                                    
                // Highlight parent cell - commented out for now, as if you do lots quickly it looks shite
                if (curVal != v){
//                    $(el.parents('td')[0]).effect( 'highlight', {color: '#ccff66'}, 3000 );
                }
                
            }
            
        });
    }    
    
    // Progress
    if (result.progress !== undefined){
        var classNam = "S"+sID+"Q"+qID+"U"+uID;
        var classEleBar = $(".progress_bar_"+classNam);
        var classElePer = $(".progress_percent_"+classNam);
        for (x = 0; x < classEleBar.length; x++){
            classEleBar[x].style.width = result.progress + "%";
        }
        for (x = 0; x < classElePer.length; x++){
            classElePer[x].innerHTML = result.progress + "%";
        }
    }
    
    
    // Refresh Predicted Grades
    refreshPredictedGrades();  
    
    
}



function start_script_debugging(){
    
    if ($('.gt_debug_start').hasClass('gt_img_disable')){
        return false;
    }
    
    $.post( M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', { action: 'set_debugging', params: {value: 1} }, function(){
     
        $('.gt_debug_start').addClass('gt_img_disable');
        $('.gt_debug_stop').removeClass('gt_img_disable');
        
        // Add notification
        gtGenerateNotification('warning', M.util.get_string('debuggingrunning', 'block_gradetracker'), 'c');
        
    });
    
}



function stop_script_debugging(){
    
    if ($('.gt_debug_stop').hasClass('gt_img_disable')){
        return false;
    }
    
    $.post( M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', { action: 'set_debugging', params: {value: 0} }, function(){
     
        $('.gt_debug_start').removeClass('gt_img_disable');
        $('.gt_debug_stop').addClass('gt_img_disable');
        
        // Remove notification
        $('.bc-notification').fadeOut('slow', function(){
            $(this).remove();
        });
        
    });
    
}

function clear_debugging_logs(){
    
    $('#gt_loading').show();
    $.post( M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', { action: 'clear_debugging' }, function(){
        $('#gt_loading').hide();
    });
    
}

var keyMap = {16: false, 17: false, 191: false};

function isKeyPressed(keyCode){
    if (keyCode in keyMap){
        return keyMap[keyCode];
    } else {
        return null;
    }
}

// Record key down/up events, so we know when CTRL is pressed
$(document).keydown(function(e){
        
    if (e.keyCode in keyMap){
        keyMap[e.keyCode] = true;
    }

}).keyup(function(e) {

    $.each(keyMap, function(i, item){ 
        keyMap[i] = false; 
    });

});

$(document).ready( function(){
    
    if (isDebugging)
    {
         gtGenerateNotification('warning', M.util.get_string('debuggingrunning', 'block_gradetracker'), 'c'); 
    }
    
} );