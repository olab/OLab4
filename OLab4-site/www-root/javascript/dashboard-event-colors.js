function dashboard_event_color(calEventColor) {
    var dashboard_event_colors = {background: '#68a1e5', text:'white'};
    if (calEventColor) {
        dashboard_event_colors.background = calEventColor;
        var colors = (calEventColor).match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        if (!colors) {
            colors = (hexToRgbColor(calEventColor)).match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        }
        if (colors) {
            var red = parseInt(colors[1]), green = parseInt(colors[2]), blue = parseInt(colors[3]);
            var yiq = (red * 299 + green * 587 + blue * 114) / 1000;
            dashboard_event_colors.text = (yiq >= 128 ? 'black' : 'white');
            dashboard_event_colors.background = 'rgb(' + red + ', ' + green + ', ' + blue + ')';
        }
    }
    return dashboard_event_colors;
}

function hexToRgbColor(hex) {

    // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
    var shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
    hex = hex.replace(shorthandRegex, function(m, r, g, b) {
        return r + r + g + g + b + b;
    });

    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);

    return result ? ( 'rgb(' + parseInt(result[1], 16) + ',' + parseInt(result[2], 16) + ',' + parseInt(result[3], 16) + ')') : hex;
}
