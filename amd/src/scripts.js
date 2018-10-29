define(['jquery', 'jqueryui'], function($, ui) {
       

    var client = {};
    var GT = {};

    // Core element bindings
    GT.bind = function(){

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


        $('#chosen_quals').off('change');
        $('#chosen_quals').on('change', function(){
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


    };


    // Choose Bindings
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


    /**
     * Read a chosen file for uploading to preview the image
     * @param {type} input
     * @returns {undefined}
     */
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


    /**
     * Generate a noty notification
     */
    GT.notify = function(type, text, position) {

        $.bcNotify({
            content: text,
            type: type,
            position: position
        });

    };


    /**
     * Refresh the timestamp on a URL to force reload every time
     */
    GT.refresh_url_time = function(el){
        $(el).attr('href', $(el).attr('href').replace(/t=\d+/, 't='+Date.now()));
    };


    /**
     * Shuffle an array
     * @param {type} o
     * @returns {@var;x}
     */
    GT.shuffle = function(o){
        for(var j, x, i = o.length; i; j = Math.floor(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
        return o;
    };


    /**
     * Toggle checkboxes based on master checkbox
     * @param {type} el
     * @param {type} cl
     * @returns {undefined}
     */
    GT.checkbox_toggle = function(el, cl){

        var chk = $(el).prop('checked');
        $('.'+cl).prop('checked', chk);

    };


    /**
     * Show a section in the DOM
     * @param {type} section
     * @param {type} hideClass
     * @param {type} el
     * @returns {undefined}
     */
    GT.show_section = function(section, hideClass, el){

        $(el).parents('ul').find('a').removeClass('selected')
        $('.'+hideClass).hide();

        $('#'+section).slideDown();
        $(el).addClass('selected');

    };


    GT.qual_picker = {};

    /**
     * Filter the qualifications
     * @returns {undefined}
     */
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


    /**
     * Add selected qualifications to the quals on course select menu
     * @returns {undefined}
     */
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


    /**
     * Remove qualification from selected ones on the course
     * @returns {undefined}
     */
    GT.qual_picker.remove = function(){

        var options = $('#chosen_quals option:selected');
        $.each(options, function(){

            var id = $(this).val();
            $('#hidden_qual_'+id).remove();
            $(this).remove();

        });
    };


    /**
     * Centre an element
     * @param {type} el
     * @returns {undefined}
     */
    GT.centre = function(el){

        var w = $(el).width();
        var posX = ($(window).width() - w) / 2;
        $(el).css('left', posX + 'px');

    };


    GT.reporting = {};

    GT.reporting.refresh = function(id){
        var screenWidth = $(window).width();
        var width = screenWidth * 0.85;
        $("#qualification_report_table_"+ id).gridviewScroll({
            width: width,
            height: 600,
            freezesize: 1
        });
    };


    GT.reporting.create_dropdown = function(id){

        var params = {qualid: id};

        if( $('#gt_table_row_'+ id).length == 0)
        {
            $('#report_icon_'+ id).attr('src', M.cfg.wwwroot + '/blocks/gradetracker/pix/ajax-loader.gif')

            $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_qualification_report', params: params}).done(
                function(reportinggrid){

                    $('#gt_row_'+ id).after(reportinggrid);
                    $('#report_icon_'+ id).attr('src', M.cfg.wwwroot + '/blocks/gradetracker/pix/dropup.png');
                    gtRefreshReportTable(id);

                }
            ).fail(
                function(xhr, textStatus, error){
                    $('#report_icon_'+ id).attr('src', M.cfg.wwwroot + '/blocks/gradetracker/pix/dropdown.png');
                    $('#gt_row_'+id+' td').effect( 'highlight', {color: '#f24c3d'}, 3000 );
                }
            );

        }
        else
        {
            $('#gt_table_row_'+ id).remove()
            $('#report_icon_'+ id).attr('src', M.cfg.wwwroot + '/blocks/gradetracker/pix/dropdown.png')
        }
    };



    GT.reporting.change_tabs = function(id,param){

        if (param == 'students'){
            if (!$('#students_'+ id).hasClass('selected')){
                // change tabs
                $('#units_'+ id).attr("class", "");
                $('#students_'+ id).attr("class", "selected");
                // change tables
                $('#student_filter_'+ id).show();
                $('#student_table_view_'+ id).show();
                $('#unit_table_view_'+ id).hide();
                $('#students_view_buttons').show();
            }
        }
        else if (param == 'units'){
            if (!$('#units_'+ id).hasClass('selected')){
                // change tabs
                $('#students_'+ id).attr("class", "");
                $('#units_'+ id).attr("class", "selected");
                // change tables
                $('#student_filter_'+ id).hide();
                $('#student_table_view_'+ id).hide();
                $('#unit_table_view_'+ id).show();
                $('#students_view_buttons').hide();
            }
        }
    };



    GT.reporting.filter = function(id){

        $('.reporting_table_row_'+ id).show();

        if ($('#student_filter_'+ id +' select').val() == 'allmarked'){
            $('.reporting_table_row_'+ id).each(function(){

                var unitsawarded = $(this).attr('unitsawarded');
                var totalunits = $(this).attr('totalunits');

                if (unitsawarded != totalunits){
                    $(this).hide();
                }

            });
        }
        else if ($('#student_filter_'+ id +' select').val() == 'all'){
            $('.reporting_table_row_'+ id).each(function(){
                $(this).show();
            });
        }
        else if ($('#student_filter_'+ id +' select').val() == 'someoutstanding'){
            $('.reporting_table_row_'+ id).each(function(){

                var unitsawarded = $(this).attr('unitsawarded');
                var totalunits = $(this).attr('totalunits');

                if (unitsawarded >= totalunits && totalunits != 0){
                    $(this).hide();
                }

            });
        }
        else if ($('#student_filter_'+ id +' select').val() == 'alloutstanding'){
            $('.reporting_table_row_'+ id).each(function(){

                var unitsawarded = $(this).attr('unitsawarded');

                if (unitsawarded != 0){
                    $(this).hide();
                }

            });
        }
    };


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


    /**
     * Toggle between grades and comments tables in import overview
     * @param {type} tbl
     * @returns {undefined}
     */
    GT.toggle_import_grid_tables = function(tbl)
    {

        $('.gt_import_grid_table').hide();
        $('#gt_import_grid_table_'+tbl).show();

    };


    GT.html = function(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };



    GT.ajax = function( url, params, el, onSuccess ){

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





    client.log = function(log){
        console.log('[GT] ' + new Date().toTimeString().split(' ')[0] + ': ' + log );
    }


    client.init = function(){

        // Bindings
        GT.bind();

        client.log('Loaded');

    };

    window.GT = GT;

    return client;
    
    
});