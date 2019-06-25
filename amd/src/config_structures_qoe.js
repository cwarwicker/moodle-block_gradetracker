define(['jquery', 'jqueryui'], function($, ui) {

  var config_structures_qoe = {};

  // Initialise QOE config object
  config_structures_qoe.init = function(specialVals){

    // Bind elements
    config_structures_qoe.bindings();

  }

  // Bind elements
  config_structures_qoe.bindings = function(){

    $('.gt_add_qoe_subject').unbind('click');
    $('.gt_add_qoe_subject').bind('click', function(e){

      $('#gt_subjects_cloneme').clone().attr('id', '').removeClass('gt_hidden').appendTo('#gt_qoe_subjects');

      // Rebind new elements
      config_structures_qoe.bindings();

      e.preventDefault();

    });

    $('.gt_add_qoe_type').unbind('click');
    $('.gt_add_qoe_type').bind('click', function(e){

      $('#gt_types_cloneme').clone().attr('id', '').removeClass('gt_hidden').appendTo('#gt_qoe_types');

      // Rebind new elements
      config_structures_qoe.bindings();

      e.preventDefault();

    });

    $('.gt_add_qoe_grade').unbind('click');
    $('.gt_add_qoe_grade').bind('click', function(e){

      $('#gt_grades_cloneme').clone().attr('id', '').removeClass('gt_hidden').appendTo('#gt_qoe_grades');

      // Rebind new elements
      config_structures_qoe.bindings();

      e.preventDefault();

    });

    $('.gt_remove_qoe_row').unbind('click');
    $('.gt_remove_qoe_row').bind('click', function(e){

      $(this).parents('tr').remove();

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
  client.init = function(data) {

    // Bindings
    config_structures_qoe.init(data);

    client.log('Loaded config_structures_qoe.js');

  }

  // Return client object
  return client;


});