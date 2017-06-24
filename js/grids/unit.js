function unit_grid_bindings(){

    $(document).ready( function(){

        // Load the grid
        loadUnitGrid();
        
        // Also refresh on screen resize
        var doResize;
        $(window).resize( function(){
            
            clearTimeout(doResize);
            $('#gt_loading').show();
            doResize = setTimeout( function(){
                applyUnitGridBindings();
                $('#gt_loading').hide();
            }, 1000 ); // Had to change to 1 second because Moodle 2.8 now does slow page resizing which messes this up. might need to be changed to higher if doesn't work
            
        } );
        
        
        // Bindings for the switch qual menu
        $('#gt_switch_qual').unbind('change');
        $('#gt_switch_qual').bind('change', function(){
            
            var qID = $(this).val();
            var uID = $('#gt-uID').val();
            var cID = $('#gt-crID').val();
            var access = $('#gt-access').val();
            
            if (qID <= 0 || uID <= 0){
                return;
            }
            
            window.location = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type=unit&id=' + uID + '&access=' + access + '&qualID=' + qID + '&courseID=' + cID;
            
        });
        
        // Bindings for the switch unit menu
        $('#gt_switch_unit').unbind('change');
        $('#gt_switch_unit').bind('change', function(){
            
            var qID = $('#gt-qID').val();
            var uID = $(this).val();
            var cID = $('#gt-crID').val();
            var access = $('#gt-access').val();
            
            if (qID <= 0 || uID <= 0){
                return;
            }
            
            window.location = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type=unit&id=' + uID + '&access=' + access + '&qualID=' + qID + '&courseID=' + cID;
            
        });
        
        // Bindings for the switch course menu
        $('#gt_switch_course').unbind('change');
        $('#gt_switch_course').bind('change', function(){
            
            var qID = $('#gt-qID').val();
            var uID = $('#gt-uID').val();
            var cID = $(this).val();
            var access = $('#gt-access').val();
            
            if (qID <= 0 || uID <= 0){
                return;
            }
            
            window.location = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type=unit&id=' + uID + '&access=' + access + '&qualID=' + qID + '&courseID=' + cID;
            
        });

        //bindings for switch groups
        $('#gt_switch_group').unbind('change');
        $('#gt_switch_group').bind('change', function(){
            
            var qID = $('#gt-qID').val();
            var uID = $('#gt-uID').val();
            var cID = $('#gt-crID').val();
            var access = $('#gt-access').val();
            var groupID = $(this).val();
            
            if (qID <= 0 || uID <= 0){
                return;
            }
            
            window.location = M.cfg.wwwroot + '/blocks/gradetracker/grid.php?type=unit&id=' + uID + '&access=' + access + '&qualID=' + qID + '&groupID=' + groupID + '&courseID=' + cID;
            
        });
        
        //bindings for switch crit on mass update
        $('#gt_mass_switch_crit').unbind('change');
        $('#gt_mass_switch_crit').bind('change', function(){
            
            var crit_value = $("#gt_mass_switch_crit")[0].value;
            var crit_obj = criterionObj[crit_value];
            
        });

    });

}

/**
 * 
 * @param {type} page
 * @returns {undefined}Change page number and load the grid for that page
 */
function changePage(page){
    
    $('#gt-page').val(page);
    $('.gt_pagenumber').removeClass('active');
    $('.gt_pagenumber_'+page).addClass('active');
    loadUnitGrid();
    
}

/**
 * Load the unit grid
 * @param {type} a If this is undefined it just reloads whatever should be there, 
 * otherwise it will load either "view" or "edit" as defined here
 * @returns {undefined} 
 */
function loadUnitGrid(a){
 
    $('#gt_loading').show();
 
    var qID = $('#gt-qID').val();
    var uID = $('#gt-uID').val();
    var cID = $('#gt-crID').val();
    var groupID = $('#gt-groupID').val();
    var access = $('#gt-access').val();
    var page = $('#gt-page').val();
    var view = $('#gt-view').val();
    
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
    
    
    var params = { qualID: qID, unitID: uID, courseID: cID, groupID: groupID, access: access, page: page, view: view };
    
    $.post(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', { action: 'get_unit_grid', params: params }, function(data){
        
        data = $.parseJSON(data);
        
        $('#gt-access').val(access);
        $('#gt_grid_holder').html( data );
        
        setTimeout("applyUnitGridBindings()", 250);
        //applyUnitGridBindings();
        
        $('#gt_loading').hide();
        
    });
    
}

function applyUnitGridBindings(){
    
    // Clear it first if it's already there (has to be by id not class)
    $('#gt_unit_grid').gridviewScroll({ enabled: false }); 
        
    var freeze = $('.gt_grid_freeze_col').length;
    var headerRowCount = 1;
    if ($('.gt_unit_grid_activity_header').length > 0){
        headerRowCount++;
    }
        
    // Do the fixed headers and columns and whatnot
    $('.gt_unit_grid').gridviewScroll({ 
        width: 'auto', 
        height: '600',
        freezesize: freeze,
        headerrowcount: headerRowCount
    });
    
    grid_bindings();
    
}


function refreshPredictedGrades(){
    
}