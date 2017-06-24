function data_bindings(){
    
    $('.gt_transfer_qual_table').each(function(){
        
        $(this).gridviewScroll({ 
            width: 'auto', 
            height: '600',
            freezesize: 1
        });
        
    });
    
}

function gt_bcgt_filter(){
    
    // Check if checkbox is ticked or not
    var chk = $('#gt_hide_quals_no_course').prop('checked');
    
    // Get the family id
    var fam = $('#gt_block_bcgt_old_qual_structure').val();
    
    // Hide everything to start with
    $('select#block_bcgt_quals option').optVisible(false);
    
    // If not checked, we don't care if it has no courses
    if (!chk){
        
        if (fam === ''){
            $('select#block_bcgt_quals option').optVisible(true);
        } else {
            $('select#block_bcgt_quals option.gt_bcgt_old_type_'+fam).optVisible(true);
        }
        
    }
    // If we want only quals attached to courses
    else {
        
        // If no family was selected, just show all the ones attached to courses
        if (fam === ''){
            $('select#block_bcgt_quals option:not(.gt_bcgt_old_cnt_courses_0)').optVisible(true);
        } else {
            $('select#block_bcgt_quals option.gt_bcgt_old_type_'+fam+':not(.gt_bcgt_old_cnt_courses_0)').optVisible(true);
        }
        
    }
        
}

/**
 * Toggle tickboxes for student
 * @param {type} id
 * @returns {undefined}
 */
function tickAllStudent(qualID, studentID){
    
    var chk = $('.qs_'+qualID+'_'+studentID).prop('checked');
    $('.qs_'+qualID+'_'+studentID).prop('checked', !chk);
    
}

/**
 * Toggle tickboxes for unit
 * @param {type} id
 * @returns {undefined}
 */
function tickAllUnit(qualID, unitID){
    
    var chk = $('.qu_'+qualID+'_'+unitID).prop('checked');
    $('.qu_'+qualID+'_'+unitID).prop('checked', !chk);
    
}