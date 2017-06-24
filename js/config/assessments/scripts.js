function assessments_bindings(){
    
    $('#gt_assessment_date').datepicker({

        dateFormat: "dd-mm-yy",
        showButtonPanel: true

    } );
    
}

function gtChangeAssessmentType(type){
    
    if (type == 'other'){
        $('#gt_other_type').show();
    } else {
        $('#gt_other_type').hide();
    }
    
}

function gtChangeAssessmentGradingMethod(val){
    
    if (val == 'numeric'){
        $('#grading_numeric_inputs').show();
        $('#gt_assessment_grading_method_structures_cell').hide();
    } else if (val == 'structure') {
        $('#grading_numeric_inputs').hide();
        $('#gt_assessment_grading_method_structures_cell').show();
    } else {
        $('#grading_numeric_inputs').hide();
        $('#gt_assessment_grading_method_structures_cell').hide();
    }
    
}
