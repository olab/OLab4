<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A view for rendering CBME line charts
 *
 * @author Organization: Queen's University.
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Chart_Line extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("rating_scale_id", "chart_id", "chart_title", "card_title", "card_label", "query_limit", "trends_query_limit", "assessments_count")) ) {
            return false;
        }

        if (!$this->validateArray($options, array("data", "scale", "labels", "scale_reponse_count"))) {
            return false;
        }

        return true;
    }

    protected function renderView($options = array()) {
        global $translate;

        $toggle_mode = isset($options["toggle_mode"]) ? intval($options["toggle_mode"]) : 0;
        $pin_mode = isset($options["pin_mode"]) ? intval($options["pin_mode"]) : 0;
        $status_icon = isset($options["status_icon"]) ? intval($options["status_icon"]) : 0;
        $epa_id = (isset($options["epa"]) && $options["epa"]) ? $options["epa"] : 0;

        $this->renderHead();
        ?>
        <script>
            jQuery(document).ready(function ($) {
                $(document).on("mouseover", "#<?php echo html_encode($options["chart_id"]); ?>", function () {
                    hoverTracker = false;
                });

                $(document).on("mouseout", "#<?php echo html_encode($options["chart_id"]); ?>", function () {
                    if ($("#chartjs-tooltip").is(":hover")) {
                        hoverTracker = true;
                        $("#chartjs-tooltip").css("opacity", 1);
                    }
                });
            });
        </script>

        <?php if ($options["chart_title"]): ?>
        <h2><?php echo $translate->_(html_encode($options["chart_title"])); ?></h2>
        <?php endif; ?>
        <input type="hidden" id="chart-assessments-count-<?php echo $options["rating_scale_id"]; ?>-epa-<?php echo $epa_id; ?>" value="<?php echo $options["assessments_count"]; ?>" />
        <div class="list-card trend-card">
            <div class="list-card-item-wrap">
                <div class="list-card-header">
                    <div class="table">
                        <div class="list-card-cell trend-title">
                            <span class="list-card-title"><?php echo html_encode($options["card_title"]) ; ?><?php if ($status_icon) { ?><span class="list-item-status-incomplete list-set-item-icon-sm fa fa-circle-o"></span><?php } ?></span>
                        </div>
                        <?php if (count($options["scale_reponse_count"])): ?>
                        <div class="list-card-cell list-card-rating-cell">
                            <span class="scale-wrap assessment-scale-wrap">
                                <?php foreach (array_reverse($options["scale"]) as $response): ?>
                                <span style="display: inline !important;" data-toggle="tooltip" title="<?php echo $response; ?>" class="rating-scale-count-active"><?php echo intval($options["scale_reponse_count"][$response]); ?></span>
                                <?php endforeach; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="list-card-cell">
                            <span class="list-card-label trend-assessment-count"><?php echo html_encode($options["card_label"]); ?></span>
                        </div>
                        <?php if ($pin_mode): ?>
                        <div class="list-card-cell">
                            <a href="#" class="list-card-btn pin-item">
                                <span class="list-card-icon-active fa fa-thumb-tack"></span>
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if ($toggle_mode): ?>
                        <div class="list-card-cell">
                            <a href="#" class="view-trend-toggle">View Trend<span class="fa fa-angle-down"></span></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="list-card-body">
                    <div class="charts-items-navigation<?php echo (count($options["data"]) >= $options["assessments_count"]) ? " hide" : ""; ?>">
                        <a id="rating-scale-prev-btn-<?php echo $options["rating_scale_id"]; ?>-epa-<?php echo $epa_id; ?>"
                           data-scale-id="<?php echo $options["rating_scale_id"]; ?>"
                           data-epa-id="<?php echo $epa_id; ?>"
                           data-chart-id="<?php echo $options["chart_id"]; ?>"
                           data-offset="<?php echo count($options["data"]); ?>"
                           style="font-size: 20px;"
                           class="fa fa-chevron-left pull-left scale-items-nav">
                        </a>
                        <a id="rating-scale-next-btn-<?php echo $options["rating_scale_id"]; ?>-epa-<?php echo $epa_id; ?>"
                           data-scale-id="<?php echo $options["rating_scale_id"]; ?>"
                           data-epa-id="<?php echo $epa_id; ?>"
                           data-chart-id="<?php echo $options["chart_id"]; ?>"
                           data-offset="<?php echo count($options["data"]) - $options["query_limit"]; ?>"
                           style="font-size: 20px;" class="fa fa-chevron-right pull-right scale-items-nav disabled" aria-hidden="true">
                        </a>
                    </div>
                    <div class="chart-data-loading hide"></div>
                </div>
                <div class="list-card-body trend-chart" style="display: block;">
                    <canvas id="<?php echo html_encode($options["chart_id"]); ?>" height="250"></canvas>
                </div>
            </div>
        </div>
        <?php
        $this->renderChartJS($options);
    }

    protected function renderHead () {
        global $HEAD;
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/Chart/Chart.bundle.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    }

    protected function renderChartJS($options) {
        global $translate;
        $labels_array = array();

        $data_ids = $options["data_ids"];
        $chart_id = $options["chart_id"];
        $data   = '"' . implode('","', $options["data"]) . '"';
        $scale  = '"' . implode('","', $options["scale"]) . '"';
        $lazyload_tab = isset($options["lazyload_tab"]) ? $options["lazyload_tab"] : false;
        $option["scale"] = array_map(function ($scale) {return wordwrap($scale, 25, "\n"); }, $options["scale"]);

        foreach($options["labels"] as $index => $label) {
            $labels_array[] = sprintf($translate->_("%s <br>Encounter date %s"), $label, $options["chart_dates"][$index]);
        }
        $dates = '"' . implode('","', $labels_array) . '"';

        $xaxis_label = sprintf($translate->_("Assessments (%s to %s)"), $options["chart_dates"][0], $options["chart_dates"][sizeof($options["chart_dates"]) - 1]);

        foreach ($data_ids as $index => $data_option) { ?>
            <input type="hidden" id="<?php echo "{$chart_id}_map_{$index}"; ?>" value="<?php echo $data_option; ?>" />
        <?php }
        ?>
        <script>


            var customTooltips;

            jQuery(document).ready(function ($) {
<?php if ($lazyload_tab) { ?>
                $("#<?php echo $lazyload_tab; ?>").on("isNowActive", function() {
<?php } ?>
                    var <?php echo $chart_id; ?>_map = Array();
                    var ctx = document.getElementById("<?php echo html_encode($chart_id); ?>");
                    var myChart = new Chart(ctx, {
                        type: "line",
                        data: {
                            xLabels: [<?php echo $dates; ?>],
                            yLabels: [<?php echo $scale; ?>],
                            datasets: [{
                                label: "Rating",
                                data: [<?php echo $data; ?>],
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
                                        return '<?php echo $chart_id; ?>';
                                    }
                                }
                            },
                            scales: {
                                xAxes: [{
                                    display: true,
                                    scaleLabel: {
                                        display: true,
                                        labelString: '<?php echo $xaxis_label; ?>',
                                        fontFamily: "'Lucida Grande', Geneva, Verdana, Arial, Helvetica, sans-serif",
                                        fontColor: "#8b959d"
                                    },
                                    gridLines: {
                                        display: false
                                    },
                                    ticks: {
                                        fontFamily: "'Lucida Grande', Geneva, Verdana, Arial, Helvetica, sans-serif",
                                        fontColor: "#8b959d",
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
                                        scaleInstance.width = 180; // sets the width to 100px
                                    }
                                }]
                            }
                        }
                    });
<?php if ($lazyload_tab) { ?>
                });
<?php } ?>
            });
        </script>
        <?php
    }

    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME chart"); ?></strong>
        </div>
        <?php
    }
}

