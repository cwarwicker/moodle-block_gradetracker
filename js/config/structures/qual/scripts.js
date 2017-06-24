var ruleOperator = '.';
var ruleEvents = new Array();
var ruleComparisons = new Array();
var customFormFields = 0;


/**
 * Bind relevant elements for the qual structure form page
 * @returns {undefined}
 */
function structures_qual_bindings()
{
 
    $(document).ready( function(){
    
        // Get rule events
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_rule_events'}, function(data){
            ruleEvents = $.parseJSON(data);
        });
                
        // Get rule comparisons
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_rule_comparisons'}, function(data){
            ruleComparisons = $.parseJSON(data);
        });
        
        
    
        // Level & feature click
        $('.gt_level_box, .gt_feature_box').unbind('click');
        $('.gt_level_box, .gt_feature_box').bind('click', function(){
            
            var box = $(this).children('input[type="checkbox"]');
            box.prop('checked', !box.prop('checked'));
            if (box.prop('checked') === true)
            {
                $(this).addClass('ticked');
                $(this).children('img.gt_ticked').css('display', 'inline-block');
            }
            else
            {
                $(this).removeClass('ticked');
                $(this).children('img.gt_ticked').css('display', 'none');
            }
            
        });
        
        // Stop clicks into the input calling the parent click
        $('.gt_level_box input').click( function(e){
            e.stopPropagation();
        } );
        
        
        
        // Icon upload preview
        $('#gt_structure_icon_upload').change( function(){
            gtReadFileURL(this, '.gt_image_preview');
        } );
        
        
        
        // Add new form field        
        $('#gt_add_structure_form_field').unbind('click');
        $('#gt_add_structure_form_field').bind('click', function(e){
            
            // This is defined in new.html as a count of the elements currently loaded into the structure
            customFormFields++;
            var row = "";
            row += "<tr id='gt_custom_form_field_row_"+customFormFields+"'>";
                row += "<td><input type='text' name='custom_form_fields_names["+customFormFields+"]' /></td>";
                row += "<td><select name='custom_form_fields_forms["+customFormFields+"]'><option></option><option value='qualification'>"+M.util.get_string('qualification', 'block_gradetracker')+"</option><option value='unit'>"+M.util.get_string('unit', 'block_gradetracker')+"</option></select></td>";
                row += "<td><select onchange='toggleFormFieldOptions(this.value, "+customFormFields+");return false;' name='custom_form_fields_types["+customFormFields+"]'><option></option><option value='TEXT'>"+M.util.get_string('element:text', 'block_gradetracker')+"</option><option value='NUMBER'>"+M.util.get_string('element:number', 'block_gradetracker')+"</option><option value='TEXTBOX'>"+M.util.get_string('element:textbox', 'block_gradetracker')+"</option><option value='SELECT'>"+M.util.get_string('element:select', 'block_gradetracker')+"</option><option value='CHECKBOX'>"+M.util.get_string('element:checkbox', 'block_gradetracker')+"</option></select></td>";
                row += "<td><input type='text' style='display:none;' id='custom_form_fields_options_"+customFormFields+"' name='custom_form_fields_options["+customFormFields+"]' placeholder='option1,option2,option3' /></td>";
                row += "<td><input type='checkbox' name='custom_form_fields_req["+customFormFields+"]' value='1' /></td>";
                row += "<td><a href='#' onclick='$(\"#gt_custom_form_field_row_"+customFormFields+"\").remove();return false;'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/remove.png' alt='remove' /></a></td>";
            row += "</tr>";
            
            $('#gt_custom_form_fields').append(row);
            
            e.preventDefault();
            
        });
        
        
        
        // Add new rule
        $('#gt_add_structure_rule').unbind('click');
        $('#gt_add_structure_rule').bind('click', function(e){
            
            // This is defined in new.html as a count of the rules currently loaded into the structure
            numRules++;
            
            var row = "";
            
            row += "<tr id='gt_structure_rule_row_"+numRules+"' class='gt_rule_row gt_rule_row_"+numRules+"'>";
                row += "<td><input type='text' name='rule_names["+numRules+"]' /></td>";
                row += "<td><select name='rule_events["+numRules+"]'>";
                    $.each(ruleEvents, function(i, v){
                        row += "<option value='"+v+"'>"+v+"</option>";
                    });
                row += "</select></td>";
                row += "<td><select name='rule_contexts["+numRules+"]'>";
                    $.each(ruleContexts, function(i, v){
                        row += "<option value='"+v+"'>"+v+"</option>";
                    });
                row += "</select></td>";
                row += "<td><a href='#' onclick='$(\".gt_rule_row_"+numRules+"\").remove();return false;'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/remove.png' alt='remove' /></a></td>";
            row += "</tr>";


            row += "<tr class='gt_rule_row_"+numRules+"'><td colspan='4'>";
                row += "<table id='gt_structure_rule_table_"+numRules+"'>";
                    row += "<tr id='gt_structure_rule_step_header_row_"+numRules+"' class='gt_structure_rule_step_header_row gt_rule_row_"+numRules+"'>";
                        row += "<th class='gt_structure_rule_small_td'><b>"+M.util.get_string('step', 'block_gradetracker')+"</b></td>";
                        row += "<th><b>"+M.util.get_string('conditions', 'block_gradetracker')+"</b></td>";
                        row += "<th><b>"+M.util.get_string('action', 'block_gradetracker')+"</b></td>";
                        row += "<th class='gt_structure_rule_small_td'><a href='#' onclick='addRuleStep("+numRules+");return false;'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/add.png' alt='add step' /></a></td>";
                    row += "</tr>";
                row += "</table>";
            row += "</td></tr>";
            
            
            $('#gt_structure_rules').append(row);
            e.preventDefault();
            
        });
        
        
        // Update condition values when change things
        $('#gt_condition_dialog select').unbind('change');
        $('#gt_condition_dialog select').bind('change', function(){
            
            var ruleNum = $('#conditionHiddenRuleNum').val();
            var stepNum = $('#conditionHiddenStepNum').val();
            var condNum = $('#conditionHiddenConditionNum').val();
            var span = $('#conditionHiddenSpan').val();
            
            updateConditionValues(ruleNum, stepNum, condNum, span);
            
        });
        
        // Update action values when change things
        $('#gt_action_dialog select').unbind('change');
        $('#gt_action_dialog select').bind('change', function(){
            
            var ruleNum = $('#actionHiddenRuleNum').val();
            var stepNum = $('#actionHiddenStepNum').val();
            var condNum = $('#actionHiddenConditionNum').val();
            
            updateActionValues(ruleNum, stepNum, condNum);
            
        });
        
        // Condition text input - might want to just type
        $('#gt_condition_dialog_value').unbind('keyup');
        $('#gt_condition_dialog_value').bind('keyup', function(){
            
            $('#gt_condition_dialog_save').prop('disabled', false);
            
        });
        
        // Ok button
        $('#gt_condition_dialog_save').unbind('click');
        $('#gt_condition_dialog_save').bind('click', function(){
            
            var ruleNum = $('#conditionHiddenRuleNum').val();
            var stepNum = $('#conditionHiddenStepNum').val();
            var condNum = $('#conditionHiddenConditionNum').val();
            var span = $('#conditionHiddenSpan').val();
            var val = $('#gt_condition_dialog_value').val();
            
            $('#gt_rule_'+ruleNum+'_step_'+stepNum+'_condition_'+condNum+'_'+span+'_input').val(val);
            $('#gt_rule_'+ruleNum+'_step_'+stepNum+'_'+span+'_'+condNum).text(val);
            
            $('#gt_condition_dialog').dialog('close');
            
        });
        
        // action Ok button
        $('#gt_action_dialog_save').unbind('click');
        $('#gt_action_dialog_save').bind('click', function(){
            
            var ruleNum = $('#actionHiddenRuleNum').val();
            var stepNum = $('#actionHiddenStepNum').val();
            var condNum = $('#actionHiddenActionNum').val();
            var val = $('#gt_action_dialog_value').val();
            
            $('#gt_rule_'+ruleNum+'_step_'+stepNum+'_action_'+condNum+'_input').val(val);
            $('#gt_rule_'+ruleNum+'_step_'+stepNum+'_'+condNum).text(val);
            
            $('#gt_action_dialog').dialog('close');
            
        });
                
        $('.gt_tooltip').tooltip();
        
    });
    
}



// Define array, if it's not been defined in the php yet for existing ones on a saved item
if (typeof numRuleSteps === "undefined"){
    var numRuleSteps = new Array();
}

/**
 * Add a row for a new step onto a rule
 * @param {type} ruleNum
 * @returns {undefined}
 */
function addRuleStep(ruleNum)
{

    if (numRuleSteps[ruleNum] === undefined){
        numRuleSteps[ruleNum] = 0;
    }

    numRuleSteps[ruleNum]++;
    var step = numRuleSteps[ruleNum];

    var row = "";
    row += "<tr id='gt_structure_rule_step_row_"+ruleNum+"_"+step+"' class='gt_rule_step_row gt_rule_row_"+ruleNum+"'>";
        
        // Step Numbers
        row += "<td><select class='gt_rule_step_select' name='rule_steps["+ruleNum+"]["+step+"]'>";
            for (var i = 1; i <= step; i++)
            {
                var sel = (i === step) ? 'selected' : '';
                row += "<option value='"+i+"' "+sel+">"+i+"</option>";
            }
        row += "</select></td>";
        
        // Conditions
        row += "<td id='gt_structure_rule_"+ruleNum+"_step_"+step+"_conditions'>";
            row += "<a href='#' onclick='addNewRuleStepConditionSpans("+ruleNum+", "+step+");return false;'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/add.png' alt='add' /></a>";
            row += getRuleStepConditionSpans(ruleNum, step);
        row += "</td>";
        
        // Actions
        row += "<td id='gt_structure_rule_"+ruleNum+"_step_"+step+"_actions'>";
            row += "<a href='#' onclick='addNewRuleStepActionSpans("+ruleNum+", "+step+");return false;'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/add.png' alt='add' /></a>";
            row += getRuleStepActionSpans(ruleNum, step);
        row += "</td>";
        
        
        row += "<td><a href='#' onclick='removeRuleStep("+ruleNum+", "+step+");return false;'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/remove.png' alt='remove' /></a></td>";
        
        
    row += "</tr>";

    $('#gt_structure_rule_table_'+ruleNum).append(row);
    
    updateStepSelectMenus(ruleNum);
    applyConditionBindings();

}

/**
 * Apply element bindings for new conditions row
 * @returns {undefined}
 */
function applyConditionBindings()
{
    
    // Do the onBlur for the comparison dropdown. Cannot do it on select menu, weirdest Moodle thing i've
    // seen so far, totally fucks up the whole javascript caching to have anything on that one select menu    
    $('.gt_rule_step_select_cmp').each( function(){
        
        var span = $(this).parent('span');
        var spanID = $(span).attr('id');
        var split = spanID.split("_");
        var ruleNum = split[2];
        var stepNum = split[4];
        var cndNum = split[6];
        
        $(this).unbind('blur');
        $(this).bind('blur', function(){
            
            $('#gt_rule_'+ruleNum+'_step_'+stepNum+'_cmp_'+cndNum).text( $(this).val() );
            toggleStepComparisonEditing(ruleNum, stepNum, cndNum);
            
        });
        
        
    } );
    
}

/**
 * Apply element bindings for new action row
 * @returns {undefined}
 */
function applyActionBindings()
{
}

/**
 * Add new row of conditions to a rule step
 * @param {type} ruleNum
 * @param {type} step
 * @returns {undefined}
 */
function addNewRuleStepConditionSpans(ruleNum, step)
{
    var row = getRuleStepConditionSpans(ruleNum, step);
    $('#gt_structure_rule_'+ruleNum+'_step_'+step+'_conditions').append(row);
    applyConditionBindings();
}

/**
 * Add new row of actions to a rule step
 * @param {type} ruleNum
 * @param {type} step
 * @returns {undefined}
 */
function addNewRuleStepActionSpans(ruleNum, step)
{
    var row = getRuleStepActionSpans(ruleNum, step);
    $('#gt_structure_rule_'+ruleNum+'_step_'+step+'_actions').append(row);
    applyActionBindings();
}

/**
 * Add the spans for actions in a rule step
 * @param {type} ruleNum
 * @param {type} step
 * @returns {undefined}
 */
function getRuleStepActionSpans(ruleNum, step)
{
    
    // Count existing actions in this step
    var lastAction = $('.gt_rule_'+ruleNum+'_step_'+step+'_action').last();
    
    if ($(lastAction).length > 0)
    {
        var cnt = parseInt($(lastAction).attr('actionNum')) + 1;
    }
    else
    {
        var cnt = 1;
    }
    
    // Build spans
    var row = "<span id='gt_rule_"+ruleNum+"_step_"+step+"_action_"+cnt+"'><br>";
        row += "<span id='gt_rule_"+ruleNum+"_step_"+step+"_"+cnt+"' actionNum='"+cnt+"' class='gt_rule_condition_action gt_rule_"+ruleNum+"_step_"+step+"_action' onclick='toggleRuleStepActionPopUp("+ruleNum+", "+step+", "+cnt+");return false;'>action</span> ";
        row += " <a href='#' onclick='removeRuleStepAction("+ruleNum+", "+step+", "+cnt+");return false;'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/remove.png' alt='delete' style='width:12px;' /></a>";
        row += "<input type='hidden' name='rule_actions["+ruleNum+"]["+step+"]["+cnt+"]' id='gt_rule_"+ruleNum+"_step_"+step+"_action_"+cnt+"_input' value='' />";
    row += "<br></span>";
    
    return row;
    
}

/**
 * Add the spans for conditions in a rule step
 * @param {type} ruleNum
 * @param {type} step
 * @returns {String}
 */
function getRuleStepConditionSpans(ruleNum, step)
{
    // Count existing conditions in this step
    var lastCondition = $('.gt_rule_'+ruleNum+'_step_'+step+'_condition').last();
    if ($(lastCondition).length > 0)
    {
        var cnt = parseInt($(lastCondition).attr('conditionNum')) + 1;
    }
    else
    {
        var cnt = 1;
    }
        
    var row = "<span id='gt_rule_"+ruleNum+"_step_"+step+"_condition_"+cnt+"'><br>";
    
    row += "<span id='gt_rule_"+ruleNum+"_step_"+step+"_v1_"+cnt+"' conditionNum='"+cnt+"' class='gt_rule_condition_value gt_rule_"+ruleNum+"_step_"+step+"_condition' onclick='toggleRuleStepPopUp("+ruleNum+", "+step+", "+cnt+", \"v1\");return false;'>value</span> ";
    row += "<span id='gt_rule_"+ruleNum+"_step_"+step+"_cmp_"+cnt+"' class='gt_rule_condition_comparison' onclick='toggleStepComparisonEditing("+ruleNum+", "+step+", "+cnt+");return false;'>comparison</span> <span id='gt_rule_"+ruleNum+"_step_"+step+"_cmp_"+cnt+"_editing' class='gt_rule_step_editing'><select name='rule_conditions["+ruleNum+"]["+step+"]["+cnt+"][cmp]' class='gt_rule_step_select_cmp'><option value=''></option></select></span>";
    row += "<span id='gt_rule_"+ruleNum+"_step_"+step+"_v2_"+cnt+"' class='gt_rule_condition_value_compare' onclick='toggleRuleStepPopUp("+ruleNum+", "+step+", "+cnt+", \"v2\");return false;'>value</span>";
    row += " <a href='#' onclick='removeRuleStepCondition("+ruleNum+", "+step+", "+cnt+");return false;'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/remove.png' alt='delete' style='width:12px;' /></a>";
    
    row += "<input type='hidden' name='rule_conditions["+ruleNum+"]["+step+"]["+cnt+"][v1]' id='gt_rule_"+ruleNum+"_step_"+step+"_condition_"+cnt+"_v1_input' value='' />";
    row += "<input type='hidden' name='rule_conditions["+ruleNum+"]["+step+"]["+cnt+"][v2]' id='gt_rule_"+ruleNum+"_step_"+step+"_condition_"+cnt+"_v2_input' value='' />";
    
    row += "<br></span>";
    
    return row;
}

/**
 * Remove a step from a rule
 * @param {type} ruleNum
 * @param {type} step
 * @returns {undefined}
 */
function removeRuleStep(ruleNum, step)
{
    $('#gt_structure_rule_step_row_'+ruleNum+'_'+step).remove();
    updateStepSelectMenus(ruleNum);
}

/**
 * Toggle the popup to edit the values of the actions of the steps of the rules
 * @param {type} ruleNum
 * @param {type} step
 * @param {type} action
 * @returns {undefined}
 */
function toggleRuleStepActionPopUp(ruleNum, step, action)
{
    
    // Fill the inputs in the popup based on the hidden value for this condition
    var val = $('#gt_rule_'+ruleNum+'_step_'+step+'_action_'+action+'_input').val();
    
    var arr = val.split(ruleOperator);
    var condObj = arr.shift();
    
    var condMethod = arr.pop();
    if (condMethod !== undefined){
        condMethod = condMethod.split("(")[0]; 
    }
    
    var condFilter = arr.join(ruleOperator);
    if (condFilter !== undefined){
        condFilter = condFilter.split("(")[0]; 
    }
 
    // The dialog box
    $('#actionHiddenRuleNum').val(ruleNum);
    $('#actionHiddenStepNum').val(step);
    $('#actionHiddenActionNum').val(action);
    $('#actionHiddenSpan').val('');
    
    // Object input
    $('#gt_action_dialog_obj').val(condObj);
    
    // Clear method options
    $('#gt_action_dialog_method').html('<option value=""></option>')
    if (typeof ruleConditionMethods[condObj] !== "undefined")
    {
        $.each(ruleConditionMethods[condObj], function(k, i){
            var sel = (i === condMethod) ? 'selected' : '';
            $('#gt_action_dialog_method').append('<option value="'+i+'" '+sel+'>'+i+'</option>')
        });
    }
    
    // Filter input
    $('#gt_action_dialog_filter').val(condFilter);
    
    // Value input
    $('#gt_action_dialog_value').val(val);
    
    // If object not set, clear prop and filter and disable
    if (condObj === "")
    {
        $('#gt_action_dialog_method').val('').prop('disabled', true);
        $('#gt_action_dialog_filter').val('').prop('disabled', true);
    }
    else
    {
        $('#gt_action_dialog_method').prop('disabled', false);
        $('#gt_action_dialog_filter').prop('disabled', false);
    }
    
    
    // If object or property not set, disable OK button
    if (condObj === "" || condMethod === "")
    {
        $('#gt_action_dialog_save').prop('disabled', true);
    }
    else
    {
        $('#gt_action_dialog_save').prop('disabled', false);
    }
    
        
    // Display the dialog box
    $('#gt_action_dialog').dialog();
    
}

/**
 * Toggle the popup to edit the values of the conditions of the steps of the rules
 * @param {type} ruleNum
 * @param {type} step
 * @param {type} condition
 * @param {type} span
 * @returns {undefined}
 */
function toggleRuleStepPopUp(ruleNum, step, condition, span)
{
 
    // Fill the inputs in the popup based on the hidden value for this condition
    var val = $('#gt_rule_'+ruleNum+'_step_'+step+'_condition_'+condition+'_'+span+'_input').val();
    var arr = val.split(ruleOperator);
    var condObj = arr.shift();
    var condProp = arr.pop();
    var condFilter = arr.join(ruleOperator);
    condFilter = condFilter.split("(")[0]; 
 
    // The dialog box
    $('#conditionHiddenRuleNum').val(ruleNum);
    $('#conditionHiddenStepNum').val(step);
    $('#conditionHiddenConditionNum').val(condition);
    $('#conditionHiddenSpan').val(span);
    
    // Object input
    $('#gt_condition_dialog_obj').val(condObj);
    
    // Clear property options
    $('#gt_condition_dialog_prop').html('<option value=""></option>')
    if (typeof ruleConditionProperties[condObj] !== "undefined")
    {
        $.each(ruleConditionProperties[condObj], function(k, i){
            var sel = (i === condProp) ? 'selected' : '';
            $('#gt_condition_dialog_prop').append('<option value="'+i+'" '+sel+'>'+i+'</option>')
        });
    }
    
    // Filter input
    $('#gt_condition_dialog_filter').val(condFilter);
    
    // Value input
    $('#gt_condition_dialog_value').val(val);
    
    // If object not set, clear prop and filter and disable
    if (condObj === "")
    {
        $('#gt_condition_dialog_prop').val('').prop('disabled', true);
        $('#gt_condition_dialog_filter').val('').prop('disabled', true);
    }
    else
    {
        $('#gt_condition_dialog_prop').prop('disabled', false);
        $('#gt_condition_dialog_filter').prop('disabled', false);
    }
    
    
    // If object or property not set, disable OK button
    if (condObj === "" || condProp === "")
    {
        $('#gt_condition_dialog_save').prop('disabled', true);
    }
    else
    {
        $('#gt_condition_dialog_save').prop('disabled', false);
    }
    
    
    // Show property row and hide method row
    $('#gt_dialog_prop_row').show();
    $('#gt_dialog_method_row').hide();
    
    // Display the dialog box
    $('#gt_condition_dialog').dialog();
    
        
}

/**
 * based on the drop-downs, calculate the value to put in the action input
 * @param {type} ruleNum
 * @param {type} step
 * @param {type} action
 * @returns {undefined}
 */
function updateActionValues(ruleNum, step, action)
{
    
    // Variables
    var str = "";
    var o = $('#gt_action_dialog_obj').val();
    var m = $('#gt_action_dialog_method').val();
    var f = $('#gt_action_dialog_filter').val();
    
    
    // Clear method options
    $('#gt_action_dialog_method').html('<option value=""></option>')
    
    if (typeof ruleConditionMethods[o] !== "undefined")
    {
        $.each(ruleConditionMethods[o], function(k, i){
            var sel = (i === m) ? 'selected' : '';
            $('#gt_action_dialog_method').append('<option value="'+i+'" '+sel+'>'+i+'</option>')
        });
    }
    
    
    // If object not set, clear prop and filter and disable
    if (o === "")
    {
        $('#gt_action_dialog_method').val('').prop('disabled', true);
        $('#gt_action_dialog_filter').val('').prop('disabled', true);
        m = '';
        f = '';
    }
    else
    {
        $('#gt_action_dialog_method').prop('disabled', false);
        $('#gt_action_dialog_filter').prop('disabled', false);
    }
    
    
    if ( o === "" || m === "" )
    {
        $('#gt_action_dialog_save').prop('disabled', true);
    }
    else
    {
        $('#gt_action_dialog_save').prop('disabled', false);
    }
    
    
    
    // Create string input
    
    // Object
    str += o;
    
    // Filter
    if (f !== "")
    {
        str += ruleOperator + f + "()";
    }
    
    // Method
    if (m !== "")
    {
        str += ruleOperator + m + "()";
    }
    
    $('#gt_action_dialog_value').val(str);
    
    
}

/**
 * Based on the drop-down menus, calculate the value to put in the input
 * @param {type} ruleNum
 * @param {type} step
 * @param {type} condition
 * @param {type} span
 * @returns {undefined}
 */
function updateConditionValues(ruleNum, step, condition, span)
{
    
    // Object
    var str = "";
    var o = $('#gt_condition_dialog_obj').val();
    var p = $('#gt_condition_dialog_prop').val();
    var f = $('#gt_condition_dialog_filter').val();
    
    
    // Clear property options
    $('#gt_condition_dialog_prop').html('<option value=""></option>')
    
    if (typeof ruleConditionProperties[o] !== "undefined")
    {
        $.each(ruleConditionProperties[o], function(k, i){
            var sel = (i === p) ? 'selected' : '';
            $('#gt_condition_dialog_prop').append('<option value="'+i+'" '+sel+'>'+i+'</option>')
        });
    }
    
        
    
    // If object not set, clear prop and filter and disable
    if (o === "")
    {
        $('#gt_condition_dialog_prop').val('').prop('disabled', true);
        $('#gt_condition_dialog_method').val('').prop('disabled', true);
        $('#gt_condition_dialog_filter').val('').prop('disabled', true);
        p = '';
        m = '';
        f = '';
    }
    else
    {
        $('#gt_condition_dialog_prop').prop('disabled', false);
        $('#gt_condition_dialog_method').prop('disabled', false);
        $('#gt_condition_dialog_filter').prop('disabled', false);
    }
    
    
    // If object or property not set, disable OK button
    if ( o === "" || p === "" )
    {
        $('#gt_condition_dialog_save').prop('disabled', true);
    }
    else
    {
        $('#gt_condition_dialog_save').prop('disabled', false);
    }
    
    
    // Create string input
    
    // Object
    str += o;
    
    // Filter
    if (f !== "")
    {
        str += ruleOperator + f + "()";
    }
    
    // Property
    if (p !== "")
    {
        str += ruleOperator + p;
    }
    
    $('#gt_condition_dialog_value').val(str);
    
    
}


/**
 * Remove a condition from a step in a rule
 * @param {type} ruleNum
 * @param {type} step
 * @param {type} condition
 * @returns {undefined}
 */
function removeRuleStepCondition(ruleNum, step, condition)
{
    $("#gt_rule_"+ruleNum+"_step_"+step+"_condition_"+condition).remove();
}

function removeRuleStepAction(ruleNum, step, action)
{
    $("#gt_rule_"+ruleNum+"_step_"+step+"_action_"+action).remove();
}

/**
 * Toggle the editing on the values/comparisons of a rule's step
 * @param {type} ruleNum
 * @param {type} step
 * @param {type} span
 * @returns {undefined}
 */
function toggleStepEditing(ruleNum, step, condition, span)
{
        
    // Toggle this one
    $('#gt_rule_'+ruleNum+'_step_'+step+'_'+span+'_'+condition+'_editing').toggle();
    
    // Hide all other ones
    $('.gt_rule_step_editing:not(#gt_rule_'+ruleNum+'_step_'+step+'_'+span+'_'+condition+'_editing)').hide();
    
}

/**
 * Toggle the editing for the comparison parts of rule steps
 * @param {type} ruleNum
 * @param {type} step
 * @param {type} condition
 * @returns {undefined}
 */
function toggleStepComparisonEditing(ruleNum, step, condition)
{
    
    updateComparisonSelectMenus(ruleNum, step, condition);
    $('#gt_rule_'+ruleNum+'_step_'+step+'_cmp_'+condition).toggle();
    $('#gt_rule_'+ruleNum+'_step_'+step+'_cmp_'+condition+'_editing').toggle();
    if ( $('#gt_rule_'+ruleNum+'_step_'+step+'_cmp_'+condition+'_editing').css('display') !== 'none'){
        $('#gt_rule_'+ruleNum+'_step_'+step+'_cmp_'+condition+'_editing select').focus();
    }
    
}

/**
 * Update the select menus each time we add a new step
 * @param {type} ruleid
 * @returns {undefined}
 */
function updateStepSelectMenus(ruleid)
{
    
    var cnt = $('.gt_rule_step_row.gt_rule_row_'+ruleid).length;
    $('.gt_rule_step_row.gt_rule_row_'+ruleid+' select.gt_rule_step_select').each( function(){
        
        var selected = $(this).val();
        $(this).html('');
        
        for (var i = 1; i <= cnt; i++)
        {
            $(this).append('<option value="'+i+'">'+i+'</option>');
        }
        
        // Set selected value back
        $(this).val(selected);
        
    } );
    
}

/**
 * Update the select menu for comparisons
 * @param {type} rule
 * @param {type} step
 * @param {type} condition
 * @returns {undefined}
 */
function updateComparisonSelectMenus(rule, step, condition)
{
    
    var el = $('#gt_rule_'+rule+'_step_'+step+'_cmp_'+condition+'_editing select.gt_rule_step_select_cmp');
    var val = $(el).val();
    
    $(el).html('');
    $.each(ruleComparisons, function(i, v){
        $(el).append('<option value="'+i+'">'+i+'</option>');
    } );
    
    $(el).val(val);
    
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












function gt_addNewRuleSet(){

    numRuleSets++;
    numRulesArray[numRuleSets] = 0;
    numRuleSteps[numRuleSets] = [];

    var row = "";

    row += "<tr id='gt_rule_set_row_"+numRuleSets+"'>";

        row += "<td></td>";
        row += "<td><input type='text' id='rule_set_name_"+numRuleSets+"' name='rule_sets["+numRuleSets+"][name]' value='' /></td>";
        row += "<td><a href='#' onclick='gt_openRules("+numRuleSets+");return false;'>"+M.util.get_string('openrules', 'block_gradetracker')+" <img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/open.png' alt='open' /></a></td>";
        row += "<td><input type='radio' name='rule_set_default' value='"+numRuleSets+"' /></td>";
        row += "<td><input type='checkbox' name='rule_sets["+numRuleSets+"][enabled]' value='1' checked /></td>";
        row += "<td><a href='#' onclick='gt_removeRuleSet("+numRuleSets+");return false;'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/remove.png' alt='delete' /></a></td>";

    row += "</tr>";

    $('#gt_structure_rule_sets').append(row);

}

function gt_removeRuleSet(num){
    $('#gt_rule_set_row_'+num).remove();
    if ( $('#gt_popup_rules_'+num).length > 0 ){
        $('#gt_popup_rules_'+num).bcPopUp('destroy');
    }
}

function gt_openRules(num){

    // Create div inside the form
    if ( $('#gt_popup_rules_' + num).length == 0 ){
        
        // Load template
        var params = {ruleSetNum: num};
        
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_rule_set_template', params: params}, function(data){
            
            $('form#gt_qual_structure_form').append(data);
            
            var content = $('#gt_popup_rules_'+num).html();
            var ruleSetName = $('#rule_set_name_'+num).val();
            ruleSetName = gt_html(ruleSetName);
            _gt_openRulesPopUp(num, ruleSetName, content);
            
        });

    } else {
 
        var content = $('#gt_popup_rules_'+num).html();
        var ruleSetName = $('#rule_set_name_'+num).val();
        ruleSetName = gt_html(ruleSetName);
        _gt_openRulesPopUp(num, ruleSetName, content);
    
    }

}


function _gt_openRulesPopUp(num, ruleSetName, content){
    
    $('#gt_popup_rules_' + num).bcPopUp( {
        title: M.util.get_string('rules', 'block_gradetracker') + ' - ' + ruleSetName,
        content: content,
        allowMultiple: true,
        overrideWidth: '90%',
        appendTo: 'form#gt_qual_structure_form'
    } );
    
}

function gt_addNewRule(ruleSetNum){

    numRulesArray[ruleSetNum]++;
    var ruleNum = numRulesArray[ruleSetNum];
    numRuleSteps[ruleSetNum][ruleNum] = 0;

    var params = {ruleSetNum: ruleSetNum, ruleNum: ruleNum};

    // Load content from AJAX call and insert into HTML
    $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_rule_form', params: params}, function(data){

        // Add row to table
        var row = '<tr id="gt_rule_row_'+ruleSetNum+'_'+ruleNum+'"><td><a href="#" id="gt_rule_name_link_'+ruleSetNum+'_'+ruleNum+'" onclick="gt_editRule('+ruleSetNum+', '+ruleNum+');return false;">'+M.util.get_string('newrule', 'block_gradetracker')+'</a></td><td><span id="gt_rule_event_span_'+ruleSetNum+'_'+ruleNum+'"></span></td><td><span id="gt_rule_steps_span_'+ruleSetNum+'_'+ruleNum+'">0</span></td><td><div class="gt_fancy_checkbox"><input id="chkbox_'+ruleSetNum+'_'+ruleNum+'" type="checkbox" name="rule_sets['+ruleSetNum+'][rules]['+ruleNum+'][enabled]" value="1" checked /><label for="chkbox_'+ruleSetNum+'_'+ruleNum+'"></label></div></td><td><a href="#" onclick="gt_removeRule('+ruleSetNum+', '+ruleNum+');return false;"><img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/remove.png" alt="delete" /></a></td></tr>';
        $('#gt_popup_rules_table_'+ruleSetNum + ' table').append(row);

        // Add div
        $('#gt_popup_rules_divs_'+ruleSetNum).append(data);

        // Load it
        gt_editRule(ruleSetNum, ruleNum);

    });

}

function gt_editRule(ruleSetNum, ruleNum){

    $('.gt_rule_content').hide();
    $('#gt_rule_content_'+ruleSetNum+'_'+ruleNum).fadeIn('slow');

    // Bindings
    $('input.gt_rule_name').off('keyup');
    $('input.gt_rule_name').on('keyup', function(){
        var val = $(this).val().trim();
        if (val == ''){
            val = '-';
        }
        $('a#gt_rule_name_link_'+ruleSetNum+'_'+ruleNum).text( val );
    });

    $('input.gt_rule_event').off('change');
    $('input.gt_rule_event').on('change', function(){
        $('span#gt_rule_event_span_'+ruleSetNum+'_'+ruleNum).text( $(this).val() );
    });

}

function gt_removeRule(ruleSetNum, ruleNum){

    $('#gt_rule_row_'+ruleSetNum+'_'+ruleNum).remove();
    $('#gt_rule_content_'+ruleSetNum+'_'+ruleNum).remove();

}

function gt_addRuleStep(ruleSetNum, ruleNum){

    // Only add steps if the execution event has been chosen
    if ($('.gt_rule_on_event_'+ruleSetNum+'_'+ruleNum).is(':checked') === false){
        var dialog = $('<div>'+M.util.get_string('errors:rule:oneventmissing', 'block_gradetracker')+'</div>').dialog({
            autoOpen: false,
            width: 400,
            dialogClass: 'gt_jq_dialog',
            buttons: [
                {
                    text: "Ok",
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                }
            ],
            show: {
                effect: "slideDown",
                duration: 300
              },
              hide: {
                effect: "slideUp",
                duration: 300
              }
        });

        dialog.data('uiDialog')._title = function(title){
            title.html(this.options.title);
        };

        dialog.dialog('option', 'title', '<span class="ui-icon ui-icon-alert"></span>');
        dialog.dialog('open');

        return false;
    }

    numRuleSteps[ruleSetNum][ruleNum]++;
    var stepNum = numRuleSteps[ruleSetNum][ruleNum];
    var cnt = $(".gt_rule_step_"+ruleSetNum+"_"+ruleNum+":not(#gt_rule_step_template)").length;
    var dynamicStepNum = cnt + 1;

    var div = $('#gt_rule_step_template').clone();

    $(div).html( function(i, html){
        return html.replace(/\[RS\]/g, ruleSetNum).replace(/\[R\]/g, ruleNum).replace(/\[S\]/g, stepNum);
    });

    $(div).attr('id', 'gt_rule_step_'+ruleSetNum+'_'+ruleNum+'_'+stepNum);
    $(div).addClass('gt_rule_step_'+ruleSetNum+'_'+ruleNum);
    $(div).attr('stepNum', dynamicStepNum);
    $($(div).find('.gt_step_num')[0]).text(dynamicStepNum);
    $(div).removeClass('gt_hidden');

    $('#gt_rule_steps_'+ruleSetNum+'_'+ruleNum).append(div);

    gt_updateStepNumbers(ruleSetNum, ruleNum);

}

function gt_removeRuleStep(ruleSetNum, ruleNum, el){

    var stepNum = $(el).parents('div.gt_rule_step_'+ruleSetNum+'_'+ruleNum).attr('stepNum');
    $('#gt_rule_step_'+ruleSetNum+'_'+ruleNum+'_'+stepNum).remove();
    gt_updateStepNumbers(ruleSetNum, ruleNum);

}

function gt_updateStepNumbers(ruleSetNum, ruleNum){

    var num = 1;
    var steps = $(".gt_rule_step_"+ruleSetNum+"_"+ruleNum+":not(#gt_rule_step_template)");
    $(steps).each( function(){

        $(this).attr('id', 'gt_rule_step_'+ruleSetNum+'_'+ruleNum+'_'+num);
        $(this).attr('stepNum', num);
        $($(this).find('.gt_step_num')[0]).text(num);
        $(this).find(':input').each( function(){
            var newname = $(this).attr('name').replace(/\[steps\]\[(\d+)\]/, '[steps]['+num+']');
            $(this).attr('name', newname);
        } );

        num++;

    } );

    $('#gt_rule_steps_span_'+ruleSetNum+'_'+ruleNum).text(num - 1);

}

function gt_addRuleStepCondition(ruleSetNum, ruleNum, el){

    var stepNum = $(el).parents('div.gt_rule_step_'+ruleSetNum+'_'+ruleNum).attr('stepNum');
    var div = $('#gt_rule_step_condition_template').clone();

    $(div).html( function(i, html){
        return html.replace(/\[RS\]/g, ruleSetNum).replace(/\[R\]/g, ruleNum).replace(/\[S\]/g, stepNum);
    });

    $(div).attr('id', '');
    $(div).addClass('gt_rule_step_condition_'+ruleSetNum+'_'+ruleNum+'_'+stepNum);
    $(div).removeClass('gt_hidden');

    $($(el).parents('div.gt_rule_step_content').find('div.gt_step_conditions')[0]).append(div);

}

function gt_removeRuleStepCondition(el){
    $(el).parent().remove();
}   

function gt_addRuleStepAction(ruleSetNum, ruleNum, el){

    var stepNum = $(el).parents('div.gt_rule_step_'+ruleSetNum+'_'+ruleNum).attr('stepNum');
    var div = $('#gt_rule_step_action_template').clone();

    $(div).html( function(i, html){
        return html.replace(/\[RS\]/g, ruleSetNum).replace(/\[R\]/g, ruleNum).replace(/\[S\]/g, stepNum);
    });

    $(div).attr('id', '');
    $(div).addClass('gt_rule_step_action_'+ruleSetNum+'_'+ruleNum+'_'+stepNum);
    $(div).removeClass('gt_hidden');

    $($(el).parents('div.gt_rule_step_content').find('div.gt_step_actions')[0]).append(div);

}

function gt_removeRuleStepAction(el){
   $(el).parent().remove();
}

function gt_openFx(el, type){

    $(document).bcPopUp( {
        title: M.util.get_string('function', 'block_gradetracker'),
        urlType: 'ajax',
        url: M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php?action=get_rule_fx_panel',
        allowMultiple: true,
        overrideWidth: '75%',
        buttons: {
            'Confirm': function(){

                var result = '';

                var txt = $('#gt_fx_text_input');
                var fnc = $('#gt_fx_func');

                // Text input
                if (txt.length > 0 && txt.prop('disabled') !== true){
                    var txtVal = txt.val().trim();
                    result = '"'+txtVal+'"';
                }

                // Function input
                else if (fnc.prop('disabled') !== true){
                    var txtVal = fnc.val().trim();
                    result = txtVal;
                }

                // Update step input
                $($(el).siblings('input')[0]).val(result);

                // Close popup
                $('#gt_rule_fx').parents('.bc-modal').bcPopUp('close');

            },
        },
        afterLoad: function(){

            // If we are opening it for an Action, hide the text bit and enable the function
            if (type == 'action'){
                gt_toggleDisabled('.gt_fx_func', '#gt_fx_text_input');
                $('#gt_fx_text_input').parent().remove();
            }

            // Get the value of the input and update the popup
            var val = $($(el).siblings('input')[0]).val();

            if (val.startsWith('"') || val.startsWith("'")){
                // Remove first and last charatcer, which should be quotes
                val = val.substr(1, val.length - 2);
                $('#gt_fx_text_input').val(val);
                gt_toggleDisabled('#gt_fx_text_input', '.gt_fx_func');
            } else if (val != '') {
                $('#gt_fx_func').val(val);
                gt_toggleDisabled('.gt_fx_func', '#gt_fx_text_input');
            }


        }
    } );

}

function gt_toggleDisabled(a, b){

    $(a).removeProp('disabled');
    $(a).removeAttr('disabled');
    $(b).prop('disabled', true);
    $(b).attr('disabled', '');

    // Update enabled img
    var id = a.substr(1, a.length)
    $('#'+id+'_enabled').attr('src', M.cfg.wwwroot+'/blocks/gradetracker/pix/on.png');

    var idB = b.substr(1, b.length)
    $('#'+idB+'_enabled').attr('src', M.cfg.wwwroot+'/blocks/gradetracker/pix/off.png');

}

function gt_ruleFxAddElement(type, fromType, fromVal, el){

    // If disabled, stop
    if ($(el).attr('disabled') == "disabled"){
        return;
    }

    var params = {type: type, fromType: fromType, fromVal: fromVal};

    $('#gt_fx_loading').show();

    // Load content from AJAX call and insert into HTML
    $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_rule_fx_element_options', params: params}, function(data){

        var response = $.parseJSON(data);
        var option = '<span>';

        // if we are adding a filter, it is 3 inputs, not the standard select menu
        if (type == 'filter'){

            // Conjunction
            option += '<select class="gt_fx_func" gttype="filter" filter="conjunction">';
            option += '<option value=""></option>';
            $.each(response.conjunctions, function(k, v){
                option +=' <option value="'+v+'">'+v+'</option>';
            });
            option += '</select>';

            // Field
            option += '<select class="gt_fx_func" gttype="filter" filter="field">';
            option += '<option value=""></option>';
            $.each(response.fields, function(k, v){
                option +=' <option value="'+v+'">'+v+'</option>';
            });
            option += '</select>';

            // Value
            option += '<input type="text" placeholder="v1,v2,v3..." class="gt_fx_func" gttype="filter" filter="value" />';

        }

        else if (type == 'input'){

            // Text input for the value
            option += '<input type="text" placeholder="value" class="gt_fx_func" gttype="input" />';

        }

        else {

            var cnt = $('.gt_rule_fx_element_option[type="'+type+'"]').length;
            var id = type + '_' + cnt;
            option += '<select id="'+id+'" class="gt_rule_fx_element_option gt_fx_func" gttype="'+type+'">';
            option += '<option value=""></option>';

            $.each(response, function(k, v){
                if (v.name !== undefined){
                    option += '<option value="'+v.name+'" return="'+v.return+'" object="'+v.object+'" longName="'+v.longName+'">'+v.name+'</option>';
                } else {
                    option += '<option value="'+v+'">'+v+'</option>';
                }
            });

            option += '</select>';

        }

        option += '</span>';

        // Add div
        $('#gt_fx_options').append(option);

        $(el).remove();

        // If we clicked on a method link, remove any filter links
        if (type == 'method'){
            $('#gt_rule_fx_link_filter').remove();
        }

        // Bind
        gt_bindFxInputs();

        $('#gt_fx_loading').hide();


    });

}


function gt_bindFxInputs(){

    // Rebind the onChange event for the select menus
    $('.gt_rule_fx_element_option').off('change');
    $('.gt_rule_fx_element_option').on('change', function(){

        $('#gt_fx_loading').show();

        var id = $(this).attr('id');
        var t = $(this).attr('gttype');
        var val = $(this).val();
        var selectedOption = $(this).find(':selected');
        var returnType = $(selectedOption).attr('return');
        var returnValue = $(selectedOption).attr('object');
        var longName = $(selectedOption).attr('longName');

        $(this).parent().nextAll().each( function(){
            $('#gt_fx_links a[dependenton="'+$(this).attr('id')+'"]').remove();
            $(this).remove();
        } );

        var objStr = '';
        $('.gt_rule_fx_element_option').each( function(){
            objStr += $(this).attr('type') + '['+$(this).val()+'].';
        } );
        objStr = objStr.substring(0, objStr.length-1);

        if (val == ''){
            $('#gt_fx_links a[dependenton="'+id+'"]').remove();
            $('#gt_fx_loading').hide();
            return true;
        }

        // Post params
        var p = {type: t, value: val, returnType: returnType, returnValue: returnValue, objStr: objStr, longName: longName}; 

        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_rule_fx_links', params: p}, function(data){

            var response = $.parseJSON(data);

            $('#gt_fx_links').html('');

            $.each(response, function(k, v){
                var useName = (longName !== undefined) ? longName : val;
                $('#gt_fx_links').append("<a href='#' id='gt_rule_fx_link_"+v+"' class='gt_fx_func' dependentOn='"+id+"' onclick='gt_ruleFxAddElement(\""+v+"\", \""+t+"\", \""+useName+"\", this);return false;'>"+M.util.get_string('add'+v, 'block_gradetracker')+"</a>");
            });         

            $('#gt_fx_loading').hide();

        });

        gt_updateFx();

    });

    $('select.gt_fx_func').on('change', function(){
        gt_updateFx();
    });

    // Bind change events to update the function in the textarea        
    $('input.gt_fx_func').off('keyup');
    $('input.gt_fx_func').on('keyup', function(){    
        gt_updateFx();
    });




}

function gt_updateFx(){

    var str = gt_convertElementsToFx();
    $('#gt_fx_func').val(str);

}


function gt_convertElementsToFx(){

    var str = '';
    var filterStr = '';

    $('#gt_fx_options :input').each( function(k, v){

        var val = $(v).val();
        var type = $(v).attr('gttype');

        // Strip quotes
        val = val.replace(/['"]+/g, '');
        val = val.trim();

        if (val !== ''){

            if (type == 'object'){
                str += val;
            } else if (type == 'method'){
                str = str.replace(/\(x\)/g, '()');
                str += '.' + val + '(x)';
            } else if (type == 'filter'){

                var filter = $(v).attr('filter');
                if (filter == 'conjunction'){
                    filterStr = '';
                    filterStr += "'"+val+"'";
                } else if (filter == 'field'){
                    filterStr += ",'"+val+"'";
                } else if (filter == 'value'){

                    var a = val.split(',');
                    a = a.map( function(x){ return "'"+x+"'"; } );
                    val = a.join(',');

                    filterStr += ","+val;
                    str = str.replace('\(x\)', '('+filterStr+')');

                }

            } else if (type == 'input'){
                // We might have an "(x)" from an earlier method, so we want to make sure we are replacing the last occurance of "(x)" in the string
                var n = str.lastIndexOf("(x)");
                str = str.slice(0, n) + str.slice(n).replace('\(x\)', "('"+val+"')");
            }

    }

    } );

    str = str.replace(/\(x\)/g, '()');

    return str;

}


