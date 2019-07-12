define(['jquery', 'jqueryui'], function($, ui) {

  var config_settings = {};

  var cntStudNavLinks = $('.gt_stud_nav_link').length;
  var cntUnitNavLinks = $('.gt_unit_nav_link').length;
  var cntClassNavLinks = $('.gt_class_nav_link').length;

  config_settings.init = function(){
    config_settings.bindings();
  }

  config_settings.bindings = function(){

    // Qualification Settings / Weighting Coefficients
    //
    // Change background of cell to match colour selected
    $('.gt_config_percentile_colour').on('input', function(){
        $('#gt_bg_col_'+$(this).attr('colNum')).css('background-color', $(this).val());
    });

    // Grid Settings
    //
    // Add new navigation link to student grid
    $('.gt_add_link_stud_grid').unbind('click');
    $('.gt_add_link_stud_grid').bind('click', function(e){

      cntStudNavLinks++;

      var num = cntStudNavLinks;
      var parent = $(this).attr('parent');

      if (parent > 0){
        $('#gt_stud_nav_link_'+parent+'_sub').append('<span id="gt_stud_nav_link_'+num+'" class="gt_stud_nav_link"><input type="text" name="student_grid_nav['+parent+'][sub]['+num+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="student_grid_nav['+parent+'][sub]['+num+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" class="gt_remove" remove="#gt_stud_nav_link_'+num+'"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a><br></span>');
      } else {
        $('#gt_config_stud_grid_nav_links').append('<div id="gt_stud_nav_link_'+num+'" class="gt_stud_nav_link"><br><input type="text" name="student_grid_nav['+num+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="student_grid_nav['+num+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" class="gt_remove" remove="#gt_stud_nav_link_'+num+'"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a> <a href="#" class="gt_add_link_stud_grid" parent="'+num+'"><img src="'+M.util.image_url('t/add')+'" alt="'+M.util.get_string('addnew', 'block_gradetracker')+'" /></a><br><div id="gt_stud_nav_link_'+num+'_sub"></div></div>');
      }

      config_settings.bindings();

      e.preventDefault();

    });

    // Add new navigation link to unit grid
    $('.gt_add_link_unit_grid').unbind('click');
    $('.gt_add_link_unit_grid').bind('click', function(e){

      cntUnitNavLinks++;

      var num = cntUnitNavLinks;
      var parent = $(this).attr('parent');

      if (parent > 0){
        $('#gt_unit_nav_link_'+parent+'_sub').append('<span id="gt_unit_nav_link_'+num+'" class="gt_unit_nav_link"><input type="text" name="unit_grid_nav['+parent+'][sub]['+num+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="unit_grid_nav['+parent+'][sub]['+num+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" class="gt_remove" remove="#gt_unit_nav_link_'+num+'"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a><br></span>');
      } else {
        $('#gt_config_unit_grid_nav_links').append('<div id="gt_unit_nav_link_'+num+'" class="gt_unit_nav_link"><br><input type="text" name="unit_grid_nav['+num+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="unit_grid_nav['+num+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" class="gt_remove" remove="#gt_unit_nav_link_'+num+'"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a> <a href="#" class="gt_add_link_unit_grid" parent="'+num+'"><img src="'+M.util.image_url('t/add')+'" alt="'+M.util.get_string('addnew', 'block_gradetracker')+'" /></a><br><div id="gt_unit_nav_link_'+num+'_sub"></div></div>');
      }

      config_settings.bindings();

      e.preventDefault();

    });

    // Add new navigation link to class grid
    $('.gt_add_link_class_grid').unbind('click');
    $('.gt_add_link_class_grid').bind('click', function(e){

      cntClassNavLinks++;

      var num = cntClassNavLinks;
      var parent = $(this).attr('parent');

      if (parent > 0){
        $('#gt_class_nav_link_'+parent+'_sub').append('<span id="gt_class_nav_link_'+num+'" class="gt_class_nav_link"><input type="text" name="class_grid_nav['+parent+'][sub]['+num+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="class_grid_nav['+parent+'][sub]['+num+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" class="gt_remove" remove="#gt_class_nav_link_'+num+'"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a><br></span>');
      } else {
        $('#gt_config_class_grid_nav_links').append('<div id="gt_class_nav_link_'+num+'" class="gt_class_nav_link"><br><input type="text" name="class_grid_nav['+num+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="class_grid_nav['+num+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" class="gt_remove" remove="#gt_class_nav_link_'+num+'"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a> <a href="#" class="gt_add_link_class_grid" parent="'+num+'"><img src="'+M.util.image_url('t/add')+'" alt="'+M.util.get_string('addnew', 'block_gradetracker')+'" /></a><br><div id="gt_class_nav_link_'+num+'_sub"></div></div>');
      }

      config_settings.bindings();

      e.preventDefault();

    });




    // Assessment Settings
    //
    // Add a new form field
    $('#gt_add_assessment_form_field').unbind('click');
    $('#gt_add_assessment_form_field').bind('click', function(e){

        // This is defined in new.html as a count of the elements currently loaded into the structure
        var customFormFields = $('.gt_custom_assessment_form_field_row').length;

        var row = "";
        row += "<tr class='gt_custom_assessment_form_field_row' id='gt_custom_assessment_form_field_row_"+customFormFields+"'>";
            row += "<td><input type='text' name='custom_form_fields_names["+customFormFields+"]' /></td>";
            row += "<td><select class='gt_add_assessment_form_field_type' num='"+customFormFields+"' name='custom_form_fields_types["+customFormFields+"]'><option></option><option value='TEXT'>"+M.util.get_string('element:text', 'block_gradetracker')+"</option><option value='NUMBER'>"+M.util.get_string('element:number', 'block_gradetracker')+"</option><option value='TEXTBOX'>"+M.util.get_string('element:textbox', 'block_gradetracker')+"</option><option value='SELECT'>"+M.util.get_string('element:select', 'block_gradetracker')+"</option><option value='CHECKBOX'>"+M.util.get_string('element:checkbox', 'block_gradetracker')+"</option></select></td>";
            row += "<td><input type='text' style='display:none;' id='custom_form_fields_options_"+customFormFields+"' name='custom_form_fields_options["+customFormFields+"]' placeholder='option1,option2,option3' /></td>";
            row += "<td><a href='#' class='gt_remove' remove='#gt_custom_assessment_form_field_row_"+customFormFields+"'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/remove.png' alt='remove' /></a></td>";
        row += "</tr>";

        $('#gt_custom_assessment_form_fields').append(row);

        config_settings.bindings();

        e.preventDefault();

    });

    // Toggle type dropdown
    $('.gt_add_assessment_form_field_type').unbind('click');
    $('.gt_add_assessment_form_field_type').bind('click', function(){

      var type = $(this).val();
      var num = $(this).attr('num');

      if (type == 'SELECT')
      {
          $('#custom_form_fields_options_'+num).show();
      }
      else
      {
          $('#custom_form_fields_options_'+num).hide();
      }

    });


    // Report settings
    //
    // Add criteria weighting score
    $('.gt_report_add_crit_weighting').unbind('click');
    $('.gt_report_add_crit_weighting').bind('click', function(e){

      var structureID = $(this).attr('structureID');
      $('#gt_crit_prog_wt_'+structureID).append( '<tr><td><input type="text" class="gt_text_small" name="crit_weight_scores['+structureID+'][letter][]" /></td><td><input type="text" class="gt_text_small" name="crit_weight_scores['+structureID+'][score][]" /></td><td><a href="#" class="gt_remove" remove="parent-row"><img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/remove.png" /></a></td></tr>' );

      config_settings.bindings();
      e.preventDefault();

    });


    // General bindings
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
    config_settings.init();

    client.log('Loaded config_settings.js');

  }

  // Return client object
  return client;


});