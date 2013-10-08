$(document).ready(function() {
    // make entry buttons clickable
    $('.entry-card').find('input.btn').click(function() {
        // get the target state and id from the button
        var $this = $(this),
            newStatus = $this.attr('data-status'),
            prevStatus = $this.attr('data-prev-status'),
            entryId = $this.attr('data-id'),
            $entry = $this.parents('.entry-card');

        $.post(
            "/admin/update-status",
            {status: newStatus, id: entryId, prev_status: prevStatus},
            function(data) {
                if (1 == data.success) {
                    $entry.fadeOut(400, function() {
                        $(this).remove();
                    });
                }
            },
            'json'
        );
    });

    // make random winner button clickable
    $('#random-winner').click(function() {
        var typeOfPick = $(this).attr('data-type');
        $.post(
            "/admin/pick-random-winner",
            {type: typeOfPick},
            function(result) {
                if (1 == result['success']) {
                    window.location.href = "/admin/winners";
                } else {
                    alert(result['message']);
                }
            },
            'json'
        );
    });
});