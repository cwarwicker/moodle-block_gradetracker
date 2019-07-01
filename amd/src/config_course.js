define(['jquery', 'jqueryui', 'block_gradetracker/freezetable'], function($, ui, freezeTable) {

  var config = {};

  config.init = function(){
    config.bindings();
  }

  config.bindings = function(){

    // Freeze user qual tables
    $('#gt_stud_quals, #gt_staff_quals').parent().freezeTable({
      columnNum: 2
    });

    // Freeze user unit tables
    $('.gt_stud_units').each( function(){

        var freeze = $(this).parent();
        var credits = $(this).attr('defaultCredits');
        var freezeCols = 2;
        if (credits > 0){
            freezeCols++;
        }

        $(freeze).freezeTable({
            columnNum: freezeCols
        });

    } );

    // Switch to a different course
    $('#gt_switch_course').unbind('change');
    $('#gt_switch_course').bind('change', function(){

      var section = $(this).attr('section');
      var id = $(this).val();

      return window.location = M.cfg.wwwroot + '/blocks/gradetracker/config.php?view=course&section='+section+'&id='+id;

    });

    // User unit toggling
    $('.gt_tick_all').unbind('click');
    $('.gt_tick_all').bind('click', function(e){

        var type = $(this).attr('tickType');
        var qualID = $(this).attr('qualID');
        var unitID = $(this).attr('unitID');
        var userID = $(this).attr('userID');
        var role = $(this).attr('role');

        var tickClass = '';
        var useUnitSet = false;
        var unitSet = $('#gt_unit_set_'+qualID).val();

        // Tick all students and staff on a given Unit
        if (type === 'unit' && role === 'all'){
          tickClass = '.gt_user_unit_unit_'+qualID+'_'+unitID+', ';
          tickClass += '.gt_staff_unit_unit_'+qualID+'_'+unitID;
        }

        // Tick all users of a given role on a given unit
        else if (type === 'unit' && role !== undefined){
          tickClass = '.gt_'+role+'_unit_unit_'+qualID+'_'+unitID;
        }

        // Tick all units of a given student
        else if (type === 'user' && role !== undefined && userID !== undefined){
          tickClass = '.gt_'+role+'_unit_'+role+'_'+qualID+'_'+userID;
          useUnitSet = true;
        }

        // Tick all users of a given role onto all units of a qual
        else if (type === 'user' && role !== undefined){
          tickClass = '.gt_'+role+'_unit_unit_'+qualID;
          useUnitSet = true;
        }


        // Are we using the unit set instead?
        if (useUnitSet && unitSet.length > 0){

          // Untick all checkboxes in this class and apply from unit set instead
          $(tickClass).prop('checked', false);

          // Loop through class
          $(tickClass).each( function(){

            // If the unit ID is in the selected set, tick it
            var uID = $(this).attr('uID');
            if (unitSet.indexOf(uID) >= 0){
                $(this).prop('checked', true);
            }

          } );

        } else {

          // Get the first value of this checkbox class to use as the master. Then invert them all.
          var chk = $(tickClass).prop('checked');
          $(tickClass).prop('checked', !chk);

        }

        config.updateCredits(qualID);

        e.preventDefault();

    });

    // Tick individual checkbox
    $('.gt_user_unit_checkbox').unbind('change');
    $('.gt_user_unit_checkbox').bind('change', function(){

        var qID = $(this).attr('qID');
        var sID = $(this).attr('sID');

        config.updateCredits(qID, sID);

    });

    // Show/Hide sections in Activity page
    $('.gt_show_activity_section').unbind('click');
    $('.gt_show_activity_section').bind('click', function(e){

      var show = $(this).attr('section');
      var hide = $(this).attr('hide');

      // Hide all sections
      $(this).parents('ul').find('a').removeClass('selected');
      $(hide).hide();

      // Then show this specific one
      $(this).addClass('selected');
      $(show).show();

      e.preventDefault();

    });


    $('.gt_activities_overview_criterion').unbind('click');
    $('.gt_activities_overview_criterion').bind('click', function(){

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

        GT.ajax(M.cfg.wwwroot + '/blocks/gradetracker/ajax/get.php', {action: 'get_criterion_activities_overview', params: params}, function(data){

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


    // General bindings
    GT.bind();

  }

  config.updateCredits = function(qID, sID){

    $('#gt_stud_units_'+qID+' tr.user_qual_row').each(function(){

      var qualID = qID;
      var studentID = $(this).attr('sID');

      // If ew didn't specify a student, continue. Or if we did and it matches this row, continue.
      if (sID === undefined || sID == studentID){

        var credits = 0;

        // Loop through unit checkboxes for this student and qual
        $(this).find('.gt_user_unit_checkbox').each( function(){

          if ( $(this).prop('checked') === true ){
            credits += parseInt( $(this).attr('credits') );
          }

        } );

        $('.usr_credits_'+studentID+'_'+qualID).text(credits);

        var max = parseInt( $('#gt_stud_units_'+qualID).attr('defaultCredits') );
        var cl = (credits > max) ? 'gt_incorrect_credits' : '';

        $('.usr_credits_'+studentID+'_'+qualID).parent().removeClass('gt_incorrect_credits');
        $('.usr_credits_'+studentID+'_'+qualID).parent().addClass(cl);

      }

    });


  }





  var client = {};

  //-- Log something to console
  client.log = function(log){
      console.log('[GT] ' + new Date().toTimeString().split(' ')[0] + ': ' + log );
  }

  //-- Initialise the scripts
  client.init = function() {

    // Bindings
    config.init();

    client.log('Loaded config_course.js');

  }

  // Return client object
  return client;


});