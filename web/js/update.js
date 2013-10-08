setInterval(scCheck, 6000); // update ten times per minute
setInterval(nextWinner, 60000); // update once per minute
function scCheck() {

    var lastUpdate = $('body').attr('data-updated'),
        $winnersTL = $('.winners ul.cbp_tmtimeline'),
        $entriesTL = $('.entries ul.cbp_tmtimeline');

    //lastUpdate = '1380313841'; // testing;
    jQuery.get(
        '/updates',
        {since: lastUpdate},
        function(data, status) {

            // add new winner
            if (data.winner !== undefined) {
                insertEntry($winnersTL, $(data.winner));
            }

            // update time
            if (data.next_winner !== undefined) {
                $('.next-winner').text(data.next_winner);
            }

            // add new entries
            if (data.entry !== undefined) {
                for (i = 0; i < data.entry.length; i++) {
                    insertEntry($entriesTL, $(data.entry[i]));
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
        var $li = $('<li></li>');
        $parent.prepend($li);
        $li.hide()
           .html($child.html())
           .slideDown(800);
    }
}
function nextWinner() {
    var enterInstructions = $('#enter-instructions');
    $.get('/next-winner',
        {},
        function(data) {
            if (data.next_winner) {
                enterInstructions.html(data.next_winner);
            }
        },
        'json'
    );
}