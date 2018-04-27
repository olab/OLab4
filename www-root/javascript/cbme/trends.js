var ENTRADA_URL;
var query_limit;
var trends_query_limit;
var hoverTracker = false;
var myChart5;

function wordwrap (str, intWidth, strBreak, cut) {
    intWidth = arguments.length >= 2 ? +intWidth : 75
    strBreak = arguments.length >= 3 ? '' + strBreak : '\n'
    cut = arguments.length >= 4 ? !!cut : false
    var i, j, line
    str += ''
    if (intWidth < 1) {
        return str
    }
    var reLineBreaks = /\r\n|\n|\r/
    var reBeginningUntilFirstWhitespace = /^\S*/
    var reLastCharsWithOptionalTrailingWhitespace = /\S*(\s)?$/
    var lines = str.split(reLineBreaks)
    var l = lines.length
    var match
    var result = Array();
    // for each line of text
    for (i = 0; i < l; lines[i++] += line) {
        line = lines[i]
        lines[i] = ''
        while (line.length > intWidth) {
            // get slice of length one char above limit
            var slice = line.slice(0, intWidth + 1)
            // remove leading whitespace from rest of line to parse
            var ltrim = 0
            // remove trailing whitespace from new line content
            var rtrim = 0
            match = slice.match(reLastCharsWithOptionalTrailingWhitespace)
            // if the slice ends with whitespace
            if (match[1]) {
                // then perfect moment to cut the line
                j = intWidth
                ltrim = 1
            } else {
                // otherwise cut at previous whitespace
                j = slice.length - match[0].length
                if (j) {
                    rtrim = 1
                }
                // but if there is no previous whitespace
                // and cut is forced
                // cut just at the defined limit
                if (!j && cut && intWidth) {
                    j = intWidth
                }
                // if cut wasn't forced
                // cut at next possible whitespace after the limit
                if (!j) {
                    var charsUntilNextWhitespace = (line.slice(intWidth).match(reBeginningUntilFirstWhitespace) || [''])[0]
                    j = slice.length + charsUntilNextWhitespace.length
                }
            }
            result.push(line.slice(0, j - rtrim));
            lines[i] += line.slice(0, j - rtrim)
            line = line.slice(j + ltrim)
            lines[i] += line.length ? strBreak : ''
        }
        result.push(line);
    }

    return result;
}

var customTooltips = function(tooltip) {
    // Tooltip Element
    var tooltipEl = document.getElementById('chartjs-tooltip');
    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'chartjs-tooltip';
        tooltipEl.innerHTML = "<table class='chartjs-tooltip-table'></table>"
        content_div = document.getElementById('content');
        content_div.appendChild(tooltipEl);
        //document.appendChild(tooltipEl);
    }

    // Hide if no tooltip
    if (tooltip.opacity === 0) {
        if (!hoverTracker)
        tooltipEl.style.opacity = 0;
        return;
    }

    var titleLines = tooltip.title || [];
    var dataIndex = tooltip.dataPoints[0].index;

    jQuery("#chartjs-tooltip").removeAttr("data-index");
    if (titleLines.length > 1) {
        var item_id = jQuery("#" + titleLines[1] + "_map_" + dataIndex).val();
        jQuery("#chartjs-tooltip").attr("data-index", item_id);
    }

    // Set caret Position
    tooltipEl.classList.remove('above', 'below', 'no-transform');
    if (tooltip.yAlign) {
        tooltipEl.classList.add(tooltip.yAlign);
    } else {
        tooltipEl.classList.add('no-transform');
    }
    function getBody(bodyItem) {
        return bodyItem.lines;
    }
    // Set Text
    if (tooltip.body) {
        var bodyLines = tooltip.body.map(getBody);
        var innerHtml = '<thead>';

        var style = 'font-family: ' + tooltip._titleFontFamily;
        style += '; font-style: ' + tooltip._titleFontStyle;


        innerHtml += '<tr><th class="chartjs-tooltip-table" style="' + style + '">' + titleLines[0] + '</th></tr>';

        innerHtml += '</thead><tbody>';
        bodyLines.forEach(function(body, i) {
            var colors = tooltip.labelColors[i];
            var style = 'background:' + colors.backgroundColor;
            style += '; border-color:' + colors.borderColor;
            style += '; border-width: 2px';
            style += '; font-family: ' + tooltip._bodyFontFamily;
            style += '; font-style: ' + tooltip._bodyFontStyle;
            var span = '<span class="chartjs-tooltip-key" style="' + style + '"></span>';
            innerHtml += '<tr><td class="chartjs-tooltip-table">' + span + body + '</td></tr>';
        });
        innerHtml += '</tbody>';
        var tableRoot = tooltipEl.querySelector('table');
        tableRoot.innerHTML = innerHtml;
    }
    var positionY = this._chart.canvas.offsetTop;
    var positionX = this._chart.canvas.offsetLeft;
    // Display, position, and set styles for font
    tooltipEl.style.opacity = 1;
    tooltipEl.style.left = positionX + tooltip.caretX + 'px';
    tooltipEl.style.top = positionY + tooltip.caretY + 'px';
    tooltipEl.style.fontFamily = tooltip._fontFamily;
    tooltipEl.style.fontSize = tooltip.fontSize;
    tooltipEl.style.fontStyle = tooltip._fontStyle;
    tooltipEl.style.padding = tooltip.yPadding + 'px ' + tooltip.xPadding + 'px';
};

jQuery(document).ready(function ($) {
    var filters = $("#cbme-filters").serialize();

    function get_items(scale_id, filters, offset, chart_id, epa_id, element_id, proxy_id) {

        var item_request = $.ajax({
            url: ENTRADA_URL + "/cbme?section=api-trends",
            type: "GET",
            data: {
                "method": "get-scale-data",
                "filters": filters,
                "limit": 35,
                "offset": offset,
                "scale_id" : scale_id,
                "epa_id" : epa_id,
                "proxy_id" : proxy_id
            },
            beforeSend: function () {
                $("#" + element_id).closest(".charts-items-navigation").addClass("hide");
                $("#" + element_id).closest(".list-card-body").find(".chart-data-loading").removeClass("hide");
            },
            complete: function () {
                $("#" + element_id).closest(".charts-items-navigation").removeClass("hide");
                $("#" + element_id).closest(".list-card-body").find(".chart-data-loading").addClass("hide");
            }
        });

        $.when(item_request).done(function (data) {
            var jsonResponse = safeParseJson(data, "Unknown Error");
            if (jsonResponse.status === "success") {
                $("input[id^='" + chart_id + "_map_']").remove();
                $("#" + chart_id).replaceWith('<canvas id="' + chart_id + '"></canvas>');
                jQuery.each(jsonResponse.data.data_ids, function(index, value) {
                    var hidden = jQuery(document.createElement("input")).attr({
                        'type': 'hidden',
                        'id': chart_id + '_map_' + index,
                    }).val(value);
                    $("#" + chart_id).append(hidden);
                });

                var ctx = document.getElementById(chart_id);
                var myChart = new Chart(ctx, {
                    type: "line",
                    data: {
                        xLabels: jsonResponse.data.xval_label,
                        yLabels: jsonResponse.data.scale,
                        datasets: [{
                            label: "Rating",
                            data: jsonResponse.data.data,
                            borderWidth: 4,
                            borderColor: "#00a8bc",
                            pointBorderColor: "#00a8bc",
                            pointBackgroundColor: "#00a8bc",
                            pointRadius: 4,
                            pointBorderWidth: 0,
                            pointHoverBackgroundColor: "#00a8bc",
                            pointHoverRadius: 4,
                            lineTension: 0,
                            fill: false,
                        }]
                    },
                    options: {
                        responsive: true,
                            maintainAspectRatio: false,
                            title: {
                            display: false
                        },
                        legend: {
                            display: false
                        },
                        tooltips: {
                            xPadding: 0,
                                yPadding: 0,
                                displayColors: false,
                                intersect: false,
                                titleMarginBottom: 10,
                                backgroundColor: "rgba(0,0,0,.85)",
                                titleFontFamily: "'Lucida Grande', Geneva, Verdana, Arial, Helvetica, sans-serif",
                                bodyFontFamily: "'Lucida Grande', Geneva, Verdana, Arial, Helvetica, sans-serif",
                                enabled: false,
                                mode: 'index',
                                position: 'nearest',
                                custom: customTooltips,
                                callbacks: {
                                // Hack to get the active chart ID when rendering the tooltip.
                                afterTitle: function(tooltipItems, data) {
                                    return chart_id;
                                }
                            }
                        },
                        scales: {
                            xAxes: [{
                                display: true,
                                scaleLabel: {
                                    display: true,
                                    labelString: jsonResponse.data.xaxis_label,
                                    fontFamily: "'Lucida Grande', Geneva, Verdana, Arial, Helvetica, sans-serif",
                                    fontColor: "#8b959d"
                                },
                                gridLines: {
                                    display: false
                                },
                                ticks: {
                                    display: false
                                }
                            }],
                            yAxes: [{
                                type: "category",
                                position: "left",
                                display: true,
                                gridLines: {
                                    color: "#ecf0f3",
                                    lineWidth: 1,
                                    tickMarkLength: 0,
                                    zeroLineWidth: 1,
                                    zeroLineColor: "#ecf0f3"
                                },
                                ticks: {
                                    fontFamily: "'Lucida Grande', Geneva, Verdana, Arial, Helvetica, sans-serif",
                                    fontColor: "#8b959d",
                                    padding: 15,
                                    userCallback: function(value, index, values) {
                                        return wordwrap(value, 25);
                                    }
                                },
                                afterFit: function(scaleInstance) {
                                    scaleInstance.width = 180;
                                }
                            }]
                        }
                    }
                });

                $("#rating-scale-prev-btn-" + scale_id + "-epa-" + epa_id).attr("data-offset", parseInt(offset) + parseInt(jsonResponse.data.data.length));
                val = (offset - jsonResponse.data.data.length - trends_query_limit) < 0 ? 0 : offset - jsonResponse.data.data.length - trends_query_limit;
                $("#rating-scale-next-btn-" + scale_id + "-epa-" + epa_id).attr("data-offset", val);

                var assessment_count = parseInt($("#chart-assessments-count-" + scale_id + "-epa-" + epa_id).val());

                if (offset == 0) {
                    $("#rating-scale-next-btn-" + scale_id + "-epa-" + epa_id).addClass("disabled");
                } else {
                    $("#rating-scale-next-btn-" + scale_id + "-epa-" + epa_id).removeClass("disabled");
                }

                if (parseInt($("#rating-scale-prev-btn-" + scale_id + "-epa-" + epa_id).attr("data-offset")) >= assessment_count) {
                    $("#rating-scale-prev-btn-" + scale_id + "-epa-" + epa_id).addClass("disabled");
                } else {
                    $("#rating-scale-prev-btn-" + scale_id + "-epa-" + epa_id).removeClass("disabled");
                }

            } else {
                return false;
            }
        });
    }

    $(document).on("click", "#chartjs-tooltip", function() {
        var dassessment_id = $(this).data("index");
        var proxy_id = $("input[name=\"proxy_id\"]").val();
        document.location.href = ENTRADA_URL + '/assessments/assessment?dassessment_id=' + dassessment_id + "&target_type=proxy_id&target_record_id=" + proxy_id;
    });

    $(".trend-tabs").on("click", function () {
        var tab = $(this).data("chart-type");

        $.ajax({
            url: ENTRADA_URL + "/cbme?section=api-trends",
            type: "POST",
            data: {"method": "set-trends-tab-preference", "tab": tab}
        });
    });

    $(document).on("click", ".scale-items-nav", function() {
        var offset = $(this).attr("data-offset");
        var scale_id = $(this).data("scale-id");
        var chart_id = $(this).data("chart-id");
        var epa_id = parseInt($(this).data("epa-id"));
        var element_id = $(this).attr("id");
        var proxy_id = $("input[name=\"proxy_id\"]").val();

        if (! $(this).hasClass("disabled")) {
            get_items(scale_id, filters, offset, chart_id, epa_id, element_id, proxy_id);
        }
    });

    /**
     * Handle trend title truncation
     */
    trend_header_load(110);

    $(window).on("resize", function () {
        trend_header_load(110);
    });

    $("#global_assessment_tab, #milestone_ec_tab").on("shown", function () {
        trend_header_load(110);
    });

    function trend_header_load(padding_width) {
        $(".trend-card").each(function() {
            var count_cell_width = $(this).find(".trend-assessment-count").outerWidth(),
                score_cell_width = $(this).find(".assessment-scale-wrap").outerWidth();

            var trend_card = $(this).find(".list-card-item-wrap").outerWidth(),
                cell_width_total = count_cell_width + score_cell_width + padding_width;

            var description_width = trend_card - cell_width_total;

            $(this).find(".list-card-title").css("width", description_width + "px");
            $(this).find(".trend-title").css("width", description_width + "px");
        });
    }
});