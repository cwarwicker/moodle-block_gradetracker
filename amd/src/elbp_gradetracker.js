define(['jquery', 'jqueryui', 'block_elbp/scripts', 'block_gradetracker/scripts', 'block_gradetracker/grids'], function($, ui, elbp, gt, grids) {

  var config = {};
  var elbp = elbp.scripts;
  var gt = gt.scripts;
  var grids = grids.scripts;

  config.init = function(){

    // Bind elements
    config.bindings();

  }

  config.bindings = function(){

    $('.gt_elbp_load_popup').unbind('click');
    $('.gt_elbp_load_popup').bind('click', function(e){

      var qualID = $(this).attr('qualID');

      elbp.load_expanded('elbp_gradetracker', function(){
          var el = $('#qual'+qualID+'_tab');
          config.loadGradeTracker(qualID, el);
      });

      e.preventDefault();

    });


    $('.gt_elbp_load_grid').unbind('click');
    $('.gt_elbp_load_grid').bind('click', function(e){

      var qualID = $(this).attr('qualID');
      config.loadGradeTracker(qualID, this);

      e.preventDefault();

    });


  }

  config.loadGradeTracker = function(id, el){

    // Load a display type
    var params = { type: 'tracker', studentID: elbp.studentID, courseID: elbp.courseID, id: id }
    elbp.ajax("elbp_gradetracker", "load_display_type", params, function(d){

        $('#elbp_gradetracker_content').html(d);
        elbp.set_view_link(el);

        gt.bind(); // Standard GT bindings
        grids.bindings(); // Grid-related bindings
        // grids.student_bindings(); // Disabled for now, doens't work properly in popup

        // Set a smaller max-height for grids, since they are in a popup
        $('#gt_grid_holder').css('max-height', '400px');
        $('#gt_grid_holder').css('overflow-y', 'scroll');

    }, function(d){
        $('#elbp_gradetracker_content').html('<img src="'+M.cfg.wwwroot+'/blocks/elbp/pix/loader.gif" alt="" />');
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

    client.log('Loaded elbp_gradetracker.js');

  }

  // Push scripts onto ELBP
  elbp.push_script('elbp_gradetracker', config);

  // Return client object
  return client;


});