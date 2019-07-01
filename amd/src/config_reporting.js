define(['jquery', 'jqueryui'], function($, ui) {

  var config = {};

  config.init = function(){

    // Bind elements
    config.bindings();

  }

  config.bindings = function(){

    // Toggle "Other" course input
    $('#gt_log_search_course').unbind('change');
    $('#gt_log_search_course').bind('change', function(){

        var cID = $(this).val();

        if (cID == 'OTHER'){
            $('#gt_log_search_course_name').show();
        } else {
            $('#gt_log_search_course_name').val('');
            $('#gt_log_search_course_name').hide();
        }

    });

    // Change qual drop-down
    $('#gt_log_search_qual').unbind('change');
    $('#gt_log_search_qual').bind('change', function(){

        // Reset select menus
        $('#gt_log_search_unit').html('<option value="">'+M.util.get_string('allunits', 'block_gradetracker')+'</option>');
        $('#gt_log_search_ass').html('<option value="">'+M.util.get_string('allass', 'block_gradetracker')+'</option>');
        $('#gt_log_search_crit').html('<option value="">'+M.util.get_string('allcrit', 'block_gradetracker')+'</option>');
        $('#gt_log_search_load').show();

        var qID = $(this).val();
        var params = {qualID: qID};

        // Units
        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_qual_units', params: params}, function(data){

            var units = $.parseJSON(data);
            $.each(units['order'], function(indx, uID){
                $('#gt_log_search_unit').append('<option value="'+uID+'">'+units['units'][uID]+'</option>');
            });

            $('#gt_log_search_load').hide();

        });

        // Assessments
        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_qual_assessments', params: params}, function(data){

            var assessments = $.parseJSON(data);
            $.each(assessments['order'], function(indx, aID){
                $('#gt_log_search_ass').append('<option value="'+aID+'">'+assessments['ass'][aID]+'</option>');
            });

            $('#gt_log_search_load').hide();

        });


    });

    // Change unit drop-down
    $('#gt_log_search_unit').unbind('change');
    $('#gt_log_search_unit').bind('change', function(){

        // Reset select menus
        $('#gt_log_search_crit').html('<option value="">'+M.util.get_string('allcrit', 'block_gradetracker')+'</option>');

        var uID = $(this).val();
        if (uID == ''){
            return;
        }

        $('#gt_log_search_load').show();
        var params = {unitID: uID};

        // Criteria
        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_unit_criteria', params: params}, function(data){

            var criteria = $.parseJSON(data);
            $.each(criteria['order'], function(indx, cID){
                $('#gt_log_search_crit').append('<option value="'+cID+'">'+criteria['criteria'][cID]+'</option>');
            });

            $('#gt_log_search_load').hide();

        });


    });

    // Run a pre-built report
    $('input.gt_run_report').unbind('click');
    $('input.gt_run_report').bind('click', function(e){

        // Disable input button while running
        var btn = $(this);
        btn.prop('disabled', true);
        btn.val( M.util.get_string('running', 'block_gradetracker') + '...' );

        var report = $(this).attr('report');
        var params = {};
        params.report = report;
        params.btn = btn.attr('id');
        params.params = [];

        $('.report_option').each( function(){

            var nm = $(this).attr('name');
            var vl = $(this).val();

            params.params.push( { name: nm, value: vl } );

        } );

        GT.ajaxProgress( M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'download_report', params: params }, '#gt_report_progress', function(data){

            // Reset button
            btn.prop('disabled', false);
            btn.val( M.util.get_string('run', 'block_gradetracker') );

            // Download file
            window.location = M.cfg.wwwroot + '/blocks/gradetracker/download.php?f='+data.file+'&t='+data.time;

        });

        e.preventDefault();

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

    client.log('Loaded config_reporting.js');

  }

  // Return client object
  return client;


});