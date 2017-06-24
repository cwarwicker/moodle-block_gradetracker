function course_bindings(){
   
    $('#gt_stud_quals').gridviewScroll({ 
        width: 'auto', 
        height: 600,
        freezesize: 2
    });
    
    $('#gt_staff_quals').gridviewScroll({ 
        width: 'auto', 
        height: 600,
        freezesize: 2
    });
    
    $('.gt_stud_units').each( function(){
        
        var id = $(this).attr('id');
        var credits = $(this).attr('defaultCredits');
        var freeze = 2;
        if (credits > 0){
            freeze++;
        }
        $('#'+id).gridviewScroll({ 
            width: 'auto', 
            height: 600,
            freezesize: freeze
        });
        
    } );
    
    
    $('.gt_user_unit_checkbox').off('change');
    $('.gt_user_unit_checkbox').on('change', function(){
        
        var qID = $(this).attr('qID');
        var sID = $(this).attr('sID');

        updateStudentUnitCredits(sID, qID);
        
    });
    
    $('.gt_activities_overview_criterion').off('click');
    $('.gt_activities_overview_criterion').on('click', function(){
        
        var qID = $(this).attr('qID');
        var uID = $(this).attr('uID');
        var cID = $(this).attr('cID');
        var courseID = $('#gt_cid').val();
        
        var params = { qualID: qID, unitID: uID, critID: cID };
    
        $('#gt_activities_overview_loading').show();
        $('#gt_activities_overview_details').hide();
        $('.gt_activities_overview_details_row').remove();
        $('#gt_activities_overview_details_qual').text( '' );
        $('#gt_activities_overview_details_unit').text( '' );
        $('#gt_activities_overview_details_criterion').text( '' );

        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_criterion_activities_overview', params: params}, function(data){

            var response = $.parseJSON(data);
            $('#gt_activities_overview_details_qual').text( response.qualification );
            $('#gt_activities_overview_details_unit').text( response.unit );
            $('#gt_activities_overview_details_criterion').text( response.criterion );

            $.each(response.links, function(indx, activity){
                
                var row = "";
                row += "<tr class='gt_activities_overview_details_row'>";
                    row += "<td><img class='gt_16' src='"+activity.modicon+"' alt='"+activity.modname+"' title='"+activity.modname+"' /> <a href='"+M.cfg.wwwroot+"/blocks/gradetracker/config.php?view=course&id="+courseID+"&section=activities&page=add&cmid="+activity.cmid+"'><b>"+activity.name+"</b></a></td>";
                    row += "<td>";
                        $.each(activity.criteria, function(indx2, crit){
                            row += crit.name + ", ";
                        });
                    row += "</td>";
                row += "</tr>";
                $('div#gt_activities_overview_details table').append(row);
                
            });
            
            $('#gt_activities_overview_loading').hide();
            $('#gt_activities_overview_details').show();

        });
        
    });
    
    
    
    
    
    
}

function updateStudentUnitCredits(sID, qID){
    
    // Count credits ticked
    var cnt = 0;
    $('.gt_user_unit_user_'+qID+'_'+sID).each( function(){
        if ( $(this).prop('checked') === true ){
            cnt += parseInt( $(this).attr('credits') );
        }
    } );

    $('.usr_credits_'+sID+'_'+qID).text(cnt);
    
    var max = parseInt( $('#gt_stud_units_'+qID).attr('defaultCredits') );
    var cl = (cnt > max) ? 'gt_incorrect_credits' : '';

    $('.usr_credits_'+sID+'_'+qID).parent().removeClass('gt_incorrect_credits');
    $('.usr_credits_'+sID+'_'+qID).parent().addClass(cl);
    
}

function updateUnitCredits(uID, qID){
        
    // Loop through the students with this tickbox
    $('.gt_user_unit_unit_'+qID+'_'+uID).each( function(){
        
        var sID = $(this).attr('sID');
        
        // Count credits ticked
        var cnt = 0;
        
        $('.gt_user_unit_user_'+qID+'_'+sID).each( function(){
            if ( $(this).prop('checked') === true ){
                cnt += parseInt( $(this).attr('credits') );
            }
        } );

        $('.usr_credits_'+sID+'_'+qID).text(cnt);
        
        var max = parseInt( $('#gt_stud_units_'+qID).attr('defaultCredits') );
        var cl = (cnt > max) ? 'gt_incorrect_credits' : '';
                
        $('.usr_credits_'+sID+'_'+qID).parent().removeClass('gt_incorrect_credits');
        $('.usr_credits_'+sID+'_'+qID).parent().addClass(cl);
        
    } );
    
    
    
}

/**
 * Toggle tickboxes for student
 * @param {type} id
 * @returns {undefined}
 */
function tickAllStudent(id, cID){
    
    var chk = $('.gt_user_qual_user_'+id+'_'+cID).prop('checked');
    $('.gt_user_qual_user_'+id+'_'+cID).prop('checked', !chk);
    
}

/**
 * Toggle tickboxes for qual
 * @param {type} id
 * @returns {undefined}
 */
function tickAllQual(id, cID){
    
    var chk = $('.gt_user_qual_qual_'+id+'_'+cID).prop('checked');
    $('.gt_user_qual_qual_'+id+'_'+cID).prop('checked', !chk);
    
}


/**
 * Toggle tickboxes for student
 * @param {type} id
 * @returns {undefined}
 */
function tickAllStaff(id, cID){
    
    var chk = $('.gt_staff_qual_staff_'+id+'_'+cID).prop('checked');
    $('.gt_staff_qual_staff_'+id+'_'+cID).prop('checked', !chk);
    
}

/**
 * Toggle tickboxes for qual
 * @param {type} id
 * @returns {undefined}
 */
function tickAllQualStaff(id, cID){
    
    var chk = $('.gt_staff_qual_qual_'+id+'_'+cID).prop('checked');
    $('.gt_staff_qual_qual_'+id+'_'+cID).prop('checked', !chk);
    
}

/**
 * Toggle tickboxes for student's units
 * @param {type} id
 * @param {type} unitid
 * @returns {undefined}
 */
function tickAllStudentUnits(id, qualid){
 
    var elem = document.getElementById("unitset_dropdown_img_"+qualid);
    
    if(elem.style.transform == "rotate(180deg)"){
        
        $('.gt_user_unit_user_'+qualid+'_'+id).prop('checked', false);
        $("#unitset_select_" + qualid + " option:selected").each(function()
        {
            $(".gt_user_unit_user_"+qualid+'_'+id+".gt_user_unit_unit_"+qualid+'_'+$( this ).val()).prop('checked', true);
        });
    }
    else {
        var chk = $('.gt_user_unit_user_'+qualid+'_'+id).prop('checked');
        $('.gt_user_unit_user_'+qualid+'_'+id).prop('checked', !chk);
    }
    
    updateStudentUnitCredits(id, qualid);
    
}

function tickAll(type, qualid){
    
    var chk = $('.gt_'+type+'_unit_unit_'+qualid).prop('checked');
    $('.gt_'+type+'_unit_unit_'+qualid).prop('checked', !chk);
    
}

/**
 * Toggle tickboxes for staff's units
 * @param {type} id
 * @param {type} unitid
 * @returns {undefined}
 */
function tickAllStaffUnits(id, qualid){
 
    var elem = document.getElementById("unitset_dropdown_img_"+qualid);
    
    if(elem.style.transform == "rotate(180deg)"){
        $('.gt_staff_unit_staff_'+qualid+'_'+id).prop('checked', false);
        $("#unitset_select_" + qualid + " option:selected").each(function()
        {
            $(".gt_staff_unit_staff_"+qualid+'_'+id+".gt_user_unit_unit_"+qualid+'_'+$( this ).val()).prop('checked', true);
        });
    }
    else {
        var chk = $('.gt_staff_unit_staff_'+qualid+'_'+id).prop('checked');
        $('.gt_staff_unit_staff_'+qualid+'_'+id).prop('checked', !chk);
    }
}

/**
 * Toggle all tickboxes for unit's STUDENT AND STAFF
 * @param {type} unitid
 * @param {type} qualid
 * @returns {undefined}
 */
function tickAllUnitUsers(unitid, qualid){
    
    var chk = $('.gt_user_unit_unit_'+qualid+'_'+unitid).prop('checked');
    $('.gt_user_unit_unit_'+qualid+'_'+unitid).prop('checked', !chk);
    $('.gt_staff_unit_unit_'+qualid+'_'+unitid).prop('checked', !chk);
    
    updateUnitCredits(unitid, qualid);
    
}

/**
 * Tick all users of a specific role onto a unit
 * @param {type} unitid
 * @param {type} qualid
 * @param {type} role
 * @returns {undefined}
 */
function tickAllUnitUsersRole(unitid, qualid, role){
    
    console.log(unitid + ':' + qualid +':'+role);
    
    if (role === 'STUDENT'){
        
        var chk = $('.gt_user_unit_unit_'+qualid+'_'+unitid).prop('checked');
        console.log(chk);
        $('.gt_user_unit_unit_'+qualid+'_'+unitid).prop('checked', !chk);
        console.log( $('.gt_user_unit_unit_'+qualid+'_'+unitid).length );
        updateUnitCredits(unitid, qualid);
    
    }
    
    else if (role === 'STAFF'){
        
        var chk = $('.gt_staff_unit_unit_'+qualid+'_'+unitid).prop('checked');
        $('.gt_staff_unit_unit_'+qualid+'_'+unitid).prop('checked', !chk);
        
    }
        
    
}

/**
 * Switch to a different parent/child course of this course
 * @param {type} id
 * @param {type} section
 * @returns {undefined}
 */
function switchToCourse(id, section){
    window.location = M.cfg.wwwroot + '/blocks/gradetracker/config.php?view=course&section='+section+'&id='+id;    
}


/**
 * Shows/hides select box for unit sets
 * @param {type} id
 * @returns {undefined}
 */
function unitSetDropdown(id){
    
    var elem = document.getElementById("unitset_dropdown_img_"+id);
    
    if(elem.style.transform == "rotate(180deg)"){
        document.getElementById("unitset_dropdown_img_"+id).style.transform = "";
        document.getElementById("unitset_select_"+id).style.display = "none";
    }
    else {
        document.getElementById("unitset_dropdown_img_"+id).style.transform = "rotate(180deg)";
        document.getElementById("unitset_select_"+id).style.display = "";
    }
}
