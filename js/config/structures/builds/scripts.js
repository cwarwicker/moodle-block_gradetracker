function structures_builds_bindings(){

    $(document).ready( function(){

        $('#gt_add_build_award').unbind('click');
        $('#gt_add_build_award').bind('click', function(e){

            cntAwards++;

            var row = '';
            row += '<tr id="gt_build_award_row_'+cntAwards+'">';

                row += '<td><input type="hidden" name="build_award_id['+cntAwards+']" value="0" /><input type="number" step="any" name="build_award_rank['+cntAwards+']" value="" /></td>';
                row += '<td><input type="text" name="build_award_name['+cntAwards+']" value="" /></td>';
                row += '<td><input type="number" step="any" min="0" name="build_award_points_lower['+cntAwards+']" value="" /></td>';
                row += '<td><input type="number" step="any" min="0" name="build_award_points_upper['+cntAwards+']" value="" /></td>';
                row += '<td><input type="number" step="any" min="0" name="build_award_qoe_lower['+cntAwards+']" value="" /></td>';
                row += '<td><input type="number" step="any" min="0" name="build_award_qoe_upper['+cntAwards+']" value="" /></td>';
                row += '<td><input type="number" step="any" min="0" name="build_award_ucas['+cntAwards+']" value="" /></td>';
                row += '<td><a href="#" onclick="$(\'#gt_build_award_row_'+cntAwards+'\').remove();return false;"><img src="'+M.util.image_url('t/delete')+'" alt="delete" /></a></td>';

            row += '</tr>';      

            $('#gt_build_award_table').append(row);
            e.preventDefault();

        });

    } );





}