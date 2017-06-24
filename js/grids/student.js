function student_grid_bindings(){

    $(document).ready( function(){

        // Load the grid
        loadStudentGrid();
        
        // Also refresh on screen resize
        var doResize;
        $(window).resize( function(){
            
            clearTimeout(doResize);
            $('#gt_loading').show();
            doResize = setTimeout( function(){
                applyStudentGridBindings();
                $('#gt_loading').hide();
            }, 1000 ); // Had to change to 1 second because Moodle 2.8 now does slow page resizing which messes this up. might need to be changed to higher if doesn't work
            
        } );
        
        
        // Bindings for the switch qual and user menus
        $('#gt_switch_qual').unbind('change');
        $('#gt_switch_qual').bind('change', function(){
            
            var qID = $(this).val();
            var sID = $('#gt-sID').val();
            var access = $('#gt-access').val();
            window.location = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type=student&id=' + sID + '&access=' + access + '&qualID=' + qID;
            
        });
        
        $('#gt_switch_user').unbind('change');
        $('#gt_switch_user').bind('change', function(){
            
            var sID = $(this).val();
            var qID = $('#gt-qID').val();
            var access = $('#gt-access').val();
            window.location = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type=student&id=' + sID + '&access=' + access + '&qualID=' + qID;
            
        });


    });

}

/**
 * Load the student grid
 * @param {type} a If this is undefined it just reloads whatever should be there, 
 * otherwise it will load either "view" or "edit" as defined here
 * @returns {undefined} 
 */
function loadStudentGrid(a){
 
    $('#gt_loading').show();
 
    var qID = $('#gt-qID').val();
    var sID = $('#gt-sID').val();
    var access = $('#gt-access').val();
    var assessmentView = $('#gt-assessmentView').val();
    var external = ($('#gt-external').length > 0) ? 1 : 0;
    
    if (a == 'v' || a == 'e' || a == 'ae'){
        access = a;
    }
    
    // If we clicked on edit and we are holding down the CTRL button, go to advancedEdit
    var ctrlBtn = 17;
    if (isKeyPressed(ctrlBtn) == true){
        if (a == 'e'){
            access = 'ae';
            a = 'ae';
        } 
    }
        
    
    
    // If we clicked on Edit, toggle the Advanced Edit button to show now
    if (a == 'e'){
        $('#gt_edit_button').hide();
        $('#gt_adv_edit_button').show();
    }
    
    // If we click on Advanced Edit or View, toggle the Edit button to show now
    else if (a == 'ae' || a == 'v'){
        $('#gt_adv_edit_button').hide();
        $('#gt_edit_button').show();
    }
    
    
    
    // Switch target grade & asp grade cells
    if (a == 'e' || a == 'ae'){
        $('.gt_tg_Q'+qID+'_S'+sID+'_view').hide();
        $('.gt_tg_Q'+qID+'_S'+sID+'_edit').show();
        $('.gt_asp_Q'+qID+'_S'+sID+'_view').hide();
        $('.gt_asp_Q'+qID+'_S'+sID+'_edit').show();
    } else {
        $('.gt_tg_Q'+qID+'_S'+sID+'_view').show();
        $('.gt_tg_Q'+qID+'_S'+sID+'_edit').hide();
        $('.gt_asp_Q'+qID+'_S'+sID+'_view').show();
        $('.gt_asp_Q'+qID+'_S'+sID+'_edit').hide();
    }
    
    // Check for external session, e.g. if we are viewing from Parent Portal
    var extSsn = ($('#gt-ext-sid').length === 1) ? $('#gt-ext-sid').val() : 0;
    
    var params = { qualID: qID, studentID: sID, access: access, assessmentView: assessmentView, external: external, extSsn: extSsn };
    
    $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', { action: 'get_student_grid', params: params }, function(data){
        
        data = $.parseJSON(data);
        
        $('#gt-access').val(access);
        $('#gt_grid_holder').html( data );
        
        applyStudentGridBindings();
        
        $('#gt_loading').hide();
        
    });
    
}

function applyStudentGridBindings(){
 
    // Clear it first if it's already there (has to be by id not class)
    $('#gt_student_grid').gridviewScroll({ enabled: false }); 
        
    var tbl = '.gt_student_grid';
        
    // Do the fixed headers and columns and whatnot
    $(tbl).gridviewScroll({ 
        width: 'auto', 
        height: '600',
        freezesize: 1
    });
    
    grid_bindings();
    
}


function refreshGCSEScore(){
    
    var sID = $('#gt-sID').val();
    
    $('#gt_refresh_gcse_loader').show();
    
    var params = { action: 'get_refreshed_gcse_score', params: { studentID: sID } };
    
    $.post( M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', params, function(data){
        
        data = $.parseJSON(data);
        
        if (data['score'] !== undefined){
            $('#gt_user_gcse_score').text( data['score'] );
        }
                
        $('#gt_refresh_gcse_loader').hide();
                
    });
    
}

/**
 * Refresh the target grade on the student's grid
 */
function refreshTargetGrade(){
    
    var sID = $('#gt-sID').val();
    var qID = $('#gt-qID').val();
    
    // Is there more than 1 qualification on the page? E.g. Assessment view
    if ( $('.gt-qID').length > 0 ){
        var qualIDArray = new Array();
        $('.gt-qID').each( function(){
            qualIDArray.push( $(this).val() );
        } );
        qID = qualIDArray;
    }
    
    $('#gt_refresh_tg_loader').show();
    
    var params = { action: 'get_refreshed_target_grade', params: { studentID: sID, qualID: qID } };
        
    $.post( M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', params, function(data){
        
        data = $.parseJSON(data);
        
        if (data['gradeID'] instanceof Array || data['gradeID'] instanceof Object)
        {
                        
            // Grade IDs in the select menus
            $.each( data['gradeID'], function(qualID, val){
                if (val.length > 0){
                    $('#gt_user_target_grade_'+sID+'_'+qualID+' > select.gt_target_grade_award').val( val );
                }
            } );
            
            // Grade text
            $.each( data['grade'], function(qualID, val){
                if (val.length > 0){
                    $('#gt_user_target_grade_'+sID+'_'+qualID+' > span').text( val );
                }
            } );
            
        }
        else
        {
        
            // Set the selected value of the dropdown menu
            if (data['gradeID'] !== undefined){
                $('#gt_user_target_grade_'+sID+'_'+qID+' > select.gt_target_grade_award').val( data['gradeID'] );
            }

            // Set the grade text
            if (data['grade'] !== undefined){
                $('#gt_user_target_grade_'+sID+'_'+qID+' > span').text( data['grade'] );
            }
        
        }
                
        $('#gt_refresh_tg_loader').hide();
                
    });
    
}



/**
 * Refresh the weighted target grade on the student's grid
 */
function refreshWeightedTargetGrade(){
    
    var sID = $('#gt-sID').val();
    var qID = $('#gt-qID').val();
    
    // Is there more than 1 qualification on the page? E.g. Assessment view
    if ( $('.gt-qID').length > 0 ){
        var qualIDArray = new Array();
        $('.gt-qID').each( function(){
            qualIDArray.push( $(this).val() );
        } );
        qID = qualIDArray;
    }
    
    $('#gt_refresh_wtg_loader').show();
    
    var params = { action: 'get_refreshed_weighted_target_grade', params: { studentID: sID, qualID: qID } };
        
    $.post( M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', params, function(data){
        
        data = $.parseJSON(data);
        
        if (data['gradeID'] instanceof Array || data['gradeID'] instanceof Object)
        {
                        
            // Grade text
            $.each( data['grade'], function(qualID, val){
                if (val.length > 0){
                    $('#gt_user_weighted_target_grade_'+sID+'_'+qualID+' > span').text( val );
                }
            } );
            
        }
        else
        {
        
            // Set the grade text
            if (data['grade'] !== undefined){
                $('#gt_user_weighted_target_grade_'+sID+'_'+qID+' > span').text( data['grade'] );
            }
        
        }
                
        $('#gt_refresh_wtg_loader').hide();
                
    });
    
}



/**
 * Refresh the predicted grades on the student grid
 */
function refreshPredictedGrades(){
        
    var qID = $('#gt-qID').val();
    var sID = $('#gt-sID').val();
    
    $('#gt_refresh_pg_loader').show();
    
    var params = { action: 'get_refreshed_predicted_grades', params: { studentID: sID, qualID: qID } };
    
    $.post( M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', params, function(data){
        
        data = $.parseJSON(data);
        
        if (data['average'] !== undefined){
            $('#gt_user_avg_award').text( data['average'] );//.effect( 'highlight', {color: '#ccff66'}, 3000 );
        }
        
        if (data['min'] !== undefined){
            $('#gt_user_min_award').text( data['min'] );//.effect( 'highlight', {color: '#ccff66'}, 3000 );
        }
        
        if (data['max'] !== undefined){
            $('#gt_user_max_award').text( data['max'] );//.effect( 'highlight', {color: '#ccff66'}, 3000 );
        }
        
        if (data['final'] !== undefined){
            $('#gt_user_final_award').text( data['final'] );//.effect( 'highlight', {color: '#ccff66'}, 3000 );
        }
        
        $('#gt_refresh_pg_loader').hide();
                
    });
    
}