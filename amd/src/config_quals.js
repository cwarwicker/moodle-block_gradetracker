define(['jquery', 'jqueryui'], function($, ui) {

  var config = {};

  config.init = function(){
    config.bindings();
  }

  config.bindings = function(){

    // Stop form submission on [enter] of unit name text input
    $('#gt_filter_units_name').unbind('keypress');
    $('#gt_filter_units_name').bind('keypress', function(e){
        if (e.keyCode === 13){
            config.filterUnits();
            e.preventDefault();
        }
    });

    // Stop form submission on [enter] of course name text input
    $('#gt_filter_courses_name').unbind('keypress');
    $('#gt_filter_courses_name').bind('keypress', function(e){
        if (e.keyCode === 13){
            config.filterCourses();
            e.preventDefault();
        }
    });

    //
    $('#qual_units').off('change');
    $('#qual_units').on('change', function(){
        var val = $(this).val();
        var numSelected = (val !== null) ? val.length : 0;
        if (numSelected == 1){
            $('#gt_qual_units_edit_unit_btn').removeAttr('disabled');
            $('#gt_qual_units_edit_unit_btn').attr('href', 'config.php?view=units&section=edit&id='+val);
        } else {
            $('#gt_qual_units_edit_unit_btn').attr('disabled', true);
            $('#gt_qual_units_edit_unit_btn').removeAttr('href');
        }
    });

    //
    $('#qual_courses').off('change');
    $('#qual_courses').on('change', function(){
        var val = $(this).val();
        var numSelected = (val !== null) ? val.length : 0;
        if (numSelected == 1){
            $('#gt_qual_units_edit_course_btn').removeAttr('disabled');
            $('#gt_qual_units_edit_course_btn').attr('href', 'config.php?view=course&section=quals&id='+val);
        } else {
            $('#gt_qual_units_edit_course_btn').attr('disabled', true);
            $('#gt_qual_units_edit_course_btn').removeAttr('href');
        }
    });


    // Filter units
    $('.gt_filter_units').unbind('click');
    $('.gt_filter_units').bind('click', function(e){

      config.filterUnits();
      e.preventDefault();

    });


    // Add units to qualification units select
    $('#gt_add_units_to_qual_select').unbind('click');
    $('#gt_add_units_to_qual_select').bind('click', function(e){

      var options = $('#gt_filter_units option:selected');
      $.each(options, function(){

          var id = $(this).val();

          // Add to qual's unit select
          $(this).prop('selected', false);
          $(this).attr('id', 'qual_unit_opt_'+id);
          $('#qual_units').append( $(this) );


          // Add to hidden input
          $('#gt_qual_form').append( "<input type='hidden' id='hidden_qual_unit_"+id+"' name='qual_units[]' value='"+id+"' />" );

      });

      e.preventDefault();

    });


    // Remove a unit from the qual units select
    $('#gt_remove_unit_from_qual_select').unbind('click');
    $('#gt_remove_unit_from_qual_select').bind('click', function(e){

      var options = $('#qual_units option:selected');
      $.each(options, function(){

          var id = $(this).val();
          $(this).prop('selected', false);
          $('#gt_filter_units').append( $(this) );

          $('#hidden_qual_unit_'+id).remove();

      });

      e.preventDefault();

    });


    // Add courses to qual courses select
    $('#gt_add_courses_to_qual_select').unbind('click');
    $('#gt_add_courses_to_qual_select').bind('click', function(e){

      var options = $('#gt_filter_courses option:selected');
      $.each(options, function(){

          var id = $(this).val();
          if (id > 0){

              // Add to qual's unit select
              $(this).prop('selected', false);
              $(this).attr('id', 'qual_course_opt_'+id);
              $('#qual_courses').append( $(this) );


              // Add to hidden input
              $('#gt_qual_form').append( "<input type='hidden' id='hidden_qual_course_"+id+"' name='qual_courses[]' value='"+id+"' />" );

          }

      });

      e.preventDefault();

    });


    // Remove courses from qual courses select
    $('#gt_remove_courses_from_qual_select').unbind('click');
    $('#gt_remove_courses_from_qual_select').bind('click', function(e){

      var options = $('#qual_courses option:selected');
      $.each(options, function(){

          var id = $(this).val();
          $(this).prop('selected', false);
          $('#gt_filter_courses').append( $(this) );

          $('#hidden_qual_course_'+id).remove();

      });

      e.preventDefault();

    });


    // Filter courses
    $('.gt_filter_courses').unbind('click');
    $('.gt_filter_courses').bind('click', function(e){

      config.filterCourses();
      e.preventDefault();

    });





    // Load build default values
    $('.gt_load_build_defaults').unbind('change');
    $('.gt_load_build_defaults').bind('change', function(e){

      // Get build defaults
      if ($('#qual_id').length == 0){

          $('#gt_build_loading').show();

          // Reset values on them all, except checkbox as that confuses the issue
          $('.gt_qual_element[type!="checkbox"]').val('');

          // Set checkbox property
          $('.gt_qual_element[type="checkbox"]').prop('checked', false);

          var buildID = $(this).val();
          var params = { buildID: buildID };

          GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_build_defaults', params: params}, function(data){

              defaults = $.parseJSON(data);

              $.each(defaults, function(i, v){
                  $('#gt_el_'+i).val(v);
                  if ( $('#gt_el_'+i).prop('type') == 'checkbox' && v == 1 ){
                      $('#gt_el_'+i).prop('checked', true);
                  }
              });

              $('#gt_build_loading').hide();

          });

      }

      e.preventDefault();

    });

    // General bindings
    GT.bind();

  }

  // Filter the units based on type, name, level, etc...
  config.filterUnits = function(){

    var type = $('#gt_filter_units_structure').val();
    var lvl = $('#gt_filter_units_level').val();
    var name = $('#gt_filter_units_name').val();

    var params = { structureID: type, levelID: lvl, name: name };

    $('#gt_filter_units_loading').show();
    $('#gt_filter_units').html('');

    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_filtered_units', params: params}, function(data){

        var units = $.parseJSON(data);
        $.each(units, function(indx, unit){

            if ( $('#unit_opt_'+unit.id).length == 0 && $('#qual_unit_opt_'+unit.id).length == 0 ){
                var option = "<option id='unit_opt_"+unit.id+"' value='"+unit.id+"' title='"+unit.title+"'>"+unit.name+"</option>";
                $('#gt_filter_units').append(option);
            }

        });

        $('#gt_filter_units_loading').hide();

    });

  }

  // Filter the courses based on name, category, etc...
  config.filterCourses = function(){

    var name = $.trim($('#gt_filter_courses_name').val());
    var catID = $('#gt_filter_courses_category').val();
    var params = { catID: catID, name: name };

    if (name === "" && catID === "") return;

    $('#gt_filter_courses_loading').show();
    $('#gt_filter_courses').html('');

    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_filtered_courses', params: params}, function(data){

        var courses = $.parseJSON(data);
        $.each(courses, function(indx, course){

            if ( $('#course_opt_'+course.id).length == 0 && $('#qual_course_opt_'+course.id).length == 0 ){
                var option = "<option id='course_opt_"+course.id+"' value='"+course.id+"'>"+course.name+"</option>";
                $('#gt_filter_courses').append(option);
            }

        });

        // If we have lots
        if (courses.length >= searchLimit){
            $('#gt_filter_courses').append("<option>"+M.util.get_string('toomanytoshow', 'block_gradetracker')+"</option>");
        }

        $('#gt_filter_courses_loading').hide();

    });

  }



  var client = {};

  //-- Log something to console
  client.log = function(log){
      console.log('[GT] ' + new Date().toTimeString().split(' ')[0] + ': ' + log );
  }

  //-- Initialise the scripts
  client.init = function() {

    // Bindings
    config.init();

    client.log('Loaded config_quals.js');

  }

  // Return client object
  return client;


});