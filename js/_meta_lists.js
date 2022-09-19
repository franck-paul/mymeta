/*global $, dotclear */
'use strict';

$(() => {
    $('#mymeta-list').sortable({
        cursor: 'move',
        stop(event, ui) {
            $('#mymeta-list tr td input.position').each(function (i) {
                $(this).val(i + 1);
            });
        },
    });
    $('#mymeta-list tr').hover(
        function () {
            $(this).css({ cursor: 'move' });
        },
        function () {
            $(this).css({ cursor: 'auto' });
        }
    );
    $('#mymeta-list tr td input.position').hide();
    $('#mymeta-list tr td.handle').addClass('handler');

    $('.checkboxes-helpers').each(function () {
        dotclear.checkboxesHelpers(this);
    });

    dotclear.postsActionsHelper();
});
