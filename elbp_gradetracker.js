function loadGradeTracker(id, el){

    // Load a display type
    var params = { type: 'tracker', studentID: ELBP.studentID, courseID: ELBP.courseID, id: id }
    ELBP.ajax("elbp_gradetracker", "load_display_type", params, function(d){

        $('#elbp_gradetracker_content').html(d);
        ELBP.set_view_link(el);
        
        gt_bindings();
        grid_bindings();
        student_grid_bindings();
        
    }, function(d){
        $('#elbp_gradetracker_content').html('<img src="'+M.cfg.wwwroot+'/blocks/elbp/pix/loader.gif" alt="" />');
    });

}

function loadTrackerPopup(id){
            
    ELBP.load_expanded('elbp_gradetracker', function(){
        var el = $('#qual'+id+'_tab');
        loadGradeTracker(id, el);
    });

}