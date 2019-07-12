define(['jquery', 'jqueryui'], function($, ui) {

  var config_structures_grade = {};
  config_structures_grade.specialVals = [];
  config_structures_grade.cntGrades = 0;

  config_structures_grade.init = function(specialVals){

    config_structures_grade.specialVals = specialVals;

    // Count awards
    config_structures_grade.cntGrades = $('#gt_grading_structure_table tr').length - 1;

    // Bind elements
    config_structures_grade.bindings();

  }

  config_structures_grade.bindings = function(){

    // Add a new award row to the unit grading structure
    $('#gt_add_unit_grading').unbind('click');
    $('#gt_add_unit_grading').bind('click', function(e){

        config_structures_grade.cntGrades++;
        var cntGrades = config_structures_grade.cntGrades;

        var row = '';
        row += '<tr id="gt_grading_row_'+cntGrades+'">';
            row += '<td><input type="hidden" name="grade_ids['+cntGrades+']" value="-1" /><input type="text" name="grade_names['+cntGrades+']" placeholder="e.g. Pass" value="" /></td>';
            row += '<td><input type="text" class="gt_text_small" name="grade_shortnames['+cntGrades+']" placeholder="P" value="" /></td>';
            row += '<td><input type="number" min="0" step="any" name="grade_points['+cntGrades+']" placeholder="1" value="" /></td>';
            row += '<td><input type="number" min="0" step="any" name="grade_points_lower['+cntGrades+']" placeholder="1.0" value="" /></td>';
            row += '<td><input type="number" min="0" step="any" name="grade_points_upper['+cntGrades+']" placeholder="1.5" value="" /></td>';
            row += '<td><a href="#" class="gt_remove" remove="#gt_grading_row_'+cntGrades+'"><img src="'+M.util.image_url('t/delete')+'" alt="delete" /></a></td>';
        row += '</tr>';

        $('#gt_grading_structure_table').append(row);

        config_structures_grade.bindings();
        e.preventDefault();

    });

    // Add a new award row to the crit grading structure
    $('#gt_add_crit_grading').unbind('click');
    $('#gt_add_crit_grading').bind('click', function(e){

        config_structures_grade.cntGrades++;
        var cntGrades = config_structures_grade.cntGrades;

        var row = '';
        row += '<tr id="gt_grading_row_'+cntGrades+'">';
            row += '<td>';
            row += '<input type="hidden" name="grade_ids['+cntGrades+']" value="-1" />';
            row += '<div class="gt-upload-img"><img id="gt_award_img_preview_'+cntGrades+'" src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/no_image.jpg" class="gt_image_preview"><input type="file" rowNum="'+cntGrades+'" name="grade_files['+cntGrades+']" class="gt_award_icon_input"></div>';
            row += '</td>';
            row += '<td><input type="text" name="grade_names['+cntGrades+']" placeholder="e.g. Pass" value="" /></td>';
            row += '<td><input type="text" class="gt_text_small" name="grade_shortnames['+cntGrades+']" placeholder="P" value="" /></td>';
            row += '<td><input type="checkbox" name="grade_met['+cntGrades+']" value="1" /></td>';
            row += '<td>';
                row += '<select name="grade_specialvals['+cntGrades+']">';
                    row += '<option value=""></option>';
                    $.each(config_structures_grade.specialVals, function(i, v){
                        row += '<option value="'+v+'">'+v+'</option>';
                    });
                row += '</select>';
            row += '</td>';
            row += '<td><input type="number" min="0" step="any" name="grade_points['+cntGrades+']" placeholder="1" value="" /></td>';
            row += '<td><input type="number" min="0" step="any" name="grade_points_lower['+cntGrades+']" placeholder="1.0" value="" /></td>';
            row += '<td><input type="number" min="0" step="any" name="grade_points_upper['+cntGrades+']" placeholder="1.5" value="" /></td>';
            row += '<td><a href="#" class="gt_remove" remove="#gt_grading_row_'+cntGrades+'"><img src="'+M.util.image_url('t/delete')+'" alt="delete" /></a></td>';
        row += '</tr>';

        $('#gt_grading_structure_table').append(row);

        config_structures_grade.bindings();
        e.preventDefault();

    });

    // Bind upload preview
    $('.gt_award_icon_input').unbind('change');
    $('.gt_award_icon_input').change( function(){
        var rowNum = $(this).attr('rowNum');
        GT.read_file(this, '#gt_award_img_preview_'+rowNum);
    } );

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
    config_structures_grade.init(data);

    client.log('Loaded config_structures_grade.js');

  }

  // Return client object
  return client;


});