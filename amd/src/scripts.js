define(['jquery', 'jqueryui', 'block_gradetracker/bcpopup', 'block_gradetracker/bcnotify'], function($, ui, bcPopUp, bcNotify) {




    // Gradetracker Object definition
    var GT = {};

    // Gradetracker object variables

    // Gradetracker object methods

    //-- Element bindings
    // Core element bindings
    GT.bind = function(){

        // Toggle a target
        $('.gt_toggle').unbind('click');
        $('.gt_toggle').bind('click', function(e){

          var target = $(this).attr('toggle');
          if (target !== undefined){
            $(target).toggle();
          }

          e.preventDefault();

        });

        $('.gt_remove').unbind('click');
        $('.gt_remove').bind('click', function(e){

          var target = $(this).attr('remove');
          if (target !== undefined){
            $(target).remove();
          }

          e.preventDefault();

        });


        // Stop form submission on [enter] of unit name text input
        $('#gt_filter_qual_name').unbind('keypress');
        $('#gt_filter_qual_name').bind('keypress', function(e){
            if (e.keyCode === 13){
                GT.bind_choose();
                e.preventDefault();
            }
        });


        // Small drop-down menus
        $('.gt_dropdown_toggle').unbind('click');
        $('.gt_dropdown_toggle').bind('click', function(e){
            $(this).siblings('ul.gt_dropdown_menu').toggle();
            e.preventDefault();
        });


        // Select/Deselect all options in a multi select menu
        $('input.gt_toggle_select_all').unbind('change');
        $('input.gt_toggle_select_all').bind('change', function(){

            // Get value of the checkbox and attributes to see how we want to select the options
            var val = $(this).prop('checked');
            var useID = $(this).attr('useID');
            var useClass = $(this).attr('useClass');

            // Do it by ID of select menu
            if (useID !== undefined){
                $('#'+useID+' option:enabled').prop('selected', val);
            } else if (useClass !== undefined){
                $('.'+useClass+' option:enabled').prop('selected', val);
            }

        });


        // Check/Uncheck all options of a given class
        $('input.gt_toggle_check_all').unbind('change');
        $('input.gt_toggle_check_all').bind('change', function(){

            // Get value of the checkbox and attributes to see how we want to select the options
            var val = $(this).prop('checked');
            var useClass = $(this).attr('useClass');

            if (useClass !== undefined){
                $('.'+useClass).prop('checked', val);
            }

        });

        // Check/Uncheck all options of a given class, but when the master is not a checkbox itself, just a link
        $('a.gt_toggle_check_all').unbind('click');
        $('a.gt_toggle_check_all').bind('click', function(e){

            // Get value of the checkbox and attributes to see how we want to select the options
            var val = $(this).prop('checked');
            var useClass = $(this).attr('useClass');

            // If no checked property defined, get it from the first checkbox of the class and add it to the link element
            if (val === undefined){
              val = $( $('.'+useClass)[0] ).val();
            }

            // Reverse the property on the master link
            $(this).prop('checked', !val);

            // Apply properties to class
            if (useClass !== undefined){
                $('.'+useClass).prop('checked', val);
            }

            e.preventDefault();

        });

        $('#chosen_quals').unbind('change');
        $('#chosen_quals').bind('change', function(){
            var val = $(this).val();
            var numSelected = (val !== null) ? val.length : 0;
            if (numSelected == 1){
                $('#gt_chosen_quals_edit_qual_btn').removeAttr('disabled');
                $('#gt_chosen_quals_edit_qual_btn').attr('href', 'config.php?view=quals&section=edit&id='+val);
            } else {
                $('#gt_chosen_quals_edit_qual_btn').attr('disabled', true);
                $('#gt_chosen_quals_edit_qual_btn').removeAttr('href');
            }
        });


        // Date pickers
        $('.gt_date').datepicker( {
            dateFormat: "dd-mm-yy",
            showButtonPanel: true
        } );


        // Tooltips
        $('.gt_help_tooltip').off('click');
        $('.gt_help_tooltip').on('click', function(){
            var content = $(this).attr('content');
            $('<div>'+content+'</div>').dialog({minHeight:100});
        });


        $.fn.optVisible = function( show ) {
            if( show ) {
                this.filter( "span > option" ).unwrap();
            } else {
                this.filter( ":not(span > option)" ).wrap( "<span>" ).parent().hide();
            }
            return this;
        };

        $.fn.optToggle = function() {

            if ( $(this).parent('span').length > 0 ){
                $(this).optVisible(true);
            } else {
                $(this).optVisible(false);
            }

        };

        // Element bindings for qual picker
        $('.gt_qual_picker_remove').unbind('click');
        $('.gt_qual_picker_remove').bind('click', function(e){
          GT.qual_picker.remove();
          e.preventDefault();
        });

        $('.gt_qual_picker_filter').unbind('click');
        $('.gt_qual_picker_filter').bind('click', function(e){
          GT.qual_picker.filter();
          e.preventDefault();
        });

        $('.gt_qual_picker_add').unbind('click');
        $('.gt_qual_picker_add').bind('click', function(e){
          GT.qual_picker.add();
          e.preventDefault();
        });

        // Filter on [ENTER]
        $('#gt_filter_qual_name').unbind('keypress');
        $('#gt_filter_qual_name').bind('keypress', function(e){
            if (e.keyCode === 13){
              GT.qual_picker.filter();
              e.preventDefault();
            }
        });



        // Bind change to unit select
        $('.gt_mod_hook_units').unbind('change');
        $('.gt_mod_hook_units').bind('change', function(){

            var cmID = $('#gt_cmid').val();
            var courseID = $('#gt_cid').val();
            var qualID = $(this).attr('qualID');
            var unitID = $(this).val();
            var params = { qualID: qualID, unitID: unitID, cmID: cmID, courseID: courseID };

            $('#gt_mod_hook_loader_'+qualID).show();

            GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_mod_hook_unit', params: params}, function(data){

                var response = $.parseJSON(data);

                var output = "";
                output += "<div id='gt_hooked_unit_"+qualID+"_"+unitID+"' class='gt_hooked_unit'>";

                output += ""+response.unit+" <a href='#' class='gt_mod_hook_delete_unit' qualID='"+qualID+"' unitID='"+unitID+"'><img src='"+M.util.image_url('t/delete')+"' /></a><br>";

                output += "<table class='gt_c gt_hook_unit_criteria'>";
                    output += "<tr>";
                        $.each(response.criteria, function(indx, crit){
                            output += "<th>"+crit.name+"</th>";
                        });
                    output += "</tr>";
                    output += "<tr>";
                        $.each(response.criteria, function(indx, crit){

                            output += "<td>";

                            if (response.parts){
                                output += "<select name='gt_criteria["+qualID+"]["+unitID+"]["+crit.id+"]'>";
                                    output += "<option value='0'></option>";
                                    $.each(response.parts, function(indx, part){
                                        output += "<option value='"+part.id+"'>"+part.name+"</option>";
                                    });
                                output += "</select>";
                            } else {
                                output += "<input type='checkbox' name='gt_criteria["+qualID+"]["+unitID+"]["+crit.id+"]' />";
                            }

                            output += "</td>";

                        });
                    output += "</tr>";
                output += "</table>";
                output += "</div>";

                $('#gt_mod_hook_qual_units_'+qualID).append(output);
                $('#gt_mod_hook_loader_'+qualID).hide();

                GT.bind();

            });

            // Set selected index to 0
            $(this).prop('selectedIndex', 0);

            // Disable this option so we can't select it again unless we remove the unit from the form
            $(this).children('option[value="'+unitID+'"]').prop('disabled', true);

        });


        // Bind delete unit buttons
        $('.gt_mod_hook_delete_unit').unbind('click');
        $('.gt_mod_hook_delete_unit').bind('click', function(e){

            var qualID = $(this).attr('qualID');
            var unitID = $(this).attr('unitID');
            $('#gt_hooked_unit_'+qualID+'_'+unitID).remove();
            $('#gt_mod_hook_'+qualID+'_units_select').children('option[value="'+unitID+'"]').prop('disabled', false);

            e.preventDefault();

        });

        $('.gt_mod_hook_delete_activity').unbind('click');
        $('.gt_mod_hook_delete_activity').bind('click', function(e){

            var cmID = $(this).attr('cmID');
            $('#gt_hooked_activity_'+cmID).remove();
            $('.gt_mod_activity').children('option[value="'+cmID+'"]').prop('disabled', false);

            e.preventDefault();

        });

        // Bind changing the qualification drop down, to change the units displayed
        $('.gt_mod_change_qual_units').unbind('change');
        $('.gt_mod_change_qual_units').bind('change', function(e){

            // Clear activities
            $('#gt_mod_hook_activities').html('');

            $('#gt_mod_change_qual_units_units').prop('selectedIndex', 0);

            var qualID = $(this).val();
            $('option.AQU').hide();
            $('option.Q_'+qualID).show();


        });

        // Bind change the qual unit, to get all the activities linked to it
        $('#gt_mod_change_qual_units_units').unbind('change');
        $('#gt_mod_change_qual_units_units').bind('change', function(){

            var courseID = $('#gt_cid').val();
            var qualID = $('.gt_mod_change_qual_units').val();
            var unitID = $(this).val();
            var params = { qualID: qualID, unitID: unitID, courseID: courseID };

            // Loader gif
            $('#gt_mod_hook_loader_activity').show();

            // Clear activities
            $('#gt_mod_hook_activities').html('');

            // Clear option disables
            $('.gt_mod_activity').children('option').prop('disabled', false);


            GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_mod_hook_unit_activities', params: params}, function(data){

                var response = $.parseJSON(data);

                var output = "";

                $.each(response, function(index, cm){

                    output += "<div id='gt_hooked_activity_"+cm.id+"' class='gt_hooked_unit'>";
                        output += "<img src='"+cm.icon+"' /> "+cm.name+" <a href='#' class='gt_mod_hook_delete_activity' cmID='"+cm.id+"'><img src='"+M.util.image_url('t/delete')+"' /></a><br>";
                        output += "<table class='gt_c gt_hook_unit_criteria'>";
                            output += "<tr>";
                                $.each(cm.criteria, function(indx, crit){
                                    output += "<th>"+crit.name+"</th>";
                                });
                            output += "</tr>";
                            output += "<tr>";
                                $.each(cm.criteria, function(indx, crit){

                                    output += "<td>";

                                        if (cm.parts){
                                            output += "<select name='gt_criteria["+cm.id+"]["+crit.id+"]'>";
                                                output += "<option value='0'></option>";
                                                $.each(cm.parts, function(indx, part){
                                                    var sel = ( cm.partsLinked[crit.id] !== undefined && cm.partsLinked[crit.id] == part.id ) ? 'selected' : '';
                                                    output += "<option value='"+part.id+"' "+sel+" >"+part.name+"</option>";
                                                });
                                            output += "</select>";
                                        } else {
                                            var chk = ( cm.linked.indexOf(crit.id) >= 0 ) ? 'checked' : '';
                                            output += "<input type='checkbox' name='gt_criteria["+cm.id+"]["+crit.id+"]' "+chk+" />";
                                        }

                                    output += "</td>";

                                });
                            output += "</tr>";
                        output += "</table>";
                    output += "</div>";

                    // Disable this option so we can't select it again unless we remove the unit from the form
                    $('.gt_mod_activity').children('option[value="'+cm.id+'"]').prop('disabled', true);

                });

                $('#gt_mod_hook_activities').append(output);
                $('#gt_mod_hook_loader_activity').hide();

              GT.bind();

            });

            // Set selected index to 0
            $('.gt_mod_activity').prop('selectedIndex', 0);

        });

        // Bind change to activity select
        $('.gt_mod_activity').unbind('change');
        $('.gt_mod_activity').bind('change', function(){

            var cmID = $(this).val();
            var qualID = $('.gt_mod_change_qual_units').val();
            var unitID = $('#gt_mod_change_qual_units_units').val();
            var courseID = $('#gt_cid').val();

            if (qualID == "" || unitID == "" || courseID == "") return false;

            var params = { qualID: qualID, unitID: unitID, courseID: courseID, cmID: cmID };

            $('#gt_mod_hook_loader_activity').show();

            GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_mod_hook_activity', params: params}, function(data){

                var response = $.parseJSON(data);
                var output = "";

                output += "<div id='gt_hooked_activity_"+response.id+"' class='gt_hooked_unit'>";
                output += "<img src='"+response.icon+"' /> "+response.name+" <a href='#' class='gt_mod_hook_delete_activity' cmID='"+response.id+"'><img src='"+M.util.image_url('t/delete')+"' /></a><br>";

                output += "<table class='gt_c gt_hook_unit_criteria'>";
                    output += "<tr>";
                        $.each(response.criteria, function(indx, crit){
                            output += "<th>"+crit.name+"</th>";
                        });
                    output += "</tr>";
                    output += "<tr>";
                        $.each(response.criteria, function(indx, crit){

                            output += "<td>";

                                if (response.parts){
                                    output += "<select name='gt_criteria["+cmID+"]["+crit.id+"]'>";
                                        output += "<option value='0'></option>";
                                        $.each(response.parts, function(indx, part){
                                            output += "<option value='"+part.id+"'>"+part.name+"</option>";
                                        });
                                    output += "</select>";
                                } else {
                                    output += "<input type='checkbox' name='gt_criteria["+cmID+"]["+crit.id+"]' />";
                                }

                            output += "</td>";

                        });
                    output += "</tr>";
                output += "</table>";
                output += "</div>";

                $('#gt_mod_hook_activities').append(output);
                $('#gt_mod_hook_loader_activity').hide();

                GT.bind();

            });

            // Set selected index to 0
            $(this).prop('selectedIndex', 0);

            // Disable this option so we can't select it again unless we remove the unit from the form
            $(this).children('option[value="'+cmID+'"]').prop('disabled', true);

        });




    };

    //-- Choose Bindings
    GT.bind_choose = function(){

        // When we change the qual, set the course to blank
        $('select#gt_choose_filter_all_qual').unbind('change');
        $('select#gt_choose_filter_all_qual').bind('change', function(){
            $('select#gt_choose_filter_all_course').val('');
        });

        // When we change the course, set the qual to blank
        $('select#gt_choose_filter_all_course').unbind('change');
        $('select#gt_choose_filter_all_course').bind('change', function(){
            $('select#gt_choose_filter_all_qual').val('');
        });



        // When we change the qual, set the course to blank
        $('select#gt_choose_filter_my_qual').unbind('change');
        $('select#gt_choose_filter_my_qual').bind('change', function(){
            $('select#gt_choose_filter_my_course').val('');
        });

        // When we change the course, set the qual to blank
        $('select#gt_choose_filter_my_course').unbind('change');
        $('select#gt_choose_filter_my_course').bind('change', function(){
            $('select#gt_choose_filter_my_qual').val('');
        });


    };

    //-- Read a chosen file for uploading to preview image
    GT.read_file = function(input, el)
    {

        if (input.files && input.files[0])
        {
            var reader = new FileReader();
            reader.onload = function(e){

                if (input.files[0].name.match(/\.(jpg|jpeg|png|gif)$/))
                {
                    $(el).attr('src', e.target.result);
                }
                else
                {
                    $(el).attr('src', M.cfg.wwwroot + '/blocks/gradetracker/pix/no_image.jpg');
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    };

    //-- Generate notification
    GT.notify = function(type, text, position) {

        $.bcNotify({
            type: type,
            content: text,
            position: position
        });

    };

    //-- Refresh the timestamp on a url to force refresh
    GT.refresh_url_time = function(el){
        $(el).attr('href', $(el).attr('href').replace(/t=\d+/, 't='+Date.now()));
    };

    //-- Shuffle an array
    GT.shuffle = function(o){
        for(var j, x, i = o.length; i; j = Math.floor(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
        return o;
    };

    //-- Toggle checkboxes based on a master checkbox
    GT.checkbox_toggle = function(el, cl){
        var chk = $(el).prop('checked');
        $('.'+cl).prop('checked', chk);
    };

    //-- Show an html section
    GT.show_section = function(section, hideClass, el){

        $(el).parents('ul').find('a').removeClass('selected')
        $('.'+hideClass).hide();

        $('#'+section).slideDown();
        $(el).addClass('selected');

    };

    //-- Centre an element
    GT.centre = function(el){

        var w = $(el).width();
        var posX = ($(window).width() - w) / 2;
        $(el).css('left', posX + 'px');

    };

    //-- Toggle between grades and comments tables in import overview
    GT.toggle_import_grid_tables = function(tbl)
    {

        $('.gt_import_grid_table').hide();
        $('#gt_import_grid_table_'+tbl).show();

    };

    //-- Convert tags to html elements
    GT.html = function(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    //-- Open a url
    GT.open_url = function(title, url){

        $(document).bcPopUp( {
            title: title,
            open: function(){
                $('.bc-modal-body').html('<img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/ajax-loader.gif" />');
                $('.bc-modal-body').load(url);
            },
            allowMultiple: false
        } );

    };

    //-- Make AJAX request
    GT.ajax = function(url, params, callback, callBefore){

        // Code to run before the ajax request
        if (callBefore){
            callBefore();
        }

        $.ajax({
            type: "POST",
            url: url,
            data: params,
            error: function(d){
                GT.ajax_error(d.responseText);
                client.log('Error: ' + d);
            },
            success: function(d){

                // Run specified callback after the ajax request
                if (callback){
                    callback(d);
                }

                // Run default callback
                // todo

            }
        });

    };

    GT.ajaxProgress = function( url, params, el, onSuccess ){

            var startTime = false;
            var max = $(el).attr('max');

            // Button pressed
            var btn = $('#'+params.params.btn);

            // Reset progress to 0
            $(el).val(0);
            $('#gt_progress_errors').remove();

            var req = $.ajax({
                xhr: function() {

                    var xhr = new window.XMLHttpRequest();
                    xhr.addEventListener("progress", function(evt){

                        var progress = $(el);
                        var txt = xhr.responseText;

                        if (txt.length)
                        {

                            // Check that it's a valid response and not an error
                            if (txt.charAt(0) !== '{' || txt.charAt(txt.length-1) !== '}'){

                                // Error box
                                var err = $('#gt_progress_errors');
                                if (err.length == 0){
                                    $(el).before('<div id="gt_progress_errors" class="gt_alert_bad"></div>');
                                    err = $('#gt_progress_errors');
                                }

                                err.html( txt );
                                err.show();

                                $('#gt_report_time_left').text('');
                                btn.prop('disabled', false);
                                btn.val( M.util.get_string('run', 'block_gradetracker') );

                                req.abort();

                                return false;

                            }

                            var matches = txt.match(/\{.*?\}/g);
                            var m = matches.pop();

                            if (m.length > 0){

                                var response = $.parseJSON( m );

                                // Estimated time left
                                if (startTime === false){
                                    startTime = response.time;
                                } else if (response.progress < 100){
                                    var progressLeft = max - response.progress;
                                    var timesLeft = progressLeft / response.progress;
                                    var time = response.time - startTime;
                                    var remaining = Math.round(time * timesLeft);
                                    if (remaining > 0){
                                        $('#gt_report_time_left').text(remaining + ' ' + M.util.get_string('sexleft', 'block_gradetracker'));
                                    }
                                }

                                if (response.result == 'pending'){
                                    progress.val( response.progress );
                                }

                            }

                        }

                    }, false);

                  return xhr;

                },
                url: url,
                type: "POST",
                data: params,
                dataType: "text",
                success: function(data){

                    var matches = data.match(/\{.*?\}/g);
                    if (matches != null && matches.length > 0){
                        var m = matches.pop();
                        if (m.length > 0){
                            data = $.parseJSON(m);
                        }
                    }

                    if (data.length == 0 || data.result == false){

                        // Error box
                        var err = $('#gt_progress_errors');
                        if (err.length == 0){
                            $(el).before('<div id="gt_progress_errors" class="gt_alert_bad"></div>');
                            err = $('#gt_progress_errors');
                        }

                        var error = (data.error !== undefined) ? data.error : 'error';

                        err.html( error );
                        err.show();

                        btn.prop('disabled', false);
                        btn.val( M.util.get_string('run', 'block_gradetracker') );

                        return false;

                    }

                    $(el).val(max);
                    $('#gt_report_time_left').text('');
                    $('#gt_progress_errors').hide();

                    onSuccess( data );

                },
            error: function(data){

                // Error box
                var err = $('#gt_progress_errors');
                if (err.length == 0){
                    $(el).before('<div id="gt_progress_errors" class="gt_alert_bad"></div>');
                    err = $('#gt_progress_errors');
                }

                err.html( data );
                err.show();

                $('#gt_report_time_left').text('');
                btn.prop('disabled', false);
                btn.val( M.util.get_string('run', 'block_gradetracker') );

                return false;

            }
          });

    };

    //-- AJAX error function
    GT.ajax_error = function(msg){
        client.log('['+new Date() + '] ' + msg);
        alert('['+new Date() + '] ' + msg);
    };




    /** QualPicker **/
    GT.qual_picker = {};

    //-- Filter the qualifications in the QualPicker
    GT.qual_picker.filter = function(){

        var type = $('#gt_filter_qual_structure').val();
        var lvl = $('#gt_filter_qual_level').val();
        var sub = $('#gt_filter_qual_subtype').val();
        var name = $('#gt_filter_qual_name').val();

        var params = { structureID: type, levelID: lvl, subTypeID: sub, name: name };

        $('#gt_filter_quals_loading').show();
        $('#gt_filter_quals').html('');

        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_filtered_quals', params: params}, function(data){

            var quals = $.parseJSON(data);
            $.each(quals, function(indx, qual){

                if ( $('#qual_opt_'+qual.id).length == 0 && $('#chosen_qual_opt_'+qual.id).length == 0 ){
                    var option = "<option id='qual_opt_"+qual.id+"' value='"+qual.id+"'>"+qual.name+"</option>";
                    $('#gt_filter_quals').append(option);
                }

            });

            $('#gt_filter_quals_loading').hide();

        });

    };


    //-- Added selected quals to QualPicker
    GT.qual_picker.add = function(){

        var options = $('#gt_filter_quals option:selected');
        $.each(options, function(){

            var id = $(this).val();

            // Add to qual's unit select
            $(this).prop('selected', false);
            $(this).attr('id', 'chosen_qual_opt_'+id);
            $('#chosen_quals').append( $(this) );

            // Add to hidden input
            $('#gt_chosen_quals_hidden_ids').append( "<input type='hidden' id='hidden_qual_"+id+"' name='quals[]' value='"+id+"' />" );

        });

    };

    //-- Remove select quals from QualPicker
    GT.qual_picker.remove = function(){

        var options = $('#chosen_quals option:selected');
        $.each(options, function(){

            var id = $(this).val();
            $(this).prop('selected', false);

            // Add it back to the filtered search
            $('#gt_filter_quals').append( $(this) );

            // Remove hidden input
            $('#hidden_qual_'+id).remove();

        });
    };




    /** Grades **/
    GT.grades = {};

    GT.grades.recalculate = function(type, qualID){

        $('#loading_'+qualID).show();

        if (type == 'target'){
            var action = 'get_refreshed_target_grades';
            var cellName = 'stud_target_grade_view_'+qualID+'_';
            var editCellname = 'stud_target_grade_edit_'+qualID+'_';
            var wCellName = 'stud_weighted_target_grade_view_'+qualID+'_';
        } else if (type == 'aspirational'){
            var action = 'get_refreshed_aspirational_grades';
            var cellName = 'stud_aspirational_grade_view_'+qualID+'_';
            var editCellname = 'stud_aspirational_grade_edit_'+qualID+'_';
        } else {
            $('#loading_'+qualID).hide();
            return false;
        }

        var params = {qualID: qualID};
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: action, params: params}, function(data){

            var results = $.parseJSON(data);
            $.each(results, function(studentID, result){

                var cell = '#'+cellName+studentID;
                var selectCell = '#'+editCellname+studentID;
                var weightedCell = '#'+wCellName+studentID;

                if (type == 'target'){

                    // Target Grades
                    var tResult = result.target;

                    // Calculated successfully
                    if (tResult.result == 1){
                        $(cell).text( tResult.grade );
                        if (tResult.error !== 0 && tResult.error !== '' && tResult.error !== undefined){
                            $(cell).append('<small style="color:red";><br>'+tResult.error+'</small>');
                        }
                        $(selectCell + ' select').val( tResult.gradeID );
                        $($(cell).parents('td')[0]).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    } else {
                        $(cell).html( '<span style="color:red;">'+tResult.error+'</span>' );
                        $(selectCell + ' select').val('');
                        $($(cell).parents('td')[0]).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                    }


                    // Weighted Target Grades
                    if (result.weighted !== undefined)
                    {

                        var tResult = result.weighted;

                        // Calculated successfully
                        if (tResult.result == 1){
                            $(weightedCell).text( tResult.grade );
                            if (tResult.error !== 0 && tResult.error !== '' && tResult.error !== undefined){
                                $(weightedCell).append('<small style="color:red";><br>'+tResult.error+'</small>');
                            }
                            $($(weightedCell).parents('td')[0]).effect( 'highlight', {color: '#ccff66'}, 3000 );
                        } else {
                            $(weightedCell).html( '<span style="color:red;">'+tResult.error+'</span>' );
                            $($(weightedCell).parents('td')[0]).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                        }

                    }

                } else {

                    // Calculated successfully
                    if (result.result == 1){
                        $(cell).text( result.grade );
                        if (result.error !== 0 && result.error !== '' && result.error !== undefined){
                            $(cell).append('<small style="color:red";><br>'+result.error+'</small>');
                        }
                        $(selectCell + ' select').val( result.gradeID );
                        $($(cell).parents('td')[0]).effect( 'highlight', {color: '#ccff66'}, 3000 );
                    } else {
                        $(cell).html( '<span style="color:red;">'+result.error+'</span>' );
                        $(selectCell + ' select').val('');
                        $($(cell).parents('td')[0]).effect( 'highlight', {color: '#f24c3d'}, 3000 );
                    }

                }



            });

            gtRefreshReportTable(qualID);
            $('#loading_'+qualID).hide();

        });


    };



    GT.grades.update = function(type, userID, qualID, awardID){

        $('#loading_'+qualID).show();

        var cellName = 'stud_'+type+'_grade_view_'+qualID+'_';

        var params = {sID: userID, qID: qualID, awardID: awardID, type: type};
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/update.php', {action: 'update_user_grade', params: params}, function(data){

            var result = $.parseJSON(data);
            var cell = '#'+cellName+userID;

            $(cell).text( result.grade );
            $($(cell).parents('td')[0]).effect( 'highlight', {color: '#ccff66'}, 3000 );
            $('#loading_'+qualID).hide();

        });

    };


    GT.grades.toggle = function(type, qualID){

        var viewClass = '.stud_'+type+'_grade_view_'+qualID;
        var editClass = '.stud_'+type+'_grade_edit_'+qualID;

        $(viewClass).toggle();
        $(editClass).toggle();

    };


    /**
     * Refresh the predicted grades from the dashboard
     */
    GT.grades.refresh_predicted = function(qID){

        $('#loading_'+qID).show();

        var params = { action: 'get_refreshed_predicted_grades', params: { qualID: qID } };

        $.post( M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', params, function(data){

            data = $.parseJSON(data);

            $.each(data, function(sID, row){

                if ($("#gt_qualAward_Q"+qID+"_S"+sID+" > div").length > 0){
                    var cell = "#gt_qualAward_Q"+qID+"_S"+sID+" > div";
                } else {
                    var cell = "#gt_qualAward_Q"+qID+"_S"+sID;
                }

                // If there is a final award, use that
                if (row['final'] !== undefined && row['final'] !== M.util.get_string('na', 'block_gradetracker')){
                    $(cell).text( row['final'] + ' (Final)' ).effect( 'highlight', {color: '#ccff66'}, 3000 );
                }

                // Otherwise if there is an average, use that
                else if (row['average'] !== undefined && row['average'] !== M.util.get_string('na', 'block_gradetracker')){
                    $(cell).text( row['average'] + ' (Average)' ).effect( 'highlight', {color: '#ccff66'}, 3000 );
                }

                // Otherwise just print N/A
                else {
                    $(cell).text( M.util.get_string('na', 'block_gradetracker') ).effect( 'highlight', {color: '#ccff66'}, 3000 );
                }

            });


            $('#loading_'+qID).hide();

        });

    };


    GT.toggle_disabled = function(a, b){

      $(a).removeProp('disabled');
      $(a).removeAttr('disabled');
      $(b).prop('disabled', true);
      $(b).attr('disabled', '');

      // Update enabled img
      var id = a.substr(1, a.length)
      $('#'+id+'_enabled').attr('src', M.cfg.wwwroot+'/blocks/gradetracker/pix/on.png');

      var idB = b.substr(1, b.length)
      $('#'+idB+'_enabled').attr('src', M.cfg.wwwroot+'/blocks/gradetracker/pix/off.png');

    };



    // Set Gradetracker object into global space
    window.GT = GT;


    // Client object definition
    var client = {};

    // CLient object tmethods

    //-- Log something to console
    client.log = function(log){
        console.log('[GT] ' + new Date().toTimeString().split(' ')[0] + ': ' + log );
    }

    //-- Initialise the scripts
    client.init = function() {

      // Bindings
      GT.bind();

      client.log('Loaded gt.js');

    }

    // Return client object
    return client;

});