define(['jquery', 'jqueryui'], function($, ui) {

  var config = {};
  config.supportedCritTypes = [];
  config.cntCrit = 0;
  config.cntNumericSubCriteria = 0;
  config.cntNumericObservationCriteria = 0;
  config.arrayOfNumericSubCriteria = [];
  config.arrayOfNumericObservationCriteria = [];
  config.maxNumericPoints = 0;
  config.cntRangedRangeCriteria = 0;
  config.cntRangedSubCriteria = 0;
  config.critGradingStructures = [];
  config.critGradingTypes = [];


  config.init = function(data){

    // Count existing criteria
    config.cntCrit = $('.gt_unit_criteria_table_row').length;

    // Supported criteria types
    $.each(data['supportedTypes'], function(index, el){
      config.supportedCritTypes[el.id] = el.type;
    });

    // Remove undefined elements, as the keys are the ids
    config.supportedCritTypes = config.supportedCritTypes.filter(function(el){
      return el !== null;
    });

    // Criteria grading structures
    $.each(data['gradingStructures'], function(index, el){
      config.critGradingStructures.push( { id: el.id, name: el.name } );
    });

    // Criteria grading types
    $.each(data['gradingTypes'], function(index, el){
      config.critGradingTypes.push(el);
    });

    config.bindings();

    // Update parent drop-downs
    config.applyCritNameBlurFocus();

  }

  config.bindings = function(){

    // Add criteria based on letter drop-downs
    $('.gt_update_unit_criteria_letter').unbind('change');
    $('.gt_update_unit_criteria_letter').bind('change', function(){

        var letter = $(this).attr('letter');
        var num = $(this).val();
        var cnt = 0;

        // Count the number of criteria currently starting with this letter
        $('.critNameInput').each( function(){

            var val = $(this).val();
            if (val.match("^"+letter) !== null){
                cnt++;
                // If we have more than the number asked for, remove it
                if (cnt > num){
                    var rownum = $($(this).parents('tr')[0]).attr('rownum');
                    config.removeCriterion(rownum);
                }
            }

        });


        // If we haven't yet met the number asked for, add some more
        while(cnt < num){

            cnt++;

            // Add new criterion
            config.addNewCriterion(letter + cnt);

        }


    });


    // Load unit defaults based on level
    $('#gt_load_unit_defaults').unbind('change');
    $('#gt_load_unit_defaults').unbind('change', function(){

      var structureID = $('#unit_type_id').val();
      var levelID = $(this).val();

      // Get build defaults
      if ($('#unit_id').length == 0){

          $('#gt_level_loading').show();

          // Reset values on them all, except checkbox as that confuses the issue
          $('.gt_unit_element[type!="checkbox"]').val('');

          // Set checkbox property
          $('.gt_unit_element[type="checkbox"]').prop('checked', false);

          var params = { structureID: structureID, levelID: levelID };

          GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_unit_defaults', params: params}, function(data){

              defaults = $.parseJSON(data);

              $.each(defaults, function(i, v){
                  $('#gt_el_'+i).val(v);
                  if ( $('#gt_el_'+i).prop('type') == 'checkbox' && v == 1 ){
                      $('#gt_el_'+i).prop('checked', true);
                  }
              });

              $('#gt_level_loading').hide();

          });

      }

    });

    // Add new criterion
    $('#gt_add_new_criterion').unbind('click');
    $('#gt_add_new_criterion').bind('click', function(e){

      config.addNewCriterion( $(this).attr('critName') );
      e.preventDefault();

    });


    // Remove criterion row
    $('.gt_remove_criterion').unbind('click');
    $('.gt_remove_criterion').bind('click', function(e){

      config.removeCriterion( $(this).attr('critNum') );
      e.preventDefault();

    });


    // Add a child criterion to a criterion
    $('.gt_add_child_criterion').unbind('click');
    $('.gt_add_child_criterion').bind('click', function(e){

      var pNum = $(this).attr('critNum');

      // First add a normal new criterion
      config.addNewCriterion();

      var cntCrit = config.cntCrit;

      // Then get the row we just added
      var row = $('.gt_criterion_row_'+cntCrit);

      // And the row we added the child from
      var parent = $('.gt_criterion_row_'+pNum);

      // And now copy values across

      // Name
      // Count how many criteria have this as a parent
      var cnt = $('.parent_criteria_select').filter( function(){ return (this.value == pNum); } ).length;
      cnt++;

      var nm = $(parent).find('td input.critNameInput').val();
      $(row).find('td input.critNameInput').val( nm + '.' + cnt );

      // Type
      var type = $(parent).find('select[name="unit_criteria['+pNum+'][type]"]').val();
      $(row).find('select[name="unit_criteria['+cntCrit+'][type]"]').val(type);

      // Parent
      $(row).find('select[name="unit_criteria['+cntCrit+'][parent]"]').val(pNum);

      // Grading structure
      var grade = $(parent).find('select[name="unit_criteria['+pNum+'][grading]"]').val();
      $(row).find('select[name="unit_criteria['+cntCrit+'][grading]"]').val(grade);

      // Grading type
      var gType = $(parent).find('select[name="unit_criteria['+pNum+'][gradingtype]"]').val();
      $(row).find('select[name="unit_criteria['+cntCrit+'][gradingtype]"]').val(gType);

      e.preventDefault();

    });


    // Duplicate a criterion
    $('.gt_duplicate_criterion').unbind('click');
    $('.gt_duplicate_criterion').bind('click', function(e){

      var pNum = $(this).attr('critNum');

      // First add a normal new criterion
      config.addNewCriterion();

      var cntCrit = config.cntCrit;

      // Then get the row we just added
      var row = $('.gt_criterion_row_'+cntCrit);

      // And the row we duplicated
      var parent = $('.gt_criterion_row_'+pNum);

      // And now copy values across

      // Type
      var type = $(parent).find('select[name="unit_criteria['+pNum+'][type]"]').val();
      $(row).find('select[name="unit_criteria['+cntCrit+'][type]"]').val(type);

      // Parent
      var par = $(parent).find('select[name="unit_criteria['+pNum+'][parent]"]').val();
      $(row).find('select[name="unit_criteria['+cntCrit+'][parent]"]').val(par);

      // Grading structure
      var grade = $(parent).find('select[name="unit_criteria['+pNum+'][grading]"]').val();
      $(row).find('select[name="unit_criteria['+cntCrit+'][grading]"]').val(grade);

      // Grading type
      var gType = $(parent).find('select[name="unit_criteria['+pNum+'][gradingtype]"]').val();
      $(row).find('select[name="unit_criteria['+cntCrit+'][gradingtype]"]').val(gType);

      // Weighting
      var weight = $(parent).find('input[name="unit_criteria['+pNum+'][weight]"]').val();
      $(row).find('input[name="unit_criteria['+cntCrit+'][weight]"]').val(weight);

      // Options
      // TODO

      e.preventDefault();

    });




    // General bindings
    GT.bind();

  }



  config.addNewCriterion = function(name){

    var output = "";

    if (name === undefined){
      name = "";
    }

    config.cntCrit++;

    var cntCrit = config.cntCrit;

    output += "<tr class='gt_criterion_row_"+cntCrit+" gt_unit_criteria_table_row' rowNum='"+cntCrit+"'>";

        // Name
        output += "<td><input type='text' placeholder='C"+cntCrit+"' name='unit_criteria["+cntCrit+"][name]' class='critNameInput' value='"+name+"' /></td>";

        // Type
        output += "<td>";
            output += "<select name='unit_criteria["+cntCrit+"][type]' class='gt_change_criterion_type' critNum='"+cntCrit+"'>";
                $.each(config.supportedCritTypes, function(i, v){
                    output += "<option value='"+i+"'>"+v+"</option>";
                });
            output += "</select>";
        output += "</td>";

        // Options
        output += "<td id='gt_criterion_options_cell_"+cntCrit+"' class='gt_criterion_options_cell'>";
        // TODO: Get options dynamically from criterion type
        // output += "<small>Force Popup?</small><input type='checkbox' name='unit_criteria["+cntCrit+"][options][forcepopup]' value='"+cntCrit+"'><br>";
        output += "</td>";
        // Details/Description
        output += "<td><textarea name='unit_criteria["+cntCrit+"][details]'></textarea></td>";

        // Weighting
        output += "<td><input type='number' min='0' step='any' placeholder='1.0' name='unit_criteria["+cntCrit+"][weight]' value='1.0' /></td>";

        // Parent
        output += "<td>";
            output += "<select name='unit_criteria["+cntCrit+"][parent]' class='parent_criteria_select'>";
                output += "<option value=''>"+M.util.get_string('na', 'block_gradetracker')+"</option>";
            output += "</select>&nbsp;&nbsp;";
            output += "<a href='#' class='gt_add_child_criterion' critNum='"+cntCrit+"'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/icons/node.png' class='gt_16' alt='"+M.util.get_string('addchildcrit', 'block_gradetracker')+"' title='"+M.util.get_string('addchildcrit', 'block_gradetracker')+"' /></a>";
        output += "</td>";

        // Grading structure
        output += "<td>";
            output += "<select name='unit_criteria["+cntCrit+"][grading]' id='gt_crit_grading_input_"+cntCrit+"' onchange='changeCriterionGradingStructure("+cntCrit+");return false;'>";

                if (config.critGradingStructures.length == 1){
                      soloGradingStructure = config.critGradingStructures[0]
                      output += "<option value='"+soloGradingStructure.id+"'>"+soloGradingStructure.name+"</option>";
                }
                else {
                    output += "<option value=''></option>"; // Can be blank - readonly criterion
                    $.each(config.critGradingStructures, function(){
                        output += "<option value='"+this.id+"'>"+this.name+"</option>";
                    });
                }

            output += "</select>";
        output += "</td>";

        // Grading type
        output += "<td>";
            output += "<select name='unit_criteria["+cntCrit+"][gradingtype]'>";
                $.each(config.critGradingTypes, function(i, v){
                    output += "<option value='"+v+"'>"+v+"</option>";
                });
            output += "</select>";
        output += "</td>";

        // Duplicate row - So we don't have to change the type, structure, etc... every time if they are all the same
        output += "<td>";
            output += "<a href='#' class='gt_duplicate_criterion' critNum='"+cntCrit+"'>";
                output += "<img src='"+M.util.image_url('t/copy')+"' alt='copy' />";
            output += "</a>";
        output += "</td>";

        // Delete row
        output += "<td>";
            output += "<a href='#' class='gt_remove_criterion' critNum='"+cntCrit+"'>";
                output += "<img src='"+M.util.image_url('t/delete')+"' alt='delete' />";
            output += "</a>";
        output += "</td>";

    output += "</tr>";

    $('#gt_unit_criteria').append(output);

    config.refreshParentCriteriaLists();
    config.applyCritNameBlurFocus();

    // Rebind new elements
    config.bindings();

  }

  config.removeCriterion = function(critNum){
    $('.gt_criterion_row_'+critNum).remove();
    config.refreshParentCriteriaLists();
  }







  // Refresh the drop-down menus of parent criteria with the names of the criteria currently set in the rows
  config.refreshParentCriteriaLists = function(){

    var names = new Array();
    $('.critNameInput').each( function(){
        var tr = $(this).parents('tr');
        var rowNum = $(tr).attr('rowNum');
        names.push( { row: rowNum, name: $(this).val() } );
    } );

    // Loop through the select menus
    $('.parent_criteria_select').each( function(){

        var optionExists = false;
        var current = $(this).val();
        $(this).html('');

        var select = $(this);

        $(select).append('<option value="">N/A</option>');

        $.each(names, function(){

            $(select).append('<option value="'+this.row+'">'+this.name+'</option>');
            if ( current === this.row ){
                optionExists = true;
            }

        });

        if (optionExists){
            $(select).val(current);
        }


    } );

  }

  // Update the parent drop-down with the correct criteria names when you update a criterion name
  config.applyCritNameBlurFocus = function(){

    var critNames = $('.critNameInput');
    if (critNames.length > 0){

        var parSelects = $('.parent_criteria_select');
        if (parSelects.length > 0){

            $('.critNameInput').off('focus');
            $('.critNameInput').on('focus', function(){
                critNameSwitchThis = $(this).parents('tr').attr('rowNum');
            });

            $('.critNameInput').off('blur');
            $('.critNameInput').on('blur', function(){

                var critval = $(this).val();
                critval = critval.replace(/[^0-9a-z- \._ \/]/ig, '');
                $(this).val(critval);

                $.each(parSelects, function(){

                    var options = $(this).children();
                    $.each(options, function(i, o){

                        if ($(o).val() == critNameSwitchThis){
                            $(o).text( critval );
                        }

                    });


                });

                critNameSwitchThis = null;

            });

        }

    }

  }



  var client = {};

  //-- Log something to console
  client.log = function(log){
      console.log('[GT] ' + new Date().toTimeString().split(' ')[0] + ': ' + log );
  }

  //-- Initialise the scripts
  client.init = function(data) {

    // Bindings
    config.init(data);

    client.log('Loaded config_units.js');

  }

  // Return client object
  return client;


});