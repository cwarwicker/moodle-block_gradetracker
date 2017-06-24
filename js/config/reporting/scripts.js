function reporting_bindings(){
    
    $('input.gt_run_report').off('click');
    $('input.gt_run_report').on('click', function(){
                
        // Disable input button while running
        var btn = $(this);
        btn.prop('disabled', true);
        btn.val( M.util.get_string('running', 'block_gradetracker') + '...' );
        
        var params = {};
        var report = $(this).attr('report');
        params.report = report;
        params.btn = btn.attr('id');
        params.params = [];
        
        $('.report_option').each( function(){
            
            var nm = $(this).attr('name');
            var vl = $(this).val();
            
            params.params.push( { name: nm, value: vl } );
            
        } );
        
        gtAjaxProgress( M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'download_report', params: params }, '#gt_report_progress', function(data){
            
            // Reset button
            btn.removeProp('disabled');
            btn.val( M.util.get_string('run', 'block_gradetracker') );
            
            // Download file
            window.location = M.cfg.wwwroot + '/blocks/gradetracker/download.php?f='+data.file+'&t='+data.time;
            
        });
        
    });
    
    
    $('#gt_log_search_qual').off('change');
    $('#gt_log_search_qual').on('change', function(){
        
        // Reset select menus
        $('#gt_log_search_unit').html('<option value="">'+M.util.get_string('allunits', 'block_gradetracker')+'</option>');
        $('#gt_log_search_ass').html('<option value="">'+M.util.get_string('allass', 'block_gradetracker')+'</option>');
        $('#gt_log_search_crit').html('<option value="">'+M.util.get_string('allcrit', 'block_gradetracker')+'</option>');
        
        var qID = $(this).val();
        if (qID == ''){
            // Reset units to all units
            if (typeof allUnits !== 'undefined'){
                $.each(allUnits, function(indx, obj){
                    $('#gt_log_search_unit').append('<option value="'+obj.id+'">'+obj.name+' ['+obj.id+']</option>');
                });
            }
            return;
        }
        
        $('#gt_log_search_load').show();
        var params = {qualID: qID};

        // Units
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_qual_units', params: params}, function(data){
            
            var units = $.parseJSON(data);
            $.each(units['order'], function(indx, uID){
                $('#gt_log_search_unit').append('<option value="'+uID+'">'+units['units'][uID]+'</option>');
            });
            
            $('#gt_log_search_load').hide();
            
        });
        
        // Assessments
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_qual_assessments', params: params}, function(data){
            
            var assessments = $.parseJSON(data);
            $.each(assessments['order'], function(indx, aID){
                $('#gt_log_search_ass').append('<option value="'+aID+'">'+assessments['ass'][aID]+'</option>');
            });
                        
            $('#gt_log_search_load').hide();
            
        });
        
        
    });
    
    
    $('#gt_log_search_unit').off('change');
    $('#gt_log_search_unit').on('change', function(){
        
        // Reset select menus
        $('#gt_log_search_crit').html('<option value="">'+M.util.get_string('allcrit', 'block_gradetracker')+'</option>');

        var uID = $(this).val();
        if (uID == ''){
            return;
        }
        
        $('#gt_log_search_load').show();
        var params = {unitID: uID};

        // Criteria
        $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_unit_criteria', params: params}, function(data){
            
            var criteria = $.parseJSON(data);
            $.each(criteria['order'], function(indx, cID){
                $('#gt_log_search_crit').append('<option value="'+cID+'">'+criteria['criteria'][cID]+'</option>');
            });
            
            $('#gt_log_search_load').hide();
            
        });
        
        
    });
    
    
    
    $('#gt_log_search_course').off('change');
    $('#gt_log_search_course').on('change', function(){
        
        var cID = $(this).val();
        if (cID == 'OTHER'){
            $('#gt_log_search_course_name').show();
        } else {
            $('#gt_log_search_course_name').val('');
            $('#gt_log_search_course_name').hide();
        }      
        
    });
    
    
    
}


