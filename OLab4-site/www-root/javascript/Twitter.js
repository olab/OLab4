jQuery(function($) {
    var select, chosen;

    // Cache the select element as we'll be using it a few times
    select = $("#twitter_hashtags");

    // Init the chosen plugin
    select.chosen({no_results_text: 'Press Enter to add new hashtag:'});

    // Get the chosen object
    chosen = select.data('chosen');

    // Bind the keyup event to the search box input
    $(".search-field").find('input').on('keyup',function(e) {
        // If we hit Enter and the results list is empty (no matches) add the option
        if (e.which == 13 && chosen.dropdown.find('li.no-results').length > 0) {
            selectedVal = this.value;
            if( selectedVal.charAt(0) != "#" ) {
                selectedVal = "#" + selectedVal;
            }

            var x = document.getElementById("twitter_hashtags");
            var txt = "All options: ";
            var i;
            for (i = 0; i < x.length; i++) {
                if (x.options[i].value == selectedVal )
                    return;
            }

            select.append('<option value="' + selectedVal + '" selected="selected">' + selectedVal + '</option>');
            // Trigger the update
            select.trigger("liszt:updated");
        }
    });
});
