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