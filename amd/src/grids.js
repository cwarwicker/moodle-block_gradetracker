define(['jquery', 'jqueryui', 'block_gradetracker/bcpopup', 'block_gradetracker/freezetable', 'block_gradetracker/slimmenu'], function($, ui, bcPopUp, freezeTable, slimmenu) {

  var config = {};
  config.qualification = 0;
  config.unit = 0;
  config.student = 0;
  config.id = 0;
  config.course;
  config.grid = '';
  config.access = '';

  config.init = function(data){

    // Set grid variables so we know what we're looking at
    config.grid = data.type;
    config.qualification = data.qualID;
    config.id = data.id;
    if (config.grid === 'student'){
      config.student = data.id;
    } else if(config.grid === 'unit'){
      config.unit = data.id;
    }

    config.access = $('#gt-access').val();
    config.course = data.courseID;

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

      e.preventDefault();

    });

    // Switch user
    $('.gt_switch_user').unbind('change');
    $('.gt_switch_user').bind('change', function(e){

      var sID = $(this).val();
      var url = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type='+config.grid+'&id='+sID+'&access='+config.access+'&qualID='+config.qualification;

      // Only actually change url if the qual ID was set
      if (sID > 0){
        window.location = url;
      }

      e.preventDefault();

    });

    // Switch unit
    // Switch course


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
      var studentID = config.student;
      var qualID = config.qualification;
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
      $.post( M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', params, function(data){

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
                $('#gt_user_avg_award').text( data['average'] );
            }

            if (data['min'] !== undefined){
                $('#gt_user_min_award').text( data['min'] );
            }

            if (data['max'] !== undefined){
                $('#gt_user_max_award').text( data['max'] );
            }

            if (data['final'] !== undefined){
                $('#gt_user_final_award').text( data['final'] );
            }

          }

          $('#gt_refresh_'+type+'_loader').hide();

      });

      e.preventDefault();

    });


    // Load a grid
    $('.gt_load_grid').unbind('click');
    $('.gt_load_grid').bind('click', function(e){

      var grid = $(this).attr('grid');
      var access = $(this).attr('access');

      // Student grid
      if (grid === 'student'){
        config.loadStudentGrid(access);
      } else if (grid === 'unit'){
        config.loadUnitGrid(access);
      } else if (grid === 'class'){
        config.loadClassGrid(access);
      }

      e.preventDefault();

    });




    // Example
    $('.class').unbind('click');
    $('.class').bind('click', function(e){



      e.preventDefault();

    });

  }

  // Bindings specific to the student grid
  config.student_bindings = function(){

    // Call main bindings first
    config.bindings();

    // Freeze the grids
    $('#gt_grid_holder').freezeTable('destroy');
    $('#gt_grid_holder').freezeTable({
      columnNum: 1,
      scrollable: true,
      shadow: true,
      freezeHead: true
    });

  }


  // Load the student grid
  config.loadStudentGrid = function(access){

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


  var client = {};

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