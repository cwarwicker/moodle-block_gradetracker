define(['jquery', 'jqueryui', 'block_gradetracker/bcpopup'], function($, ui, bcPopUp) {

    var config_structures_quals = {};

    config_structures_quals.ruleOperator = '.';
    config_structures_quals.ruleEvents = [];
    config_structures_quals.ruleComparisons = [];

    config_structures_quals.numCustomFormFields = 0;

    config_structures_quals.numRuleSets = 0;
    config_structures_quals.numRules = [];
    config_structures_quals.numRuleSteps = [];


    config_structures_quals.init = function(){

      // Count rule set rows
      config_structures_quals.numRuleSets = $('.gt_rule_set_row').length;

      // Count the number of steps in each rule
      var ruleSet = 1;

      $('.gt_rule_set_row').each( function(){

          // Set initial array
          config_structures_quals.numRuleSteps[ruleSet] = [];

          // Count rules in this set
          config_structures_quals.numRules[ruleSet] = $('.gt_rule_row_'+ruleSet).length;

          // Count the steps per rule
          var ruleNum = 1;

          $('.gt_rule_row_'+ruleSet).each( function(){

            config_structures_quals.numRuleSteps[ruleSet][ruleNum] = $('.gt_rule_step_'+ruleSet+'_'+ruleNum).length;

            // Show/Hide the relevant steps
            var stepNum = 1;

            $('.gt_rule_step_'+ruleSet+'_'+ruleNum).each(function(){

              $(this).removeClass('gt_hidden').addClass('gt_rule_step_'+ruleSet+'_'+ruleNum);
              $(this).find('div.gt_rule_step_condition').removeClass('gt_hidden').addClass('gt_rule_step_condition_'+ruleSet+'_'+ruleNum+'_'+stepNum);
              $(this).find('div.gt_rule_step_action').removeClass('gt_hidden').addClass('gt_rule_step_action_'+ruleSet+'_'+ruleNum+'_'+stepNum);

              stepNum++;

            });

            ruleNum++;

          } );

          ruleSet++;

      } );

      // Bind the elements
      config_structures_quals.bindings();

    }






    // Bind all the elements
    config_structures_quals.bindings = function(){

        // Count the number of custom form fields
        config_structures_quals.numCustomFormFields = $('.gt_custom_form_field_row').length;

        // Count the number of rule sets
        config_structures_quals.numRuleSets = $('.gt_rule_set_row').length;




        // Get rules :TODO




        // Level & feature click
        $('.gt_level_box, .gt_feature_box').unbind('click');
        $('.gt_level_box, .gt_feature_box').bind('click', function(){

            var box = $(this).children('input[type="checkbox"]');
            box.prop('checked', !box.prop('checked'));
            if (box.prop('checked') === true)
            {
                $(this).addClass('ticked');
                $(this).children('img.gt_ticked').css('display', 'inline-block');
            }
            else
            {
                $(this).removeClass('ticked');
                $(this).children('img.gt_ticked').css('display', 'none');
            }

        });

        // Stop clicks into the input calling the parent click
        $('.gt_level_box input').click( function(e){
            e.stopPropagation();
        } );


        // Add new form field
        $('#gt_add_structure_form_field').unbind('click');
        $('#gt_add_structure_form_field').bind('click', function(e){

            // This is defined in new.html as a count of the elements currently loaded into the structure
            config_structures_quals.numCustomFormFields++;

            var row = "";
            row += "<tr id='gt_custom_form_field_row_"+config_structures_quals.numCustomFormFields+"' class='gt_custom_form_field_row'>";
                row += "<td><input type='text' name='custom_form_fields_names["+config_structures_quals.numCustomFormFields+"]' /></td>";
                row += "<td><select name='custom_form_fields_forms["+config_structures_quals.numCustomFormFields+"]'><option></option><option value='qualification'>"+M.util.get_string('qualification', 'block_gradetracker')+"</option><option value='unit'>"+M.util.get_string('unit', 'block_gradetracker')+"</option></select></td>";
                row += "<td><select class='gt_toggle_structure_form_field_type' num='"+config_structures_quals.numCustomFormFields+"' name='custom_form_fields_types["+config_structures_quals.numCustomFormFields+"]'><option></option><option value='TEXT'>"+M.util.get_string('element:text', 'block_gradetracker')+"</option><option value='NUMBER'>"+M.util.get_string('element:number', 'block_gradetracker')+"</option><option value='TEXTBOX'>"+M.util.get_string('element:textbox', 'block_gradetracker')+"</option><option value='SELECT'>"+M.util.get_string('element:select', 'block_gradetracker')+"</option><option value='CHECKBOX'>"+M.util.get_string('element:checkbox', 'block_gradetracker')+"</option></select></td>";
                row += "<td><input type='text' style='display:none;' id='custom_form_fields_options_"+config_structures_quals.numCustomFormFields+"' name='custom_form_fields_options["+config_structures_quals.numCustomFormFields+"]' placeholder='option1,option2,option3' /></td>";
                row += "<td><input type='checkbox' name='custom_form_fields_req["+config_structures_quals.numCustomFormFields+"]' value='1' /></td>";
                row += "<td><a href='#' class='gt_remove' remove='#gt_custom_form_field_row_"+config_structures_quals.numCustomFormFields+"'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/remove.png' alt='remove' /></a></td>";
            row += "</tr>";

            $('#gt_custom_form_fields').append(row);

            config_structures_quals.bindings();

            e.preventDefault();

        });

        // Toggle form field type
        $('.gt_toggle_structure_form_field_type').unbind('click');
        $('.gt_toggle_structure_form_field_type').bind('click', function(){

          var type = $(this).val();
          var num = $(this).attr('num');

          if (type == 'SELECT')
          {
              $('#custom_form_fields_options_'+num).show();
          }
          else
          {
              $('#custom_form_fields_options_'+num).hide();
          }

        });


        // Add new rule set
        $('#gt_add_structure_rule_set').unbind('click');
        $('#gt_add_structure_rule_set').bind('click', function(e){

          config_structures_quals.numRuleSets++;
          var numRuleSets = config_structures_quals.numRuleSets;

          config_structures_quals.numRules[numRuleSets] = 0;
          config_structures_quals.numRuleSteps[numRuleSets] = [];

          var row = "";

          row += "<tr id='gt_rule_set_row_"+numRuleSets+"' class='gt_rule_set_row'>";

              row += "<td></td>";
              row += "<td><input type='text' id='rule_set_name_"+numRuleSets+"' name='rule_sets["+numRuleSets+"][name]' value='' /></td>";
              row += "<td><a href='#' class='gt_structure_open_rules' ruleSetNum='"+numRuleSets+"'>"+M.util.get_string('openrules', 'block_gradetracker')+" <img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/open.png' alt='open' /></a></td>";
              row += "<td><input type='radio' name='rule_set_default' value='"+numRuleSets+"' /></td>";
              row += "<td><input type='checkbox' name='rule_sets["+numRuleSets+"][enabled]' value='1' checked /></td>";
              row += "<td><a href='#' class='gt_remove_structure_rule_set' ruleSetNum='"+numRuleSets+"'><img src='"+M.cfg.wwwroot+"/blocks/gradetracker/pix/remove.png' alt='delete' /></a></td>";

          row += "</tr>";

          $('#gt_structure_rule_sets').append(row);

          // Rebind newly created elements
          config_structures_quals.bindings();

          e.preventDefault();

        });


        // Remove rule set
        $('.gt_remove_structure_rule_set').unbind('click');
        $('.gt_remove_structure_rule_set').bind('click', function(e){

          var ruleSetNum = $(this).attr('ruleSetNum');

          $('#gt_rule_set_row_'+ruleSetNum).remove();
          if ( $('#gt_popup_rules_'+ruleSetNum).length > 0 ){
              $('#gt_popup_rules_'+ruleSetNum).bcPopUp('destroy');
          }

          e.preventDefault();

        });

        // Open rules popup
        $('.gt_structure_open_rules').unbind('click');
        $('.gt_structure_open_rules').bind('click', function(e){

          var num = $(this).attr('ruleSetNum');

          // Create div inside the form
          if ( $('#gt_popup_rules_' + num).length == 0 ){

              // Load template
              GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_rule_set_template', params: {ruleSetNum: num}}, function(data){

                $('form#gt_qual_structure_form').append(data);
                var content = $('#gt_popup_rules_'+num).html();
                var ruleSetName = GT.html( $('#rule_set_name_'+num).val() );
                config_structures_quals.openRulesPopup(num, ruleSetName, content);

                // Rebind newly created elements
                config_structures_quals.bindings();

              });

          } else {

              var content = $('#gt_popup_rules_'+num).html();
              var ruleSetName = GT.html( $('#rule_set_name_'+num).val() );
              config_structures_quals.openRulesPopup(num, ruleSetName, content);

              // Rebind newly created elements
              config_structures_quals.bindings();

          }

          e.preventDefault();

        });

        // Add new rule
        $('.gt_add_rule').unbind('click');
        $('.gt_add_rule').bind('click', function(e){

          var ruleSetNum = $(this).attr('ruleSetNum');

          config_structures_quals.numRules[ruleSetNum]++;
          var ruleNum = config_structures_quals.numRules[ruleSetNum];

          config_structures_quals.numRuleSteps[ruleSetNum][ruleNum] = 0;

          var params = {ruleSetNum: ruleSetNum, ruleNum: ruleNum};

          GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_rule_form', params: params}, function(data){

              // Add row to table
              var row = '<tr id="gt_rule_row_'+ruleSetNum+'_'+ruleNum+'" class="gt_rule_row_'+ruleSetNum+'"><td><a href="#" id="gt_rule_name_link_'+ruleSetNum+'_'+ruleNum+'" class="gt_edit_rule" ruleSetNum="'+ruleSetNum+'" ruleNum="'+ruleNum+'">'+M.util.get_string('newrule', 'block_gradetracker')+'</a></td><td><span id="gt_rule_event_span_'+ruleSetNum+'_'+ruleNum+'"></span></td><td><span id="gt_rule_steps_span_'+ruleSetNum+'_'+ruleNum+'">0</span></td><td><div class="gt_fancy_checkbox"><input id="chkbox_'+ruleSetNum+'_'+ruleNum+'" type="checkbox" name="rule_sets['+ruleSetNum+'][rules]['+ruleNum+'][enabled]" value="1" checked /><label for="chkbox_'+ruleSetNum+'_'+ruleNum+'"></label></div></td><td><a href="#" class="gt_remove" remove="#gt_rule_row_'+ruleSetNum+'_'+ruleNum+', #gt_rule_content_'+ruleSetNum+'_'+ruleNum+'\"><img src="'+M.cfg.wwwroot+'/blocks/gradetracker/pix/remove.png" alt="delete" /></a></td></tr>';
              $('#gt_popup_rules_table_'+ruleSetNum + ' table').append(row);

              // Add div
              $('#gt_popup_rules_divs_'+ruleSetNum).append(data);

              // Rebind newly created elements
              config_structures_quals.bindings();

              // Load the edit screen for the new rule
              $('#gt_rule_name_link_'+ruleSetNum+'_'+ruleNum).click();

          });

          e.preventDefault();

        });


        // Edit a rule
        $('.gt_edit_rule').unbind('click');
        $('.gt_edit_rule').bind('click', function(e){

          var ruleSetNum = $(this).attr('ruleSetNum');
          var ruleNum = $(this).attr('ruleNum');

          $('.gt_rule_content').hide();
          $('#gt_rule_content_'+ruleSetNum+'_'+ruleNum).fadeIn('slow');

          // Bindings
          $('input.gt_rule_name').off('keyup');
          $('input.gt_rule_name').on('keyup', function(){
              var val = $(this).val().trim();
              if (val == ''){
                  val = '-';
              }
              $('a#gt_rule_name_link_'+ruleSetNum+'_'+ruleNum).text( val );
          });

          $('input.gt_rule_event').off('change');
          $('input.gt_rule_event').on('change', function(){
              $('span#gt_rule_event_span_'+ruleSetNum+'_'+ruleNum).text( $(this).val() );
          });

          e.preventDefault();

        });


        // Add new step to a rule
        $('.gt_add_rule_step').unbind('click');
        $('.gt_add_rule_step').bind('click', function(e){

          var ruleSetNum = $(this).attr('ruleSetNum');
          var ruleNum = $(this).attr('ruleNum');

          // Only add steps if the execution event has been chosen
          if ($('.gt_rule_on_event_'+ruleSetNum+'_'+ruleNum).is(':checked') === false){
              var dialog = $('<div>'+M.util.get_string('errors:rule:oneventmissing', 'block_gradetracker')+'</div>').dialog({
                  autoOpen: false,
                  width: 400,
                  dialogClass: 'gt_jq_dialog',
                  buttons: [
                      {
                          text: "Ok",
                          click: function() {
                              $( this ).dialog( "close" );
                          }
                      }
                  ],
                  show: {
                      effect: "slideDown",
                      duration: 300
                    },
                    hide: {
                      effect: "slideUp",
                      duration: 300
                    }
              });

              dialog.data('uiDialog')._title = function(title){
                  title.html(this.options.title);
              };

              dialog.dialog('option', 'title', '<span class="ui-icon ui-icon-alert"></span>');
              dialog.dialog('open');

              return false;
          }

          config_structures_quals.numRuleSteps[ruleSetNum][ruleNum]++;
          var stepNum = config_structures_quals.numRuleSteps[ruleSetNum][ruleNum];

          var cnt = $(".gt_rule_step_"+ruleSetNum+"_"+ruleNum+":not(#gt_rule_step_template)").length;
          var dynamicStepNum = cnt + 1;

          var div = $('#gt_rule_step_template').clone();

          // Clone the step template and replace with correct variable values
          $(div).html( function(i, html){
              return html.replace(/\[RS\]/g, ruleSetNum).replace(/\[R\]/g, ruleNum).replace(/\[S\]/g, stepNum);
          });

          $(div).attr('id', 'gt_rule_step_'+ruleSetNum+'_'+ruleNum+'_'+stepNum);
          $(div).attr('stepNum', dynamicStepNum);
          $(div).addClass('gt_rule_step_'+ruleSetNum+'_'+ruleNum);
          $(div).removeClass('gt_hidden').removeClass('gt_rule_step_[RS]_[R]');
          $($(div).find('.gt_step_num')[0]).text(dynamicStepNum);

          $('#gt_rule_steps_'+ruleSetNum+'_'+ruleNum).append(div);

          // Rebind newly created elements
          config_structures_quals.bindings();

          // Update step numbers
          config_structures_quals.updateStepNumbers(ruleSetNum, ruleNum);

          e.preventDefault();

        });

        // Remove a rule step and re-order the numbers of the ones still there
        $('.gt_remove_rule_step').unbind('click');
        $('.gt_remove_rule_step').bind('click', function(e){

          var ruleSetNum = $(this).attr('ruleSetNum');
          var ruleNum = $(this).attr('ruleNum');
          var ruleStepNum = $(this).attr('ruleStepNum');

          $('#gt_rule_step_'+ruleSetNum+'_'+ruleNum+'_'+ruleStepNum).remove()

          config_structures_quals.updateStepNumbers(ruleSetNum, ruleNum);

          e.preventDefault();

        });


        // Add a new condition to a rule
        $('.gt_add_rule_step_condition').unbind('click');
        $('.gt_add_rule_step_condition').bind('click', function(e){

          var ruleSetNum = $(this).attr('ruleSetNum');
          var ruleNum = $(this).attr('ruleNum');
          var ruleStepNum = $(this).attr('ruleStepNum');

          var div = $('#gt_rule_step_condition_template').clone();

          // Clone the template condition and replace with correct variable values
          $(div).html( function(i, html){
              return html.replace(/\[RS\]/g, ruleSetNum).replace(/\[R\]/g, ruleNum).replace(/\[S\]/g, ruleStepNum);
          });

          $(div).attr('id', '');
          $(div).addClass('gt_rule_step_condition_'+ruleSetNum+'_'+ruleNum+'_'+ruleStepNum);
          $(div).removeClass('gt_hidden');

          $($(this).parents('div.gt_rule_step_content').find('div.gt_step_conditions')[0]).append(div);

          // Rebind newly created elements
          config_structures_quals.bindings();

          e.preventDefault();

        });


        // Add a new action to a rule
        $('.gt_add_rule_step_action').unbind('click');
        $('.gt_add_rule_step_action').bind('click', function(e){

          var ruleSetNum = $(this).attr('ruleSetNum');
          var ruleNum = $(this).attr('ruleNum');
          var ruleStepNum = $(this).attr('ruleStepNum');

          var div = $('#gt_rule_step_action_template').clone();

          // Clone the template condition and replace with correct variable values
          $(div).html( function(i, html){
              return html.replace(/\[RS\]/g, ruleSetNum).replace(/\[R\]/g, ruleNum).replace(/\[S\]/g, ruleStepNum);
          });

          $(div).attr('id', '');
          $(div).addClass('gt_rule_step_action_'+ruleSetNum+'_'+ruleNum+'_'+ruleStepNum);
          $(div).removeClass('gt_hidden');

          $($(this).parents('div.gt_rule_step_content').find('div.gt_step_actions')[0]).append(div);

          // Rebind newly created elements
          config_structures_quals.bindings();

          e.preventDefault();

        });

        $('.gt_remove_step_action, .gt_remove_rule_condition').unbind('click');
        $('.gt_remove_step_action, .gt_remove_rule_condition').bind('click', function(e){
          $(this).parent().remove();
          e.preventDefault();
        });


        // Change the condition dropdown
        $('.gt_rule_step_condition_comparison').unbind('change');
        $('.gt_rule_step_condition_comparison').bind('change', function(){

          var value = $(this).val();

          if (value === 'is_met' || value === 'is_not_met'){
            $(this).parent().siblings('.gt_rule_condition_value_compare').addClass('gt_disabled');
            $(this).parent().siblings('.gt_rule_condition_value_compare input').val('');
          } else {
            $(this).parent().siblings('.gt_rule_condition_value_compare').removeClass('gt_disabled');
          }

        });


        // Open the function screen
        $('.gt_open_rule_fx').unbind('click');
        $('.gt_open_rule_fx').bind('click', function(){

          var type = $(this).attr('type');
          var el = $(this);

          $(document).bcPopUp( {
              title: M.util.get_string('function', 'block_gradetracker'),
              urlType: 'ajax',
              url: M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php?action=get_rule_fx_panel',
              allowMultiple: true,
              overrideWidth: '75%',
              buttons: {
                  'Confirm': function(){

                      var result = '';

                      var txt = $('#gt_fx_text_input');
                      var fnc = $('#gt_fx_func');

                      // Text input
                      if (txt.length > 0 && txt.prop('disabled') !== true){
                          var txtVal = txt.val().trim();
                          result = '"'+txtVal+'"';
                      }

                      // Function input
                      else if (fnc.prop('disabled') !== true){
                          var txtVal = fnc.val().trim();
                          result = txtVal;
                      }

                      // Update step input
                      $($(el).siblings('input')[0]).val(result);

                      // Close popup
                      $('#gt_rule_fx').parents('.bc-modal').bcPopUp('close');

                  },
              },
              afterLoad: function(){

                  // If we are opening it for an Action, hide the text bit and enable the function
                  if (type == 'action'){
                      GT.toggle_disabled('.gt_fx_func', '#gt_fx_text_input');
                      $('#gt_fx_text_input').parent().remove();
                  }

                  // Get the value of the input and update the popup
                  var val = $($(el).siblings('input')[0]).val();

                  if (val.startsWith('"') || val.startsWith("'")){
                      // Remove first and last charatcer, which should be quotes
                      val = val.substr(1, val.length - 2);
                      $('#gt_fx_text_input').val(val);
                      GT.toggle_disabled('#gt_fx_text_input', '.gt_fx_func');
                  } else if (val != '') {
                      $('#gt_fx_func').val(val);
                      GT.toggle_disabled('.gt_fx_func', '#gt_fx_text_input');
                  }

                  // Rebind
                  config_structures_quals.bindings();

              }
          } );

        });


        // Add element to function section
        $('.gt_add_rule_fx_element').unbind('click');
        $('.gt_add_rule_fx_element').bind('click', function(){

          // If disabled, stop
          if ($(this).attr('disabled') == "disabled"){
              return;
          }

          var type = $(this).attr('elType');
          var fromType = $(this).attr('fromType');
          var fromVal = $(this).attr('fromVal');

          var params = {type: type, fromType: fromType, fromVal: fromVal};

          $('#gt_fx_loading').show();

          // Load content from AJAX call and insert into HTML
          GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_rule_fx_element_options', params: params}, function(data){

              var response = $.parseJSON(data);
              var option = '<span>';

              // if we are adding a filter, it is 3 inputs, not the standard select menu
              if (type === 'filter'){

                  // Conjunction
                  option += '<select class="gt_fx_func" gttype="filter" filter="conjunction">';
                  option += '<option value=""></option>';
                  $.each(response.conjunctions, function(k, v){
                      option +=' <option value="'+v+'">'+v+'</option>';
                  });
                  option += '</select>';

                  // Field
                  option += '<select class="gt_fx_func" gttype="filter" filter="field">';
                  option += '<option value=""></option>';
                  $.each(response.fields, function(k, v){
                      option +=' <option value="'+v+'">'+v+'</option>';
                  });
                  option += '</select>';

                  // Value
                  option += '<input type="text" placeholder="v1,v2,v3..." class="gt_fx_func" gttype="filter" filter="value" />';

              }

              else if (type === 'input'){

                  // Text input for the value
                  option += '<input type="text" placeholder="value" class="gt_fx_func" gttype="input" />';

              }

              else {

                  var cnt = $('.gt_rule_fx_element_option[type="'+type+'"]').length;
                  var id = type + '_' + cnt;
                  option += '<select id="'+id+'" class="gt_rule_fx_element_option gt_fx_func" gttype="'+type+'">';
                  option += '<option value=""></option>';

                  $.each(response, function(k, v){
                      if (v.name !== undefined){
                          option += '<option value="'+v.name+'" return="'+v.return+'" object="'+v.object+'" longName="'+v.longName+'">'+v.name+'</option>';
                      } else {
                          option += '<option value="'+v+'">'+v+'</option>';
                      }
                  });

                  option += '</select>';

              }

              option += '</span>';

              // Add div
              $('#gt_fx_options').append(option);

              // Remove element?
              $(this).remove();

              // If we clicked on a method link, remove any filter links
              if (type === 'method'){
                  $('#gt_rule_fx_link_filter').remove();
              }

              $('#gt_fx_loading').hide();

              // Rebind
              config_structures_quals.bindings();

          });

        });


        // Bind the on change event for the select menus
        $('.gt_rule_fx_element_option').off('change');
        $('.gt_rule_fx_element_option').on('change', function(){

            $('#gt_fx_loading').show();

            var id = $(this).attr('id');
            var t = $(this).attr('gttype');
            var val = $(this).val();
            var selectedOption = $(this).find(':selected');
            var returnType = $(selectedOption).attr('return');
            var returnValue = $(selectedOption).attr('object');
            var longName = $(selectedOption).attr('longName');

            $(this).parent().nextAll().each( function(){
                $('#gt_fx_links a[dependenton="'+$(this).attr('id')+'"]').remove();
                $(this).remove();
            } );

            var objStr = '';
            $('.gt_rule_fx_element_option').each( function(){
                objStr += $(this).attr('type') + '['+$(this).val()+'].';
            } );
            objStr = objStr.substring(0, objStr.length-1);

            if (val == ''){
                $('#gt_fx_links a[dependenton="'+id+'"]').remove();
                $('#gt_fx_loading').hide();
                return true;
            }

            // Post params
            var p = {type: t, value: val, returnType: returnType, returnValue: returnValue, objStr: objStr, longName: longName};

            GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_rule_fx_links', params: p}, function(data){

                var response = $.parseJSON(data);

                $('#gt_fx_links').html('');

                $.each(response, function(k, v){
                    var useName = (longName !== undefined) ? longName : val;
                    $('#gt_fx_links').append("<a href='#' id='gt_rule_fx_link_"+v+"' class='gt_fx_func gt_add_rule_fx_element' dependentOn='"+id+"' elType='"+v+"' fromType='"+t+"' fromVal='"+useName+"'>"+M.util.get_string('add'+v, 'block_gradetracker')+"</a>");
                });

                $('#gt_fx_loading').hide();

                // Rebind
                config_structures_quals.bindings();

            });

            // Update the function
            config_structures_quals.updateFX();

        });

        // Bind the change event of the dropdown
        $('select.gt_fx_func').on('change', function(){
          config_structures_quals.updateFX();
        });

        // Bind change events to update the function in the textarea
        $('input.gt_fx_func').off('keyup');
        $('input.gt_fx_func').on('keyup', function(){
          config_structures_quals.updateFX();
        });


        // Bind general elements from GT object
        GT.bind();


    }



    // Open the rules popup
    config_structures_quals.openRulesPopup = function(num, ruleSetName, content){

      $('#gt_popup_rules_' + num).bcPopUp( {
          title: M.util.get_string('rules', 'block_gradetracker') + ' - ' + ruleSetName,
          content: content,
          allowMultiple: true,
          overrideWidth: '90%',
          appendTo: 'form#gt_qual_structure_form'
      } );

    }

    // Update the step numbers of a rule
    config_structures_quals.updateStepNumbers = function(ruleSetNum, ruleNum){

      var num = 1;
      var steps = $(".gt_rule_step_"+ruleSetNum+"_"+ruleNum+":not(#gt_rule_step_template)");
      $(steps).each( function(){

          $(this).attr('id', 'gt_rule_step_'+ruleSetNum+'_'+ruleNum+'_'+num);
          $(this).attr('stepNum', num);
          $($(this).find('.gt_step_num')[0]).text(num);
          $(this).find(':input').each( function(){
              var newname = $(this).attr('name').replace(/\[steps\]\[(\d+)\]/, '[steps]['+num+']');
              $(this).attr('name', newname);
          } );

          num++;

      } );

      $('#gt_rule_steps_span_'+ruleSetNum+'_'+ruleNum).text(num - 1);

    }

    // Update function screen
    config_structures_quals.updateFX = function(){
      $('#gt_fx_func').val( config_structures_quals.convertElementsToFX() );
    }

    // Convert function elements to valid values
    config_structures_quals.convertElementsToFX = function(){

      var str = '';
      var filterStr = '';

      $('#gt_fx_options :input').each( function(k, v){

          var val = $(v).val();
          var type = $(v).attr('gttype');

          // Strip quotes
          val = val.replace(/['"]+/g, '');
          val = val.trim();

          if (val !== ''){

              if (type == 'object'){
                  str += val;
              } else if (type == 'method'){
                  str = str.replace(/\(x\)/g, '()');
                  str += '.' + val + '(x)';
              } else if (type == 'filter'){

                  var filter = $(v).attr('filter');
                  if (filter == 'conjunction'){
                      filterStr = '';
                      filterStr += "'"+val+"'";
                  } else if (filter == 'field'){
                      filterStr += ",'"+val+"'";
                  } else if (filter == 'value'){

                      var a = val.split(',');
                      a = a.map( function(x){ return "'"+x+"'"; } );
                      val = a.join(',');

                      filterStr += ","+val;
                      str = str.replace('\(x\)', '('+filterStr+')');

                  }

              } else if (type == 'input'){
                  // We might have an "(x)" from an earlier method, so we want to make sure we are replacing the last occurance of "(x)" in the string
                  var n = str.lastIndexOf("(x)");
                  str = str.slice(0, n) + str.slice(n).replace('\(x\)', "('"+val+"')");
              }

      }

      } );

      str = str.replace(/\(x\)/g, '()');

      return str;

    }




    var client = {};

    //-- Log something to console
    client.log = function(log){
        console.log('[GT] ' + new Date().toTimeString().split(' ')[0] + ': ' + log );
    }

    //-- Initialise the scripts
    client.init = function() {

      // Bindings
      config_structures_quals.init();

      client.log('Loaded config_structures_qual.js');

    }

    // Return client object
    return client;

});