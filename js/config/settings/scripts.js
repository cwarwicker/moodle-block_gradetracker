
function settings_bindings(){
    
    $('.gt_config_percentile_colour').on('input', function(){
        
        var col = $(this).attr('colNum');
        $('#gt_bg_col_'+col).css('background-color', $(this).val());
        
    });
    
    
    // Add new form field to Assessment settings    
    $('#gt_add_assessment_form_field').unbind('click');
    $('#gt_add_assessment_form_field').bind('click', function(e){

        // This is defined in new.html as a count of the elements currently loaded into the structure
        var customFormFields = $('.gt_custom_assessment_form_field_row').length;
        
        var row = "";
        row += "<tr class='gt_custom_assessment_form_field_row' id='gt_custom_assessment_form_field_row_"+customFormFields+"'>";
            row += "<td><input type='text' name='custom_form_fields_names["+customFormFields+"]' /></td>";
            row += "<td><select onchange='toggleFormFieldOptions(this.value, "+customFormFields+");return false;' name='custom_form_fields_types["+customFormFields+"]'><option></option><option value='TEXT'>"+M.util.get_string('element:text', 'block_gradetracker')+"</option><option value='NUMBER'>"+M.util.get_string('element:number', 'block_gradetracker')+"</option><option value='TEXTBOX'>"+M.util.get_string('element:textbox', 'block_gradetracker')+"</option><option value='SELECT'>"+M.util.get_string('element:select', 'block_gradetracker')+"</option><option value='CHECKBOX'>"+M.util.get_string('element:checkbox', 'block_gradetracker')+"</option></select></td>";
            row += "<td><input type='text' style='display:none;' id='custom_form_fields_options_"+customFormFields+"' name='custom_form_fields_options["+customFormFields+"]' placeholder='option1,option2,option3' /></td>";
            row += "<td><a href='#' onclick='$(\"#gt_custom_assessment_form_field_row_"+customFormFields+"\").remove();return false;'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/remove.png' alt='remove' /></a></td>";
        row += "</tr>";

        $('#gt_custom_assessment_form_fields').append(row);

        e.preventDefault();

    });
    
}

/**
 * Add a new navigation link to the student grid settings
 * @returns {undefined}
 */
function addNewStudentGridNavLink(pNum){
    
    cntStudNavLinks++;
    
    if (pNum > 0){
        $('#gt_stud_nav_link_'+pNum+'_sub').append('<span id="gt_stud_nav_link_'+cntStudNavLinks+'"><input type="text" name="student_grid_nav['+pNum+'][sub]['+cntStudNavLinks+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="student_grid_nav['+pNum+'][sub]['+cntStudNavLinks+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" onclick="removeStudentGridNavLink('+cntStudNavLinks+');return false;"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a><br></span>');
    } else {
        $('#gt_config_stud_grid_nav_links').append('<div id="gt_stud_nav_link_'+cntStudNavLinks+'"><br><input type="text" name="student_grid_nav['+cntStudNavLinks+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="student_grid_nav['+cntStudNavLinks+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" onclick="removeStudentGridNavLink('+cntStudNavLinks+');return false;"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a> <a href="#" onclick="addNewStudentGridNavLink('+cntStudNavLinks+');return false;"><img src="'+M.util.image_url('t/add')+'" alt="'+M.util.get_string('addnew', 'block_gradetracker')+'" /></a><br><div id="gt_stud_nav_link_'+cntStudNavLinks+'_sub"></div></div>');
    }
    
    
}

/**
 * Remove a link's inputs
 * @param {type} num
 * @returns {undefined}
 */
function removeStudentGridNavLink(num){
    
    $('#gt_stud_nav_link_'+num).remove();
    
}




/**
 * Add a new navigation link to the unit grid settings
 * @returns {undefined}
 */
function addNewUnitGridNavLink(pNum){
    
    cntUnitNavLinks++;
    
    if (pNum > 0){
        $('#gt_unit_nav_link_'+pNum+'_sub').append('<span id="gt_unit_nav_link_'+cntUnitNavLinks+'"><input type="text" name="unit_grid_nav['+pNum+'][sub]['+cntUnitNavLinks+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="unit_grid_nav['+pNum+'][sub]['+cntUnitNavLinks+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" onclick="removeUnitGridNavLink('+cntUnitNavLinks+');return false;"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a><br></span>');
    } else {
        $('#gt_config_unit_grid_nav_links').append('<div id="gt_unit_nav_link_'+cntUnitNavLinks+'"><input type="text" name="unit_grid_nav['+cntUnitNavLinks+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="unit_grid_nav['+cntUnitNavLinks+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" onclick="removeUnitGridNavLink('+cntUnitNavLinks+');return false;"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a> <a href="#" onclick="addNewUnitGridNavLink('+cntUnitNavLinks+');return false;"><img src="'+M.util.image_url('t/add')+'" alt="'+M.util.get_string('addnew', 'block_gradetracker')+'" /></a><br><div id="gt_unit_nav_link_'+cntUnitNavLinks+'_sub"></div></div>');
    }
    
    
}

/**
 * Remove a link's inputs
 * @param {type} num
 * @returns {undefined}
 */
function removeUnitGridNavLink(num){
    
    $('#gt_unit_nav_link_'+num).remove();
    
}





/**
 * Add a new navigation link to the unit grid settings
 * @returns {undefined}
 */
function addNewClassGridNavLink(pNum){
    
    cntClassNavLinks++;
    
    if (pNum > 0){
        $('#gt_class_nav_link_'+pNum+'_sub').append('<span id="gt_class_nav_link_'+cntClassNavLinks+'"><input type="text" name="class_grid_nav['+pNum+'][sub]['+cntClassNavLinks+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="class_grid_nav['+pNum+'][sub]['+cntClassNavLinks+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" onclick="removeClassGridNavLink('+cntClassNavLinks+');return false;"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a><br></span>');
    } else {
        $('#gt_config_class_grid_nav_links').append('<div id="gt_class_nav_link_'+cntClassNavLinks+'"><input type="text" name="class_grid_nav['+cntClassNavLinks+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> <input type="text" class="gt_nav_url" name="class_grid_nav['+cntClassNavLinks+'][url]" value="" placeholder="'+M.util.get_string('url', 'block_gradetracker')+'" /> <a href="#" onclick="removeClassGridNavLink('+cntClassNavLinks+');return false;"><img src="'+M.util.image_url('t/delete')+'" alt="'+M.util.get_string('delete', 'block_gradetracker')+'" /></a> <a href="#" onclick="addNewClassGridNavLink('+cntClassNavLinks+');return false;"><img src="'+M.util.image_url('t/add')+'" alt="'+M.util.get_string('addnew', 'block_gradetracker')+'" /></a><br><div id="gt_class_nav_link_'+cntClassNavLinks+'_sub"></div></div>');
    }
    
    
}

/**
 * Remove a link's inputs
 * @param {type} num
 * @returns {undefined}
 */
function removeClassGridNavLink(num){
    
    $('#gt_class_nav_link_'+num).remove();
    
}


/**
 * Show or hide the options
 * @param {type} type
 * @returns {undefined}
 */
function toggleFormFieldOptions(type, num)
{
    
    if (type == 'SELECT')
    {
        $('#custom_form_fields_options_'+num).show();
    }
    else
    {
        $('#custom_form_fields_options_'+num).hide();
    }
    
}

function addReportingCritWeightingScore(id)
{
    
    $('#gt_crit_prog_wt_'+id).append( '<tr><td><input type="text" class="gt_text_small" name="crit_weight_scores['+id+'][letter][]" /></td><td><input type="text" class="gt_text_small" name="crit_weight_scores['+id+'][score][]" /></td><td><a href="#" onclick="$($(this).parents(\'tr\')[0]).remove();return false;"><img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/remove.png" /></a></td></tr>' );
    
}