function quals_bindings(){
    
    // Stop form submission on [enter] of unit name text input
    $('#gt_filter_units_name').unbind('keypress');
    $('#gt_filter_units_name').bind('keypress', function(e){
        if (e.keyCode === 13){
            filterUnitSearch();
            e.preventDefault();
        }
    });
    
    // Stop form submission on [enter] of course name text input
    $('#gt_filter_courses_name').unbind('keypress');
    $('#gt_filter_courses_name').bind('keypress', function(e){
        if (e.keyCode === 13){
            filterCourseSearch();
            e.preventDefault();
        }
    });   
    
    $('#qual_units').off('change');
    $('#qual_units').on('change', function(){ 
        var val = $(this).val();
        var numSelected = (val !== null) ? val.length : 0;
        if (numSelected == 1){
            $('#gt_qual_units_edit_unit_btn').removeAttr('disabled'); 
            $('#gt_qual_units_edit_unit_btn').attr('href', 'config.php?view=units&section=edit&id='+val);
        } else {
            $('#gt_qual_units_edit_unit_btn').attr('disabled', true);
            $('#gt_qual_units_edit_unit_btn').removeAttr('href');
        }
    });
    
    $('#qual_courses').off('change');
    $('#qual_courses').on('change', function(){ 
        var val = $(this).val();
        var numSelected = (val !== null) ? val.length : 0;
        if (numSelected == 1){
            $('#gt_qual_units_edit_course_btn').removeAttr('disabled'); 
            $('#gt_qual_units_edit_course_btn').attr('href', 'config.php?view=course&section=quals&id='+val);
        } else {
            $('#gt_qual_units_edit_course_btn').attr('disabled', true);
            $('#gt_qual_units_edit_course_btn').removeAttr('href');
        }
    });
    
    
   
    
}

/**
 * Filter the unit search by type, level and name
 * @returns {undefined}
 */
function filterUnitSearch(){
    
    var type = $('#gt_filter_units_structure').val();
    var lvl = $('#gt_filter_units_level').val();
    var name = $('#gt_filter_units_name').val();
    
    var params = { structureID: type, levelID: lvl, name: name };
    
    $('#gt_filter_units_loading').show();
    $('#gt_filter_units').html('');
    
    $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_filtered_units', params: params}, function(data){
            
        var units = $.parseJSON(data);
        $.each(units, function(indx, unit){
            
            if ( $('#unit_opt_'+unit.id).length == 0 && $('#qual_unit_opt_'+unit.id).length == 0 ){
                var option = "<option id='unit_opt_"+unit.id+"' value='"+unit.id+"' title='"+unit.title+"'>"+unit.name+"</option>";
                $('#gt_filter_units').append(option);
            }
            
        });
        
        $('#gt_filter_units_loading').hide();
            
    });
    
}

/**
 * Filter the course search by name and category
 * @returns {undefined}
 */
function filterCourseSearch(){
    
    var name = $.trim($('#gt_filter_courses_name').val());   
    var catID = $('#gt_filter_courses_category').val();
    var params = { catID: catID, name: name };
    
    if (name === "" && catID === "") return;
    
    $('#gt_filter_courses_loading').show();
    $('#gt_filter_courses').html('');
    
    $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_filtered_courses', params: params}, function(data){
            
        var courses = $.parseJSON(data);
        $.each(courses, function(indx, course){
            
            if ( $('#course_opt_'+course.id).length == 0 && $('#qual_course_opt_'+course.id).length == 0 ){
                var option = "<option id='course_opt_"+course.id+"' value='"+course.id+"'>"+course.name+"</option>";
                $('#gt_filter_courses').append(option);
            }
            
        });
        
        // If we have lots
        if (courses.length >= searchLimit){
            $('#gt_filter_courses').append("<option>"+M.util.get_string('toomanytoshow', 'block_gradetracker')+"</option>");
        }
        
        $('#gt_filter_courses_loading').hide();
            
    });
    
}

/**
 * Used on click of the "Remove" button to remove a unit from the select menu on the qualification
 * @returns {undefined}
 */
function removeUnitsFromQualSelect(){
 
    var options = $('#qual_units option:selected');
    $.each(options, function(){
        
        var id = $(this).val();
        $('#hidden_qual_unit_'+id).remove();
        $(this).remove();
        
    });
    
}

/**
 * Used on click of the Remove button to remove courses from the select menu on the qualification
 * @returns {undefined}
 */
function removeCoursesFromQualSelect(){
    
    var options = $('#qual_courses option:selected');
    $.each(options, function(){
        
        var id = $(this).val();
        $('#hidden_qual_course_'+id).remove();
        $(this).remove();
        
    });
    
}

/**
 * Add selected units to the qualification's select menu
 * @returns {undefined}
 */
function addUnitsToQualSelect(){
    
    var options = $('#gt_filter_units option:selected');
    $.each(options, function(){
        
        var id = $(this).val();
        
        // Add to qual's unit select
        $(this).prop('selected', false);
        $(this).attr('id', 'qual_unit_opt_'+id);
        $('#qual_units').append( $(this) );
        
        
        // Add to hidden input
        $('#gt_qual_form').append( "<input type='hidden' id='hidden_qual_unit_"+id+"' name='qual_units[]' value='"+id+"' />" );
        
    });
    
}


/**
 * Add selected courses to the qualification's select menu
 * @returns {undefined}
 */
function addCoursesToQualSelect(){
    
    var options = $('#gt_filter_courses option:selected');
    $.each(options, function(){
        
        var id = $(this).val();
        if (id > 0){

            // Add to qual's unit select
            $(this).prop('selected', false);
            $(this).attr('id', 'qual_course_opt_'+id);
            $('#qual_courses').append( $(this) );


            // Add to hidden input
            $('#gt_qual_form').append( "<input type='hidden' id='hidden_qual_course_"+id+"' name='qual_courses[]' value='"+id+"' />" );
        
        }
        
    });
    
}
    
/**
 * Load the default values for custom form elements on a qualification build
 * @param {type} buildID
 * @returns {undefined}
 */
function loadBuildDefaults(buildID){
    
    // Get build defaults
    if ($('#qual_id').length == 0){
        
        $('#gt_build_loading').show();
        
        // Reset values on them all, except checkbox as that confuses the issue
        $('.gt_qual_element[type!="checkbox"]').val('');
        
        // Set checkbox property
        $('.gt_qual_element[type="checkbox"]').prop('checked', false);
        
        var params = { buildID: buildID };
        
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_build_defaults', params: params}, function(data){
            
            defaults = $.parseJSON(data);
            
            $.each(defaults, function(i, v){
                $('#gt_el_'+i).val(v);
                if ( $('#gt_el_'+i).prop('type') == 'checkbox' && v == 1 ){
                    $('#gt_el_'+i).prop('checked', true);
                }
            });
            
            $('#gt_build_loading').hide();
            
        });
        
        
        
    }
    
}
