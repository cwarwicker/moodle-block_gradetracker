define(['jquery', 'jqueryui'], function($, ui) {

  var config = {};

  config.init = function(){

    // Bind elements
    config.bindings();

  }

  config.bindings = function(){

    $('.gt_assessment_change_type').unbind('change');
    $('.gt_assessment_change_type').bind('change', function(){

      var type = $(this).val();

      if (type == 'other'){
          $('#gt_other_type').show();
      } else {
          $('#gt_other_type').hide();
      }

    });

    $('.gt_assessment_change_grading_method').unbind('change');
    $('.gt_assessment_change_grading_method').bind('change', function(){

      var val = $(this).val();

      if (val == 'numeric'){
          $('#grading_numeric_inputs').show();
          $('#gt_assessment_grading_method_structures_cell').hide();
      } else if (val == 'structure') {
          $('#grading_numeric_inputs').hide();
          $('#gt_assessment_grading_method_structures_cell').show();
      } else {
          $('#grading_numeric_inputs').hide();
          $('#gt_assessment_grading_method_structures_cell').hide();
      }

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

    client.log('Loaded config_[file].js');

  }

  // Return client object
  return client;


});