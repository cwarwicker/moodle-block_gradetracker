function gt_mod_hook_bindings()
{
    
    // Bind change to unit select
    $('.gt_mod_hook_units').unbind('change');
    $('.gt_mod_hook_units').bind('change', function(){
                
        var cmID = $('#gt_cmid').val();
        var courseID = $('#gt_cid').val();
        var qualID = $(this).attr('qualID');
        var unitID = $(this).val();
        var params = { qualID: qualID, unitID: unitID, cmID: cmID, courseID: courseID };
        
        $('#gt_mod_hook_loader_'+qualID).show();
        
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_mod_hook_unit', params: params}, function(data){
            
            var response = $.parseJSON(data);

            var output = "";
            output += "<div id='gt_hooked_unit_"+qualID+"_"+unitID+"' class='gt_hooked_unit'>";
            
            output += ""+response.unit+" <a href='#' class='gt_mod_hook_delete_unit' qualID='"+qualID+"' unitID='"+unitID+"'><img src='"+M.util.image_url('t/delete')+"' /></a><br>";
            
            output += "<table class='gt_c gt_hook_unit_criteria'>";
                output += "<tr>";
                    $.each(response.criteria, function(indx, crit){
                        output += "<th>"+crit.name+"</th>";
                    });
                output += "</tr>";
                output += "<tr>";
                    $.each(response.criteria, function(indx, crit){
                        
                        output += "<td>";
                        
                        if (response.parts){
                            output += "<select name='gt_criteria["+qualID+"]["+unitID+"]["+crit.id+"]'>";
                                output += "<option value='0'></option>";
                                $.each(response.parts, function(indx, part){
                                    output += "<option value='"+part.id+"'>"+part.name+"</option>";
                                });
                            output += "</select>";
                        } else {
                            output += "<input type='checkbox' name='gt_criteria["+qualID+"]["+unitID+"]["+crit.id+"]' />";
                        }
                        
                        output += "</td>";
                        
                    });
                output += "</tr>";
            output += "</table>";
            output += "</div>";
            
            $('#gt_mod_hook_qual_units_'+qualID).append(output);
            $('#gt_mod_hook_loader_'+qualID).hide();
            
            gt_mod_hook_bindings();
            
        });
        
        // Set selected index to 0
        $(this).prop('selectedIndex', 0);
        
        // Disable this option so we can't select it again unless we remove the unit from the form
        $(this).children('option[value="'+unitID+'"]').prop('disabled', true);
        
    });
    
    
    // Bind delete unit buttons
    $('.gt_mod_hook_delete_unit').unbind('click');
    $('.gt_mod_hook_delete_unit').bind('click', function(e){
        
        e.preventDefault();
        var qualID = $(this).attr('qualID');
        var unitID = $(this).attr('unitID');
        $('#gt_hooked_unit_'+qualID+'_'+unitID).remove();
        $('#gt_mod_hook_'+qualID+'_units_select').children('option[value="'+unitID+'"]').prop('disabled', false);;
        
    });
    
    $('.gt_mod_hook_delete_activity').unbind('click');
    $('.gt_mod_hook_delete_activity').bind('click', function(e){
        
        e.preventDefault();
        var cmID = $(this).attr('cmID');
        $('#gt_hooked_activity_'+cmID).remove();
        $('.gt_mod_activity').children('option[value="'+cmID+'"]').prop('disabled', false);
        
    });
    
    // Bind changing the qualification drop down, to change the units displayed
    $('.gt_mod_change_qual_units').unbind('change');
    $('.gt_mod_change_qual_units').bind('change', function(e){
        
        // Clear activities
        $('#gt_mod_hook_activities').html('');
        
        $('#gt_mod_change_qual_units_units').prop('selectedIndex', 0);
        var qualID = $(this).val();
        $('option.AQU').hide();
        $('option.Q_'+qualID).show();
        
        
    });
    
    // Bind change the qual unit, to get all the activities linked to it
    $('#gt_mod_change_qual_units_units').unbind('change');
    $('#gt_mod_change_qual_units_units').bind('change', function(){
        
        var courseID = $('#gt_cid').val();
        var qualID = $('.gt_mod_change_qual_units').val();
        var unitID = $(this).val();
        var params = { qualID: qualID, unitID: unitID, courseID: courseID };
        
        // Loader gif
        $('#gt_mod_hook_loader_activity').show();
        
        // Clear activities
        $('#gt_mod_hook_activities').html('');
        
        // Clear option disables
        $('.gt_mod_activity').children('option').prop('disabled', false);
        
        
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_mod_hook_unit_activities', params: params}, function(data){
            
            var response = $.parseJSON(data);
            
            var output = "";
            
            $.each(response, function(index, cm){
                
                output += "<div id='gt_hooked_activity_"+cm.id+"' class='gt_hooked_unit'>";
                    output += "<img src='"+cm.icon+"' /> "+cm.name+" <a href='#' class='gt_mod_hook_delete_activity' cmID='"+cm.id+"'><img src='"+M.util.image_url('t/delete')+"' /></a><br>";
                    output += "<table class='gt_c gt_hook_unit_criteria'>";
                        output += "<tr>";
                            $.each(cm.criteria, function(indx, crit){
                                output += "<th>"+crit.name+"</th>";
                            });
                        output += "</tr>";
                        output += "<tr>";
                            $.each(cm.criteria, function(indx, crit){

                                output += "<td>";

                                    if (cm.parts){
                                        output += "<select name='gt_criteria["+cm.id+"]["+crit.id+"]'>";
                                            output += "<option value='0'></option>";
                                            $.each(cm.parts, function(indx, part){
                                                var sel = ( cm.partsLinked[crit.id] !== undefined && cm.partsLinked[crit.id] == part.id ) ? 'selected' : '';
                                                output += "<option value='"+part.id+"' "+sel+" >"+part.name+"</option>";
                                            });
                                        output += "</select>";
                                    } else {
                                        var chk = ( cm.linked.indexOf(crit.id) >= 0 ) ? 'checked' : '';
                                        output += "<input type='checkbox' name='gt_criteria["+cm.id+"]["+crit.id+"]' "+chk+" />";
                                    }

                                output += "</td>";

                            });
                        output += "</tr>";
                    output += "</table>";
                output += "</div>";
                
                // Disable this option so we can't select it again unless we remove the unit from the form
                $('.gt_mod_activity').children('option[value="'+cm.id+'"]').prop('disabled', true);
                
            });

            $('#gt_mod_hook_activities').append(output);
            $('#gt_mod_hook_loader_activity').hide();            
            
            gt_mod_hook_bindings();
            
        });
        
        // Set selected index to 0
        $('.gt_mod_activity').prop('selectedIndex', 0);
        
        
        
        
        
    });
    
    // Bind change to activity select
    $('.gt_mod_activity').unbind('change');
    $('.gt_mod_activity').bind('change', function(){
                
        var cmID = $(this).val();
        var qualID = $('.gt_mod_change_qual_units').val();
        var unitID = $('#gt_mod_change_qual_units_units').val();
        var courseID = $('#gt_cid').val();
        
        if (qualID == "" || unitID == "" || courseID == "") return false;
        
        var params = { qualID: qualID, unitID: unitID, courseID: courseID, cmID: cmID };
        
        $('#gt_mod_hook_loader_activity').show();
        
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_mod_hook_activity', params: params}, function(data){
            
            var response = $.parseJSON(data);
            var output = "";
                        
            output += "<div id='gt_hooked_activity_"+response.id+"' class='gt_hooked_unit'>";
            output += "<img src='"+response.icon+"' /> "+response.name+" <a href='#' class='gt_mod_hook_delete_activity' cmID='"+response.id+"'><img src='"+M.util.image_url('t/delete')+"' /></a><br>";
            
            output += "<table class='gt_c gt_hook_unit_criteria'>";
                output += "<tr>";
                    $.each(response.criteria, function(indx, crit){
                        output += "<th>"+crit.name+"</th>";
                    });
                output += "</tr>";
                output += "<tr>";
                    $.each(response.criteria, function(indx, crit){
                        
                        output += "<td>";
                        
                            if (response.parts){
                                output += "<select name='gt_criteria["+cmID+"]["+crit.id+"]'>";
                                    output += "<option value='0'></option>";
                                    $.each(response.parts, function(indx, part){
                                        output += "<option value='"+part.id+"'>"+part.name+"</option>";
                                    });
                                output += "</select>";
                            } else {
                                output += "<input type='checkbox' name='gt_criteria["+cmID+"]["+crit.id+"]' />";
                            }
                        
                        output += "</td>";
                                
                    });
                output += "</tr>";
            output += "</table>";
            output += "</div>";
            
            $('#gt_mod_hook_activities').append(output);
            $('#gt_mod_hook_loader_activity').hide();
            
            gt_mod_hook_bindings();
            
        });
        
        // Set selected index to 0
        $(this).prop('selectedIndex', 0);
        
        // Disable this option so we can't select it again unless we remove the unit from the form
        $(this).children('option[value="'+cmID+'"]').prop('disabled', true);
        
    });
    
}