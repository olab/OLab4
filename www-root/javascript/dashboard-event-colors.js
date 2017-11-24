function dashboard_event_color(calEvent, $event) {
    if (calEvent.color) {
        $event.css({'backgroundColor':calEvent.color});
        var colors = $event.css('backgroundColor').match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        if (colors) {
            var red = parseInt(colors[1]), green = parseInt(colors[2]), blue = parseInt(colors[3]);
            var yiq = (red * 299 + green * 587 + blue * 114) / 1000;
            $event.css({'color': (yiq >= 128 ? 'black' : 'white')});
            var darker_red = Math.round(red * 2 / 3);
            var darker_green = Math.round(green * 2 / 3);
            var darker_blue = Math.round(blue * 2 / 3);
            console.log('rgb(' + red + ', ' + green + ', ' + blue + ')');
            console.log('rgb(' + darker_red + ', ' + darker_green + ', ' + darker_blue + ')');
            $event.find('.wc-time').css('backgroundColor', 'rgb(' + darker_red + ', ' + darker_green + ', ' + darker_blue + ')');
        }
    }
}
