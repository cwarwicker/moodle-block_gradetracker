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

    // Count range criteria
    config.cntRangedRangeCriteria = $('.gt_criterion_range_row').length;

    // Count ranged sub criteria
    config.cntRangedSubCriteria = $('.gt_criterion_range_criterion_row').length;

    // Count numeric observations (ranges), create array elements and push any child criteria onto it
    $('.gt_numeric_observation').each(function(i, v){

      var critNum = $(this).attr('critNum');

      config.cntNumericObservationCriteria++;

      // Create array for parent criterion
      if (config.arrayOfNumericObservationCriteria[critNum] === undefined){
        config.arrayOfNumericObservationCriteria[critNum] = [];
      }

      // Push observation num to array
      config.arrayOfNumericObservationCriteria[critNum].push( config.cntNumericObservationCriteria );

    });

    // COunt numeric sub criteria
    $('.gt_numeric_observation_criterion').each(function(i, v){

      var critNum = $(this).attr('critNum');

      config.cntNumericSubCriteria++;

      // Create array for parent criterion
      if (config.arrayOfNumericSubCriteria[critNum] === undefined){
        config.arrayOfNumericSubCriteria[critNum] = [];
      }

      // Push observation num to array
      config.arrayOfNumericSubCriteria[critNum].push( config.cntNumericSubCriteria );

    });


    // Supported criteria types
    $.each(data['supportedTypes'], function(index, el){
      config.supportedCritTypes.push( {id: el.id, name: el.name} );
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

    // Max Numeric Points setting
    config.maxNumericPoints = data['maxNumericPoints'];

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
    $('#gt_load_unit_defaults').bind('change', function(){

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


    // Change criterion type
    $('.gt_change_criterion_type').unbind('change');
    $('.gt_change_criterion_type').bind('change', function(){

      var critNum = $(this).attr('critNum');
      var type = $(this).val();

      var idEl = $('#gt_crit_id_input_'+critNum);
      var id = ( $(idEl).length > 0 ) ? $(idEl).val() : 0;

      var gEl = $('#gt_crit_grading_input_'+critNum);
      var gradingID = ( $(gEl).length > 0 ) ? $(gEl).val() : 0;

      var output = '';

      // Set options cell to blank
      $('#gt_criterion_options_cell_'+critNum).html('<img src="'+M.util.image_url('i/loading_small')+'" alt="loading" />');

      // Now load options
      var params = { critID: id, critType: type, num: critNum };

      GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_criterion_options', params: params}, function(data){

          data = $.parseJSON(data);
          $(data).each( function(){

              output += '<label><small>'+this.label+'</small></label><br>';
              output += this.element;
              output += '<br>';

          });

          $('#gt_criterion_options_cell_'+critNum).html(output);

      });

      // Now see if there is a subrow to bring in for this type

      // If it already exists, empty it, otherwise create it
      if ($('.gt_criterion_row_'+critNum+'.gt_unit_criteria_table_sub_row').length == 0){

          var subRow = '';
          subRow += '<tr class="gt_criterion_row_'+critNum+' gt_unit_criteria_table_sub_row">';
          subRow += '</tr>';
          $('.gt_criterion_row_'+critNum+'.gt_unit_criteria_table_row').after(subRow);

      }

      $('.gt_criterion_row_'+critNum+'.gt_unit_criteria_table_sub_row').html('<img src="'+M.util.image_url('i/loading_small')+'" alt="loading" />');


      // Now load the sub row
      var params = { critID: id, critType: type, num: critNum, gradingID: gradingID };

      GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_criterion_sub_row', params: params}, function(data){

          data = $.parseJSON(data);
          $('.gt_criterion_row_'+critNum+'.gt_unit_criteria_table_sub_row').html(data);
          config.bindings();

      });

    });


    // Add a range to a criterion
    $('.gt_add_range').unbind('click');
    $('.gt_add_range').bind('click', function(e){

      config.cntRangedRangeCriteria++;
      var num = config.cntRangedRangeCriteria;
      var pNum = $(this).attr('critNum');

      var output = '';

      output += '<tr id="gt_criterion_range_'+pNum+'_'+num+'" class="gt_criterion_range_row" rangeNum="'+num+'">';

          output += '<td class="gt_range_info_cell">';

              output += '<table>';

                  output += '<tr>';
                      output += '<td>'+M.util.get_string('name', 'block_gradetracker')+'</td>';
                      output += '<td><input type="text" name="unit_criteria['+pNum+'][ranges]['+num+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /></td>';
                  output += '</tr>';

                  output += '<tr>';
                      output += '<td>'+M.util.get_string('details', 'block_gradetracker')+'</td>';
                      output += '<td><textarea name="unit_criteria['+pNum+'][ranges]['+num+'][details]" placeholder="'+M.util.get_string('details', 'block_gradetracker')+'"></textarea></td>';
                  output += '</tr>';

                  output += '<tr>';
                      output += '<td>'+M.util.get_string('numobservations', 'block_gradetracker')+'</td>';
                      output += '<td><input type="number" name="unit_criteria['+pNum+'][ranges]['+num+'][numobservations]" value="" /></td>';
                  output += '</tr>';

                  output += '<tr>';
                      output += '<td>'+M.util.get_string('gradestructure', 'block_gradetracker')+'</td>';
                      output += '<td>';
                          output += '<select name="unit_criteria['+pNum+'][ranges]['+num+'][gradingstructure]">';
                              $.each(config.critGradingStructures, function(key, obj){
                                  output += '<option value="'+obj.id+'">'+obj.name+'</option>';
                              });
                          output += '</select>';
                      output += '</td>';
                  output += '</tr>';

                  output += '<tr>';
                      output += '<td>'+M.util.get_string('criteria', 'block_gradetracker')+'</td>';
                      output += '<td><a href="#" class="gt_add_range_criterion" critNum="'+pNum+'" rangeNum="'+num+'"><img src="'+M.util.image_url('t/add')+'" alt="add" class="gt_no_float" /></a></td>';
                  output += '</tr>';

                  output += '<tr>';
                      output += '<td colspan="2"><a href="#" class="gt_remove_range" critNum="'+pNum+'" rangeNum="'+num+'"><img src="'+M.util.image_url('t/delete')+'" alt="delete" /></a></td>';
                  output += '</tr>';

              output += '</table>';

          output += '</td>';

          output += '<td class="gt_range_criteria_cell">';

              output += '<table>';

                  output += '<tr>';
                      output += '<th>'+M.util.get_string('name', 'block_gradetracker')+'</th>';
                      output += '<th>'+M.util.get_string('details', 'block_gradetracker')+'</th>';
                      output += '<th>'+M.util.get_string('gradestructure', 'block_gradetracker')+'</th>';
                      output += '<th></th>';
                  output += '</tr>';

              output += '</table>';

          output += '</td>';

      output += '</tr>';

      $('#gt_criterion_sub_table_'+pNum).append(output);

      config.bindings();

      e.preventDefault();

    });

    // Remove a range
    $('.gt_remove_range').unbind('click');
    $('.gt_remove_range').bind('click', function(e){

      var critNum = $(this).attr('critNum');
      var rangeNum = $(this).attr('rangeNum');

      $('#gt_criterion_range_'+critNum+'_'+rangeNum).remove();
      e.preventDefault();

    });

    // Add a sub criterion to the range
    $('.gt_add_range_criterion').unbind('click');
    $('.gt_add_range_criterion').bind('click', function(e){

      config.cntRangedSubCriteria++;
      var num = config.cntRangedSubCriteria;
      var pNum = $(this).attr('critNum');
      var rNum = $(this).attr('rangeNum');

      var output = '';

      output += '<tr id="gt_range_criteria_row_'+pNum+'_'+rNum+'_'+num+'">';
          output += '<td>';
              output += '<input type="text" name="unit_criteria['+pNum+'][ranges]['+rNum+'][criteria]['+num+'][name]" />';
          output += '</td>';
          output += '<td>';
              output += '<textarea name="unit_criteria['+pNum+'][ranges]['+rNum+'][criteria]['+num+'][details]"></textarea>';
          output += '</td>';
          output += '<td>';
              output += '<select name="unit_criteria['+pNum+'][ranges]['+rNum+'][criteria]['+num+'][gradingstructure]">';
                  output += '<option value=""></option>';
                  $.each(config.critGradingStructures, function(key, obj){
                      var sel = (config.critGradingStructures.length === 1) ? 'selected' : '';
                      output += '<option value="'+obj.id+'" '+sel+' >'+obj.name+'</option>';
                  });
              output += '</select>';
          output += '</td>';
          output += '<td>';
              output += '<a href="#" class="gt_remove_range_criterion" critNum="'+pNum+'" rangeNum="'+rNum+'" subCritNum="'+num+'"><img src="'+M.util.image_url('t/delete')+'" alt="delete" /></a>';
          output += '</td>';

      output += '</tr>';

      $('#gt_criterion_range_'+pNum+'_'+rNum+' td.gt_range_criteria_cell table').append(output);

      config.bindings();

      e.preventDefault();

    });

    // Remove sub criterion from the range
    $('.gt_remove_range_criterion').unbind('click');
    $('.gt_remove_range_criterion').bind('click', function(e){

      var critNum = $(this).attr('critNum');
      var rangeNum = $(this).attr('rangeNum');
      var subCritNum = $(this).attr('subCritNum');

      $('#gt_range_criteria_row_'+critNum+'_'+rangeNum+'_'+subCritNum).remove();
      e.preventDefault();

    });


    // Add a numeric observation (range)
    $('.gt_add_numeric_observation').unbind('click');
    $('.gt_add_numeric_observation').bind('click', function(e){

      var pNum = $(this).attr('critNum');

      config.cntNumericObservationCriteria++;

       // Create an array of observations for this pid (parent num)
      if (config.arrayOfNumericObservationCriteria[pNum] == undefined)
      {
          config.arrayOfNumericObservationCriteria[pNum] = new Array();
      }

      // Set the dyamic number into an easier to use variable
      var num = config.cntNumericObservationCriteria;

      // Add this new number to the array
      config.arrayOfNumericObservationCriteria[pNum].push(num);

      // Add the name input for the observation
      var output = '';

      output += '<td class="gt_criterion_observation_'+pNum+'_'+num+' gt_range_cell" obNum="'+num+'">';
          output += '<a href="#" class="gt_remove_numeric_observation" critNum="'+pNum+'" obNum="'+num+'"><img src="'+M.util.image_url('t/delete')+'" /></a>';
          output += '<input type="text" name="unit_criteria['+pNum+'][observation]['+num+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> &nbsp;&nbsp; ';
      output += '</td>';

      $('#gt_sub_observation_row_'+pNum).append(output);


      // Hide the conversion chart and points scores for just the criteria
      $('#gt_criterion_sub_table_'+pNum+' td.gt_sub_criteria_chart_cell').html('');
      $('#gt_criterion_sub_table_'+pNum+' td.gt_sub_criterion_points_cell').html('');


      // Add the conversion chart for the observation
      var output = '';

      output += '<td class="gt_criterion_observation_'+pNum+'_'+num+' gt_observation_chart_cell gt_observation_chart_cell_'+num+'">';
      output += '</td>';

      $('#gt_sub_criteria_row_'+pNum).append(output);

      config.loadObservationConversionChart(pNum, num);


      // There are observations, so let's add a table cell for the points drop down for each of them
      // Add table cell to each row
      var rows = $('.gt_criterion_sub_criterion_row');
      $.each(rows, function(){

          var rowNum = $(this).attr('dNum');

          // Check if cell exists already
          if ( $('.gt_criterion_sub_criterion_row td.gt_criterion_observation_'+pNum+'_'+num+'.gt_criterion_sub_criterion_'+pNum+'_'+rowNum).length == 0 )
          {

              var cell = '';
              cell += '<td class="gt_observation_points_cell gt_criterion_observation_'+pNum+'_'+num+' gt_criterion_sub_criterion_'+pNum+'_'+rowNum+'" cNum="'+rowNum+'" obNum="'+num+'">';
              cell += '</td>';

              $('#gt_criterion_sub_criterion_'+pNum+'_'+rowNum).append(cell);

          }

          config.refreshObservationSelectMenus(pNum);

      });

      config.bindings();
      e.preventDefault();

    });

    // Remove a numeric observation (range)
    $('.gt_remove_numeric_observation').unbind('click');
    $('.gt_remove_numeric_observation').bind('click', function(e){

      var pNum = $(this).attr('critNum');
      var num = $(this).attr('obNum');

      // Remove from screen
      $('.gt_criterion_observation_'+pNum+'_'+num).remove();

      // Remove from array
      config.arrayOfNumericObservationCriteria[pNum] = $.grep(config.arrayOfNumericObservationCriteria[pNum], function(val, ind){
          return (val < num || val > num);
      });

      // Refresh select menus for criteria if no observations left
      if ( $('#gt_sub_observation_row_'+pNum+' td.gt_range_cell').length == 0 )
      {
          config.refreshCriteriaSelectMenus(pNum);
          config.loadCriteriaConversionChart(pNum);
      }

      // If no criteria left, remove the criteria conversion chart from the relevant cell
      if ( $('table#gt_criterion_sub_table_'+pNum+' tr.gt_criterion_sub_criterion_row').length == 0 )
      {
          $('table#gt_criterion_sub_table_'+pNum+' td.gt_sub_criteria_chart_cell').html('');
      }

      e.preventDefault();

    });

    // Add a sub criterion to a numeric observation (range)
    $('.gt_add_numeric_sub_criterion').unbind('click');
    $('.gt_add_numeric_sub_criterion').bind('click', function(e){

      var pNum = $(this).attr('critNum');

      config.cntNumericSubCriteria++;

      // Create an array of sub criteria for this pid (parent num), so we can work out the incrementing numbers for them
      if (config.arrayOfNumericSubCriteria[pNum] == undefined)
      {
          config.arrayOfNumericSubCriteria[pNum] = new Array();
      }

      // Set the dyamic number into an easier to use variable
      var num = config.cntNumericSubCriteria;

      // Add this new number to the array
      config.arrayOfNumericSubCriteria[pNum].push(num);

      var output = '';

      output += '<tr id="gt_criterion_sub_criterion_'+pNum+'_'+num+'" class="gt_criterion_sub_criterion_row" dNum="'+num+'">';

          output += '<td class="gt_sub_criterion_name_cell">';
              output += '<a href="#" class="gt_remove_numeric_sub_criterion" critNum="'+pNum+'" subCritNum="'+num+'"><img src="'+M.util.image_url('t/delete')+'" /></a>';
              output += '<input type="text" name="unit_criteria['+pNum+'][subcriteria]['+num+'][name]" value="" placeholder="'+M.util.get_string('name', 'block_gradetracker')+'" /> &nbsp;&nbsp; ';
          output += '</td>';

          output += '<td class="gt_sub_criterion_points_cell">';
          output += '</td>';

      output += '</tr>';

      $('#gt_criterion_sub_table_'+pNum).append(output);


      // If there are no observations defined, show the conversion chart for just the criteria
      if ( $('#gt_sub_observation_row_'+pNum+' td.gt_range_cell').length == 0 )
      {

          config.refreshCriteriaSelectMenus(pNum);

          // If there is nothing in there already, do the conversion chart
          if ( $('#gt_criterion_sub_table_'+pNum+' td.gt_sub_criteria_chart_cell:empty').length > 0 )
          {
              config.loadCriteriaConversionChart(pNum);
          }

      }
      else
      {

          // There are observations, so let's add a table cell for the points drop down for each of them
          var obs = $('#gt_sub_observation_row_'+pNum+' td.gt_range_cell');
          $.each(obs, function(){

              var obNum = $(this).attr('obNum');

              // Add table cell to each row
              var rows = $('.gt_criterion_sub_criterion_row');
              $.each(rows, function(){

                  var rowNum = $(this).attr('dNum');

                  // Check if cell exists already
                  if ( $('.gt_criterion_sub_criterion_row td.gt_criterion_observation_'+pNum+'_'+obNum+'.gt_criterion_sub_criterion_'+pNum+'_'+rowNum).length == 0 )
                  {

                      var cell = '';
                      cell += '<td class="gt_observation_points_cell gt_criterion_observation_'+pNum+'_'+obNum+' gt_criterion_sub_criterion_'+pNum+'_'+rowNum+'" cNum="'+rowNum+'" obNum="'+obNum+'">';
                      cell += '</td>';

                      $('#gt_criterion_sub_criterion_'+pNum+'_'+rowNum).append(cell);

                  }

                  config.refreshObservationSelectMenus(pNum);

              });

          });

      }

      config.bindings();

      e.preventDefault();

    });

    // Remove a sub criterion from a numeric observation (range)
    $('.gt_remove_numeric_sub_criterion').unbind('click');
    $('.gt_remove_numeric_sub_criterion').bind('click', function(e){

      var pNum = $(this).attr('critNum');
      var num = $(this).attr('subCritNum');

      // Remove from screen
      $('#gt_criterion_sub_criterion_'+pNum+'_'+num).remove();

      // Remove from array
      config.arrayOfNumericSubCriteria[pNum] = $.grep(config.arrayOfNumericSubCriteria[pNum], function(val, ind){
          return (val < num || val > num);
      });

      // If no criteria left, remove the criteria conversion chart from the relevant cell
      if ( $('table#gt_criterion_sub_table_'+pNum+' tr.gt_criterion_sub_criterion_row').length == 0 )
      {
          $('table#gt_criterion_sub_table_'+pNum+' td.gt_sub_criteria_chart_cell').html('');
      }

      e.preventDefault();

    });


    // Change criterion grading structure
    $('.gt_change_criterion_grading_structure').unbind('change');
    $('.gt_change_criterion_grading_structure').bind('change', function(){

      var pNum = $(this).attr('critNum');

      // If any conversion charts loaded, reload them with new grading structure
      if ($('.gt_criterion_row_'+pNum+'.gt_unit_criteria_table_sub_row table.gt_criteria_conversion_chart').length > 0)
      {

          // If observations, reload those
          if ( $('#gt_sub_observation_row_'+pNum+' td.gt_range_cell').length > 0 )
          {

              var obs = $('#gt_sub_observation_row_'+pNum+' td.gt_range_cell');
              $.each(obs, function(){

                  var num = $(this).attr('obNum');
                  config.loadObservationConversionChart(pNum, num);

              });

          }

          // Else must be the criteria one
          else
          {
              config.loadCriteriaConversionChart(pNum);
          }

      }

    });


    // General bindings
    GT.bind();

  }


  // Add a new criterion
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
                $.each(config.supportedCritTypes, function(i, el){
                    output += "<option value='"+el.id+"'>"+el.name+"</option>";
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
            output += "<select name='unit_criteria["+cntCrit+"][grading]' id='gt_crit_grading_input_"+cntCrit+"' class='gt_change_criterion_grading_structure' critNum='"+cntCrit+"'>";

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

  // Remove a criterion
  config.removeCriterion = function(critNum){

    $('.gt_criterion_row_'+critNum).remove();
    config.refreshParentCriteriaLists();

  }

  // Load the conversion chart for the observation
  config.loadObservationConversionChart = function(pNum, num){

    $('#gt_criterion_sub_table_'+pNum+' td.gt_observation_chart_cell_'+num).html('<img src="'+M.util.image_url('i/loading_small')+'" alt="loading" />');
    config.loadConversionChart(pNum, '[charts]['+num+']', $('#gt_criterion_sub_table_'+pNum+' td.gt_observation_chart_cell_'+num));

  }

  // Load the conversion chart for just the criteria (no ranges have been defined)
  config.loadCriteriaConversionChart = function(pNum){

    $('#gt_criterion_sub_table_'+pNum+' td.gt_sub_criteria_chart_cell').html('<img src="'+M.util.image_url('i/loading_small')+'" alt="loading" />');
    config.loadConversionChart(pNum, '[chart]', $('#gt_criterion_sub_table_'+pNum+' td.gt_sub_criteria_chart_cell'));

  }

  // Load the conversion chart somewhere
  config.loadConversionChart = function(pNum, inputName, obj){

    var gradingID = $('select[name="unit_criteria['+pNum+'][grading]"]').val();
    var params = { gradingStructureID: gradingID };

    var output = '';

    GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_met_values', params: params}, function(data){

        var length = 1;

        if (data){
            data = $.parseJSON(data);
            length = data.length;
        }

        output += '<table class="gt_criteria_conversion_chart">';

            output += '<tr>';
                output += '<th colspan="'+length+'">'+M.util.get_string('conversionchart', 'block_gradetracker')+'</th>';
            output += '</tr>';

            output += '<tr>';
                if (data){
                    $.each(data, function(){
                        output += '<th title="'+this.fullname+'">'+this.name+'</th>';
                    });
                }
            output += '</tr>';

            output += '<tr>';
                if (data){
                    $.each(data, function(){
                        output += '<td><input type="text" name="unit_criteria['+pNum+']'+inputName+'['+this.id+']" value="" /></td>';
                    });
                }
            output += '</tr>';

        output += '</table>';

        obj.html(output);

    });

    return output;

  }

  //  Refresh the select menus for criteria
  config.refreshObservationSelectMenus = function(pNum){

    // Loop through other sub criteria and if they are empty put in the select menu
    var otherCells = $('#gt_criterion_sub_table_'+pNum+' td.gt_observation_points_cell:empty');
    $.each(otherCells, function(){

        var rowNum = $(this).parent().attr('dNum');
        var obNum = $(this).attr('obNum');
        var output = '';
        output += '<select name="unit_criteria['+pNum+'][points]['+rowNum+'|'+obNum+']">';
            for (var i = 0; i <= config.maxNumericPoints; i++)
            {
                output += '<option value="'+i+'">'+i+'</option>';
            }
        output += '</select>';

        $(this).html(output);

    });

  }

  // Refresh the select menus for criteria
  config.refreshCriteriaSelectMenus = function(pNum){

    // Loop through other sub criteria and if they are empty put in the select menu
    var otherCells = $('#gt_criterion_sub_table_'+pNum+' td.gt_sub_criterion_points_cell:empty');
    $.each(otherCells, function(){

        var num = $(this).parent().attr('dNum');
        var output = '';
        output += '<select name="unit_criteria['+pNum+'][subcriteria]['+num+'][points]">';
            for (var i = 0; i <= config.maxNumericPoints; i++)
            {
                output += '<option value="'+i+'">'+i+'</option>';
            }
        output += '</select>';

        $(this).html(output);

    });

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

  window.config_units = config;

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