define(['jquery', 'jqueryui', 'block_gradetracker/bcpopup', 'block_gradetracker/freezetable', 'block_gradetracker/slimmenu'], function($, ui, bcPopUp, freezeTable, slimmenu) {

  var config = {};
  config.qualification = 0;
  config.unit = 0;
  config.student = 0;
  config.id = 0;
  config.course = 0;
  config.group = 0;
  config.grid = '';
  config.access = '';
  config.tmpDate = '';

  // ANything extra for specific grids
  config.extra = {};

  config.init = function(data){

    // Set grid variables so we know what we're looking at
    config.grid = data.type;
    config.access = $('#gt-access').val();
    config.course = data.courseID;
    config.group = data.groupID;
    config.qualification = data.qualID;
    config.id = data.id;

    if (config.grid === 'student'){
      config.student = data.id;
    } else if(config.grid === 'unit'){
      config.unit = data.id;
    }

    // Extras
    // Mass Update for Unit grid
    if (data.massUpdate !== undefined){
      config.extra.massUpdate = data.massUpdate;
    }

    // If debugging is enabled, set the notification
    if (GT.isDebugging == 1){
      GT.notify('warning', M.util.get_string('debuggingrunning', 'block_gradetracker'), 'c');
    }

    // Load the grid
    config.load_grid(config.access);

    // Bind elements
    config.bindings();

  }

  config.bindings = function(){

    // Switch qualification
    $('.gt_switch_qual').unbind('change');
    $('.gt_switch_qual').bind('change', function(e){

      var qID = $(this).val();
      var url = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type='+config.grid+'&id='+config.id+'&access='+config.access+'&qualID='+qID;

      if (config.course > 0){
        url += '&courseID='+config.course;
      }

      // Only actually change url if the qual ID was set
      if (qID > 0){
        window.location = url;
      }

    });

    // Switch user
    $('.gt_switch_user').unbind('change');
    $('.gt_switch_user').bind('change', function(e){

      var sID = $(this).val();
      var url = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type=student&id='+sID+'&access='+config.access+'&qualID='+config.qualification;

      // Only actually change url if the qual ID was set
      if (sID > 0){
        window.location = url;
      }

    });


    // Switch unit
    $('.gt_switch_unit').unbind('change');
    $('.gt_switch_unit').bind('change', function(){

        var uID = $(this).val();
        if (config.qualification <= 0 || uID <= 0){
            return;
        }

        window.location = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type=unit&id=' + uID + '&access=' + config.access + '&qualID=' + config.qualification + '&courseID=' + config.course;

    });

    // Switch course
    $('.gt_switch_course').unbind('change');
    $('.gt_switch_course').bind('change', function(){

        var cID = $(this).val();
        if (config.qualification <= 0){
            return;
        }

        var id = (config.grid === 'unit') ? config.unit : config.qualification;

        window.location = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type='+config.grid+'&id=' + id + '&access=' + config.access + '&qualID=' + config.qualification + '&courseID=' + cID;

    });

    // Switch course group
    $('.gt_switch_group').unbind('change');
    $('.gt_switch_group').bind('change', function(){

        var groupID = $(this).val();
        if (config.qualification <= 0 || config.course <= 0){
            return;
        }

        var id = (config.grid === 'unit') ? config.unit : config.qualification;

        window.location = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type='+config.grid+'&id=' + id + '&access=' + config.access + '&qualID=' + config.qualification + '&courseID=' + config.course + '&groupID=' + groupID;

    });




    // Slimmenus
    $('ul.slimmenu').slimmenu(
    {
        resizeWidth: '850',
        collapserTitle: M.util.get_string('mainmenu', 'block_gradetracker'),
        easingEffect:'easeInOutQuint',
        animSpeed:'medium',
        indentChildren: true
    });


    // Refresh grades at the bottom of the student grid
    $('.gt_refresh_grid_grade').unbind('click');
    $('.gt_refresh_grid_grade').bind('click', function(e){

      var type = $(this).attr('type');
      config.refresh_grades(type);

      e.preventDefault();

    });


    // Load a grid
    $('.gt_load_grid').unbind('click');
    $('.gt_load_grid').bind('click', function(e){

      var access = $(this).attr('access');

      config.load_grid(access);

      e.preventDefault();

    });


    // Debugging Console

    // Start debugging
    $('.gt_start_debugging').unbind('click');
    $('.gt_start_debugging').bind('click', function(e){

      // Already running
      if (GT.isDebugging == 1){
          return false;
      }

      GT.ajax( M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', { action: 'set_debugging', params: {value: 1} }, function(){

          $('.gt_debug_start').addClass('gt_img_disable');
          $('.gt_debug_stop').removeClass('gt_img_disable');

          // Add notification
          GT.notify('warning', M.util.get_string('debuggingrunning', 'block_gradetracker'), 'c');

          // Set GT variable
          GT.isDebugging = 1;

      });

      e.preventDefault();

    });

    // Stop debugging
    $('.gt_stop_debugging').unbind('click');
    $('.gt_stop_debugging').bind('click', function(e){

      // Already stopped
      if (GT.isDebugging == 0){
          return false;
      }

      GT.ajax( M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', { action: 'set_debugging', params: {value: 0} }, function(){

          $('.gt_debug_start').removeClass('gt_img_disable');
          $('.gt_debug_stop').addClass('gt_img_disable');

          // Remove notification
          $('.bc-notification').fadeOut('slow', function(){
              $(this).remove();
          });

          // Set GT variable
          GT.isDebugging = 0;

      });

      e.preventDefault();

    });

    // Clear debugging
    $('.gt_clear_debugging').unbind('click');
    $('.gt_clear_debugging').bind('click', function(e){

      $('#gt_loading').show();

      GT.ajax( M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', { action: 'clear_debugging' }, function(){
          $('#gt_loading').hide();
      });

      e.preventDefault();

    });




    // Grid -------------------------------------------------------------------------------------------------------------

    // Date pickers
    $('.gt_criterion_date').datepicker( {

        dateFormat: "dd-mm-yy",
        showButtonPanel: true,

        beforeShow: function(){

            config.tmpDate = $(this).val();

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
            if (date === config.tmpDate){
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

            GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_criterion', params: params}, function(data){

                // If empty data, must have been an error
                if (data.length === 0){

                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                } else {

                    // Was ok, so let's do stuff
                    $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    config.apply_award_updates(data, sID, qID, uID);

                }

            });

        }
    } );


    // Criterion Award Date only
    $('.gt_criterion_award_date').datepicker({

        dateFormat: "dd-mm-yy",
        showButtonPanel: true,

        beforeShow: function(){

            config.tmpDate = $(this).val();

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
            if (date === config.tmpDate){
                return false;
            }

            var TD = $($(this).parents('td')[0]);
            var sID = $(TD).attr('sID');
            var qID = $(TD).attr('qID');
            var uID = $(TD).attr('uID');
            var cID = $(TD).attr('cID');

            var params = { studentID: sID, qualID: qID, unitID: uID, critID: cID, date: date };

            GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_criterion_award_date', params: params}, function(data){

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

            config.tmpDate = $(this).val();

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
            if (date === config.tmpDate){
                return false;
            }

            var TD = $($(this).parents('td')[0]);
            var sID = $(this).attr('sID');
            var qID = $(this).attr('qID');
            var uID = $(this).attr('uID');
            var rID = $(this).attr('rID');
            var obNum = $(this).attr('observationNum');

            var params = { studentID: sID, qualID: qID, unitID: uID, rangeID: rID, obNum: obNum, date: date };

            GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_range_observation_award_date', params: params}, function(data){

                // If empty data, must have been an error
                if (data.length === 0){

                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                } else {

                    // Was ok, so let's do stuff
                    $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    config.apply_award_updates(data, sID, qID, uID);

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

        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_criterion', params: params}, function(data){

            // If empty data, must have been an error
            if (data.length === 0){

                // Highlight cell red
                $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                // Set checked property back to what it was
                $(cell).prop('checked', !met);

                // Alert to notify user
                alert( M.util.get_string('couldnotupdate', 'block_gradetracker') );

            } else {

                // Was ok, so let's do stuff
                $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );
                config.apply_award_updates(data, sID, qID, uID);

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

        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_criterion', params: params}, function(data){

            // If empty data, must have been an error
            if (data.length === 0){

                // Highlight cell red
                $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                // Alert to notify user
                alert( M.util.get_string('couldnotupdate', 'block_gradetracker') );

            } else {

                // Was ok, so let's do stuff
                $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );

                config.apply_award_updates(data, sID, qID, uID);

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


                    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_sub_criterion_comments', params: params}, function(data){

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
                    config.bindings();
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

                    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_detail_criterion', params: params}, function(data){

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
                    config.bindings();
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


                    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_sub_criterion_comments', params: params}, function(data){

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
                    config.bindings();
                    GT.centre( $('.bc-modal') );
                });
            },
            allowMultiple: false
        } );

        e.preventDefault();

    });

    // Update numeric criterion point
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

        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_numeric_point', params: params}, function(data){

            var response = $.parseJSON(data);

            // If empty data, must have been an error
            if (data.length === 0){

                // Highlight cell red
                $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                // Alert to notify user
                alert( M.util.get_string('couldnotupdate', 'block_gradetracker') );

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
    $('.gt_open_ranged_criterion_window').bind('click', function(e){

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


                    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_sub_criterion_comments', params: params}, function(data){

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
                    config.bindings();
                });
            },
            allowMultiple: false
        } );

        e.preventDefault();

    });


    // Open comments popup for normal criteria
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

                    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_criterion_comments', params: params}, function(data){

                        // If empty data, must have been an error
                        if (data.length === 0){

                            // Highlight cell red
                            $(TBDIV).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                            // Alert to notify user
                            alert( M.util.get_string('couldnotupdate', 'block_gradetracker') );

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
                    config.bindings();
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

                    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_assessment_comments', params: params}, function(data){

                        // If empty data, must have been an error
                        if (data.length === 0){

                            // Highlight cell red
                            $(TBDIV).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                            $('#gt_comment_loading').hide();

                            // Alert to notify user
                            alert( M.util.get_string('couldnotupdate', 'block_gradetracker') );

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
                    config.bindings();
                });
            },
            allowMultiple: false
        } );

    });


    // Unit Award Select Menu
    $('select.gt_grid_unit_award').unbind('change');
    $('select.gt_grid_unit_award').bind('change', function(){

        var TD = $($(this).parents('td')[0]);
        var sID = $(TD).attr('sID');
        var qID = $(TD).attr('qID');
        var uID = $(TD).attr('uID');
        var value = $(this).val();

        if ( $(TD).length == 0 ) return false;


        var params = { studentID: sID, qualID: qID, unitID: uID, value: value };

        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_unit', params: params}, function(data){

            // If empty data, must have been an error
            if (data.length === 0){

                // Highlight cell red
                $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                // Alert to notify user
                alert( M.util.get_string('couldnotupdate', 'block_gradetracker') );

            } else {

                // Was ok, so let's do stuff
                config.apply_award_updates(data, sID, qID, uID);
                $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );

                // Annoying issue with freezeTable means duplicated inputs in the frozen section don't get the same value
                // So have to manually set the value on the same class here
                $('select.gt_grid_unit_award.S'+sID+'_Q'+qID+'_U'+uID).val(value);

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
                    config.bindings();
                });
            },
            allowMultiple: true,
            showOverlay: false
        } );

        e.preventDefault();

    });


    // Double Click the edit cell to show details
    $('td.gt_grid_cell_e, td.gt_grid_cell_ae').unbind('dblclick');
    $('td.gt_grid_cell_e, td.gt_grid_cell_ae').bind('dblclick', function(){

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
                    config.bindings();
                });
            },
            allowMultiple: true,
            showOverlay: false
        } );

    });

    // Criterion Info Popup
    $('td.gt_grid_cell_v, img.gt_edit_info_icon').unbind('click');
    $('td.gt_grid_cell_v, img.gt_edit_info_icon').bind('click', function(){

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
                    config.bindings();
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
                    config.bindings();
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

        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_range_info', params: params}, function(data){

            $(infoDiv).html(data);
            config.bindings();

        });

        e.preventDefault();

    });

    // Add another observation
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

        config.bindings();

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

        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_assessment', params: params}, function(data){

            // If empty data, must have been an error
            if (data.length === 0){

                // Highlight cell red
                $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                // Alert to notify user
                alert( M.util.get_string('couldnotupdate', 'block_gradetracker') );

            } else {

                // Was ok, so let's do stuff
                $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );

            }

        });

    });

    // Internal Verification (IV) - Who
    $('.gt_stud_unit_IV_who').unbind('change');
    $('.gt_stud_unit_IV_who').bind('change', function(){

        var TD = $($(this).parents('td')[0]);
        var sID = $(TD).attr('sID');
        var qID = $(TD).attr('qID');
        var uID = $(TD).attr('uID');
        var value = $(this).val();

        var params = { type: 'unit', attribute: 'IV_who', studentID: sID, qualID: qID, unitID: uID, value: value };

        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_user_attribute', params: params}, function(data){

            // If empty data, must have been an error
            if (data.length === 0){

                // Highlight cell red
                $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                // Alert to notify user
                alert( M.util.get_string('couldnotupdate', 'block_gradetracker') );

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

            GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_user_attribute', params: params}, function(data){

                // If empty data, must have been an error
                if (data.length === 0){

                    // Highlight cell red
                    $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                    // Alert to notify user
                    alert( M.util.get_string('couldnotupdate', 'block_gradetracker') );

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

        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_student_assessment_custom_field', params: params}, function(data){

            // If empty data, must have been an error
            if (data.length === 0){

                // Highlight cell red
                $(TD).effect( 'highlight', {color: '#f24c3d'}, 3000 );

                // Alert to notify user
                alert( M.util.get_string('couldnotupdate', 'block_gradetracker') );

            } else {

                // Was ok, so let's do stuff
                $(TD).effect( 'highlight', {color: '#ccff66'}, 3000 );

                // Remove the little warning if there was one
                $('#q_'+qID+'s_'+sID+'a_'+aID+'f_'+fID+'_msg').remove();

            }

        });

    };

    var tmpLastCustomField = '';

    $('select.gt_assessment_custom_field').unbind('change');
    $('input.gt_assessment_custom_field').unbind('change').unbind('click').unbind('blur');
    $('textbox.gt_assessment_custom_field').unbind('change').unbind('click').unbind('blur');

    // Select menus, tetx inputs and textboxes are on change
    $('select.gt_assessment_custom_field, input[type!="checkbox"].gt_assessment_custom_field, textarea.gt_assessment_custom_field').bind('change', customBoundFunc);

    // When you blur it, remove the little Unsaved notification, if nothing has changed since we Focussed on it
    $('select.gt_assessment_custom_field, input[type!="checkbox"].gt_assessment_custom_field, textarea.gt_assessment_custom_field').bind('blur', function(){

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

    // Checkbox is on click
    $('input[type="checkbox"].gt_assessment_custom_field').bind('click', customBoundFunc);

    // Write a little message for these ones reminding people it's not saved until they click away
    $('input[type!="checkbox"].gt_assessment_custom_field, textarea.gt_assessment_custom_field').unbind('focus');
    $('input[type!="checkbox"].gt_assessment_custom_field, textarea.gt_assessment_custom_field').bind('focus', function(){

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


    // Change grid page (unit and class)
    $('.gt_change_grid_page').unbind('click');
    $('.gt_change_grid_page').bind('click', function(e){

      var page = $(this).attr('page');

      $('#gt-page').val(page);
      $('.gt_pagenumber').removeClass('active');
      $('.gt_pagenumber_'+page).addClass('active');
      config.load_grid();

      e.preventDefault();

    });

    // Unit Grid - Mass Update - Change Criterion
    $('#gt_mass_switch_crit').unbind('change');
    $('#gt_mass_switch_crit').bind('change', function(){

        var critID = $(this).val();
        var valueDropDown = $('#gt_mass_switch_value');

        // If we selected a value
        if ( critID !== '' ){

          // Set empty option at the top
          valueDropDown.html('<option></option>');

          // Now loop through valid values
          var values = config.extra.massUpdate[critID];
          $.each(values, function(indx, val){
            valueDropDown.append('<option value="'+val[1]+'">'+val[0]+'</option>');
          });

        } else {
          valueDropDown.html('');
        }

    });

    // Unit Grid - Mass Update - Apply Update
    $('#gt_mass_update_btn').unbind('click');
    $('#gt_mass_update_btn').bind('click', function(){

        var qID = $('#gt-qID').val();
        var uID = $('#gt-uID').val();
        var cID = $('#gt-crID').val();
        var groupID = $('#gt-groupID').val();
        var criterion_switch_value = $("#gt_mass_switch_crit").val();
        var value_switch_value = $("#gt_mass_switch_value").val();

        $('#gt_mass_update_loading').show();

        var params = new Array();

        params.push( { qualID: qID, unitID: uID, courseID: cID, critID: criterion_switch_value, groupID: groupID, valueID: value_switch_value } );

        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'mass_update_student_detail_criterion', params: params}, function(data){

            $('#gt_mass_update_loading').hide();

            if (data.length === 0){
                var col = '#f24c3d';
            } else {
                var col = '#ccff66';
            }

            var element_name = "gt_criteria[" + qID + "][" + uID + "][" + criterion_switch_value + "]";
            var one_type_crit = $('[name="' + element_name + '"]');
            for (i = 0; i < one_type_crit.length; i++){
                one_type_crit[i].value = value_switch_value;
            }
            one_type_crit.parent().effect( 'highlight', {color: col}, 3000 );

        });



    });



    // Example
    $('.class').unbind('click');
    $('.class').bind('click', function(e){



      e.preventDefault();

    });

  }

  config.refresh_grades = function(type, studentID){

    var qualID = config.qualification;

    if (config.grid === 'student'){
      studentID = config.student;
    }

    var params = {};

    // Show loading gif
    $('#gt_refresh_'+type+'_loader').show();

    // Refresh avg gcse score
    if (type === 'gcse'){
      params = { action: 'get_refreshed_gcse_score', params: { studentID: studentID } };
    }

    // Refresh target grade
    else if(type === 'tg'){

      // Is there more than 1 qualification on the page? E.g. Assessment view
      if ( $('.gt-qID').length > 0 ){
          var qualIDArray = new Array();
          $('.gt-qID').each( function(){
              qualIDArray.push( $(this).val() );
          } );
          qualID = qualIDArray;
      }

      var params = { action: 'get_refreshed_target_grade', params: { studentID: studentID, qualID: qualID } };

    }

    // Refresh weighted target grade
    else if(type === 'wtg'){

      // Is there more than 1 qualification on the page? E.g. Assessment view
      if ( $('.gt-qID').length > 0 ){
          var qualIDArray = new Array();
          $('.gt-qID').each( function(){
              qualIDArray.push( $(this).val() );
          } );
          qualID = qualIDArray;
      }

      var params = { action: 'get_refreshed_weighted_target_grade', params: { studentID: studentID, qualID: qualID } };

    }

    // Refresh predicted grades
    else if(type === 'pg'){
      var params = { action: 'get_refreshed_predicted_grades', params: { studentID: studentID, qualID: qualID } };
    }


    // Get the data
    GT.ajax( M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', params, function(data){

        data = $.parseJSON(data);

        // Avg GCSE score
        if (type === 'gcse'){

          if (data['score'] !== undefined){
              $('#gt_user_gcse_score').text( data['score'] );
          }

        }

        // Target grade
        else if(type === 'tg'){

          if (data['gradeID'] instanceof Array || data['gradeID'] instanceof Object)
          {

              // Grade IDs in the select menus
              $.each( data['gradeID'], function(qID, val){
                  if (val.length > 0){
                      $('#gt_user_target_grade_'+studentID+'_'+qID+' > select.gt_target_grade_award').val( val );
                  }
              } );

              // Grade text
              $.each( data['grade'], function(qID, val){
                  if (val.length > 0){
                      $('#gt_user_target_grade_'+studentID+'_'+qID+' > span').text( val );
                  }
              } );

          }
          else
          {

              // Set the selected value of the dropdown menu
              if (data['gradeID'] !== undefined){
                  $('#gt_user_target_grade_'+studentID+'_'+qualID+' > select.gt_target_grade_award').val( data['gradeID'] );
              }

              // Set the grade text
              if (data['grade'] !== undefined){
                  $('#gt_user_target_grade_'+studentID+'_'+qualID+' > span').text( data['grade'] );
              }

          }

        }

        // Weighted target grade
        else if(type === 'wtg'){

          if (data['gradeID'] instanceof Array || data['gradeID'] instanceof Object)
          {

              // Grade text
              $.each( data['grade'], function(qID, val){
                  if (val.length > 0){
                      $('#gt_user_weighted_target_grade_'+studentID+'_'+qID+' > span').text( val );
                  }
              } );

          }
          else
          {

              // Set the grade text
              if (data['grade'] !== undefined){
                  $('#gt_user_weighted_target_grade_'+studentID+'_'+qualID+' > span').text( data['grade'] );
              }

          }

        }

        // Predicted grades
        else if(type === 'pg'){

          if (data['average'] !== undefined){

              // Student Grid
              if (config.grid === 'student'){
                $('#gt_user_avg_award').text( data['average'] );
              }

              // Class grid
              else if(config.grid === 'class' || config.grid === 'unit'){
                $('.qual_award_'+studentID+'_'+qualID).html('Predicted<br>'+data['average']);
              }

          }

          if (data['min'] !== undefined){
              $('#gt_user_min_award').text( data['min'] );
          }

          if (data['max'] !== undefined){
              $('#gt_user_max_award').text( data['max'] );
          }

          if (data['final'] !== undefined){

              // Student Grid
              if (config.grid === 'student'){
                $('#gt_user_final_award').text( data['final'] );
              }

              // Class grid
              else if(config.grid === 'class' || config.grid === 'unit'){
                $('.qual_award_'+studentID+'_'+qualID).html('Final<br>'+data['final']);
              }

          }

        }

        $('#gt_refresh_'+type+'_loader').hide();

    });

  }

  // Apply award updates to the grid elements
  config.apply_award_updates = function(result, sID, qID, uID){

      result = $.parseJSON(result);

      // Criteria awards
      if (result.awards !== undefined){

          $.each(result.awards, function(i, v) {

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
                  $(el.parents('td')[0]).effect( 'highlight', {color: '#ccff66'}, 3000 );

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
      // Bit of a shitty way to do it, but I don't want to put that stuff in a separate function just for this one call
      config.refresh_grades('pg', sID);

  }

  // Work out which grid to load
  config.load_grid = function(a){

    // Use default access if not specified
    if (a === undefined){
      a = config.access;
    }

    // Load the correct grid
    if (config.grid === 'student'){
      config.load_student_grid(a);
    } else if (config.grid === 'unit'){
      config.load_unit_grid(a);
    } else if (config.grid === 'class'){
      config.load_class_grid(a);
    }

  }


  // Bindings specific to the student grid
  config.student_bindings = function(){

    var freeze = $('.gt_grid_freeze_col').length;
    if (freeze < 1){
      freeze = 1;
    }

    // Freeze the grids
    $('#gt_grid_holder').freezeTable('destroy');
    $('#gt_grid_holder').freezeTable({
      columnNum: freeze,
      scrollable: true,
      shadow: true,
      freezeHead: true
    });

    // Call main bindings
    config.bindings();

  }

  // Bindings specific to the unit grid
  config.unit_bindings = function(){

    // Freeze the grids
    $('#gt_grid_holder').freezeTable('destroy');

    var freeze = $('.gt_grid_freeze_col').length;

    $('#gt_grid_holder').freezeTable({
      columnNum: freeze,
      scrollable: true,
      shadow: true,
      freezeHead: true
    });

    // Call main bindings
    config.bindings();

  }

  // Bindings for class grid
  config.class_bindings = function(){

    // Freeze the grids
    $('#gt_grid_holder').freezeTable('destroy');

    var freeze = $('.gt_grid_freeze_col').length;

    $('#gt_grid_holder').freezeTable({
      columnNum: freeze,
      scrollable: true,
      shadow: true,
      freezeHead: true
    });

    // Call main bindings
    config.bindings();

  }

  // Load the student grid
  config.load_student_grid = function(access){

    $('#gt_loading').show();

    var qID = config.qualification;
    var sID = config.student;
    var assessmentView = $('#gt-assessmentView').val();
    var external = ($('#gt-external').length > 0) ? 1 : 0;

    // If we clicked on edit and we are holding down the CTRL button (17), go to advancedEdit
    if (GT.isKeyPressed(17) == true && access === 'e'){
        access = 'ae';
    }

    // If we clicked on Edit, toggle the Advanced Edit button to show now
    if (access == 'e'){
        $('#gt_edit_button').hide();
        $('#gt_adv_edit_button').show();
    }

    // If we click on Advanced Edit or View, toggle the Edit button to show now
    else if (access == 'ae' || access == 'v'){
        $('#gt_adv_edit_button').hide();
        $('#gt_edit_button').show();
    }


    // Switch target grade & asp grade cells
    if (access == 'e' || access == 'ae'){
        $('.gt_tg_Q'+qID+'_S'+sID+'_view').hide();
        $('.gt_tg_Q'+qID+'_S'+sID+'_edit').show();
        $('.gt_asp_Q'+qID+'_S'+sID+'_view').hide();
        $('.gt_asp_Q'+qID+'_S'+sID+'_edit').show();
    } else {
        $('.gt_tg_Q'+qID+'_S'+sID+'_view').show();
        $('.gt_tg_Q'+qID+'_S'+sID+'_edit').hide();
        $('.gt_asp_Q'+qID+'_S'+sID+'_view').show();
        $('.gt_asp_Q'+qID+'_S'+sID+'_edit').hide();
    }

    // Check for external session, e.g. if we are viewing from Parent Portal
    var extSsn = ($('#gt-ext-sid').length === 1) ? $('#gt-ext-sid').val() : 0;
    var params = { qualID: qID, studentID: sID, access: access, assessmentView: assessmentView, external: external, extSsn: extSsn };

    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', { action: 'get_student_grid', params: params }, function(data){

        data = $.parseJSON(data);

        $('#gt_grid_holder').html( data );
        $('#gt_loading').hide();

        config.access = access;
        config.student_bindings();

    });

  }

  // Load the unit grid
  config.load_unit_grid = function(access){

    $('#gt_loading').show();

    var qID = config.qualification;
    var uID = config.unit;
    var cID = config.course;
    var groupID = config.group;

    var page = $('#gt-page').val();
    var view = $('#gt-view').val();

    $('#gt_unit_mass_update').hide();

    // If we clicked on edit and we are holding down the CTRL button (17), go to advancedEdit
    if (GT.isKeyPressed(17) == true && access === 'e'){
        access = 'ae';
    }

    // If we clicked on Edit, toggle the Advanced Edit button to show now
    if (access == 'e'){
        $('#gt_edit_button').hide();
        $('#gt_adv_edit_button').show();
    }

    // If we click on Advanced Edit or View, toggle the Edit button to show now
    else if (access == 'ae' || access == 'v'){
        $('#gt_adv_edit_button').hide();
        $('#gt_edit_button').show();
    }


    var params = { qualID: qID, unitID: uID, courseID: cID, groupID: groupID, access: access, page: page, view: view };

    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', { action: 'get_unit_grid', params: params }, function(data){

        data = $.parseJSON(data);

        $('#gt_grid_holder').html( data );
        $('#gt_loading').hide();

        // Show mass update on advanced edit
        if (access === 'ae'){
          $('#gt_unit_mass_update').show();
        }

        config.access = access;
        config.unit_bindings();

    });

  }

  // Load the class grid
  config.load_class_grid = function(access){

    $('#gt_loading').show();

    var qID = config.qualification;
    var cID = config.course;
    var groupID = config.group;

    var page = $('#gt-page').val();
    var assessmentView = $('#gt-assessmentView').val();

    // If we clicked on edit and we are holding down the CTRL button (17), go to advancedEdit
    if (GT.isKeyPressed(17) == true && access === 'e'){
        access = 'ae';
    }

    // If we clicked on Edit, toggle the Advanced Edit button to show now
    if (access == 'e'){
        $('#gt_edit_button').hide();
        $('#gt_adv_edit_button').show();
    }

    // If we click on Advanced Edit or View, toggle the Edit button to show now
    else if (access == 'ae' || access == 'v'){
        $('#gt_adv_edit_button').hide();
        $('#gt_edit_button').show();
    }


    var params = { qualID: qID, courseID: cID, groupID: groupID, access: access, page: page, assessmentView: assessmentView };

    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', { action: 'get_class_grid', params: params }, function(data){

        data = $.parseJSON(data);

        $('#gt_grid_holder').html( data );
        $('#gt_loading').hide();

        config.access = access;
        config.class_bindings();

    });

  }


  var client = {};
  client.scripts = config;

  //-- Log something to console
  client.log = function(log){
      console.log('[GT] ' + new Date().toTimeString().split(' ')[0] + ': ' + log );
  }

  //-- Initialise the scripts
  client.init = function(data) {

    // Bindings
    config.init(data);

    client.log('Loaded grids.js');

  }

  // Return client object
  return client;


});