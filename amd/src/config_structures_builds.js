define(['jquery', 'jqueryui'], function($, ui) {

  var config_structures_builds = {};
  config_structures_builds.cntAwards = 0;

  config_structures_builds.init = function(){

    // Count awards
    config_structures_builds.cntAwards = $('#gt_build_award_table tr').length - 1;

    // Bind elements
    config_structures_builds.bindings();

  }

  config_structures_builds.bindings = function(){

    // Add new award row to build
    $('#gt_add_build_award').unbind('click');
    $('#gt_add_build_award').bind('click', function(e){

        config_structures_builds.cntAwards++;

        var cntAwards = config_structures_builds.cntAwards;
        var row = '';
        row += '<tr id="gt_build_award_row_'+cntAwards+'">';

            row += '<td><input type="hidden" name="build_award_id['+cntAwards+']" value="0" /><input type="number" step="any" name="build_award_rank['+cntAwards+']" value="" /></td>';
            row += '<td><input type="text" name="build_award_name['+cntAwards+']" value="" /></td>';
            row += '<td><input type="number" step="any" min="0" name="build_award_points_lower['+cntAwards+']" value="" /></td>';
            row += '<td><input type="number" step="any" min="0" name="build_award_points_upper['+cntAwards+']" value="" /></td>';
            row += '<td><input type="number" step="any" min="0" name="build_award_qoe_lower['+cntAwards+']" value="" /></td>';
            row += '<td><input type="number" step="any" min="0" name="build_award_qoe_upper['+cntAwards+']" value="" /></td>';
            row += '<td><input type="number" step="any" min="0" name="build_award_ucas['+cntAwards+']" value="" /></td>';
            row += '<td><a href="#" class="gt_remove" remove="#gt_build_award_row_'+cntAwards+'"><img src="'+M.util.image_url('t/delete')+'" alt="delete" /></a></td>';

        row += '</tr>';

        $('#gt_build_award_table').append(row);
        
        config_structures_builds.bindings();
        e.preventDefault();

    });

    // Bind general elements from GT object
    GT.bind();

  }




  var client = {};

  //-- Log something to console
  client.log = function(log){
      console.log('[GT] ' + new Date().toTimeString().split(' ')[0] + ': ' + log );
  }

  //-- Initialise the scripts
  client.init = function() {

    // Bindings
    config_structures_builds.init();

    client.log('Loaded config_structures_builds.js');

  }

  // Return client object
  return client;


});