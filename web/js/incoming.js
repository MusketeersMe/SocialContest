$(document).ready(function() {

    setInterval(scCheck, 60000); // update ten times per minute
    function scCheck() {

        var lastUpdate = $('body').attr('data-updated'),
            incomingList = $('#incomingList');

        //lastUpdate = '1380313841'; // testing;
        jQuery.get(
            '/admin/latest-incoming',
            {since: lastUpdate},
            function(data, status) {

                // add new winner
                if (data.entry !== undefined) {
                    for (i = 0; i < data.entry.length; i++) {
                        insertEntry(incomingList, $(data.entry[i]));
                    }

                }

                // set updated date for next poll
                if (data.updated !== undefined) {
                    $('body').attr('data-updated', data.updated);
                }
            },
            'json'
        );

        function insertEntry($parent, $child) {
            var div = $('<div class="span4 well well-small entry-card"></div>');
            $parent.prepend(div);
            div.hide()
                .html($child.html())
                .attr('data-id', $child.attr('data-id'))
                .attr('data-status', $child.attr('data-status'))
                .attr('data-prev-status', $child.attr('data-prev-status'))
                .slideDown(800)
                .find('input.btn').click(function() {
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
        }
    }

    // make random winner button clickable
    $('#approve-all').click(function() {
        $('.entry-card').each(function(i) {
            var $this = $(this),
                newStatus = $this.attr('data-status'),
                prevStatus = $this.attr('data-prev-status'),
                entryId = $this.attr('data-id');
            $.post(
                "/admin/update-status",
                {status: newStatus, id: entryId, prev_status: prevStatus},
                function(data) {
                    if (1 == data.success) {
                        $this.fadeOut(400, function() {
                            $(this).remove();
                        });
                    }
                },
                'json'
            );
        });
    });

});