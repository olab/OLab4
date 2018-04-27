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
 * A view for rendering CBME trends charts.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_TrendsChart extends Views_HTML {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("rating_scales", "preferences", "course_id", "course_name", "courses", "trends_query_limit"))) {
            return false;
        }

        if (!$this->validateArray($options, array("charts"))) {
            return false;
        }

        return true;
    }

    protected function renderView($options = array()) {
        global $translate;

        $this->renderHead($options["trends_query_limit"]);

        $has_charts = false;
        if (count($options["charts"])) {
            foreach ($options["charts"] as $chart_data) {
                if (isset($chart_data["charts"]) && is_array($chart_data["charts"]) && count($chart_data["charts"])) {
                    $has_charts = true;
                }
            }
        }
        
        if ($has_charts):
            $tabs = array_keys($options["charts"]);
            $active_tab = isset($options["preferences"]["trends_selected_tab"]) && array_key_exists($options["preferences"]["trends_selected_tab"], $options["charts"])
                ? $options["preferences"]["trends_selected_tab"]
                : $tabs[0];

            $chart_view = new Views_CBME_Chart_Line();
            ?>
            <div class="clearfix"></div>
            <ul class="nav nav-tabs space-above medium" role="tablist">
            <?php
            $tab_count = 0;
            foreach ($options["charts"] as $chart_type => $chart_data): ?>
                <?php if ($chart_type != "milestone_ec") : ?>
                <li role="presentation"<?php echo ($chart_type == $active_tab) ? " class=\"active\"" : ""; ?>>
                    <a id="<?php echo $chart_type; ?>_tab" href="#<?php echo html_encode($chart_type); ?>" class="trend-tabs" data-chart-type="<?php echo $chart_type; ?>" aria-controls="home" role="tab" data-toggle="tab"><?php echo html_encode($chart_data["title"]); ?></a>
                </li>
                <?php endif; ?>
                <script type="text/javascript">
                        jQuery(document).ready(function () {
                        jQuery("#<?php echo $chart_type; ?>_tab").on("click", function() {
                            if (!<?php echo $chart_type?>_loaded) {
                                var waitTillActive = setInterval(function () {
                                    if (jQuery("#<?php echo $chart_type; ?>").hasClass("active")) {
                                        jQuery("#<?php echo $chart_type; ?>").trigger('isNowActive')
                                        clearInterval(waitTillActive);
                                    }
                                }, 100);
                                <?php echo $chart_type?>_loaded = true;
                            }
                        });
                        var <?php echo $chart_type?>_loaded = false;
                    });
                </script>
            <?php endforeach;?>
            </ul>

            <div class="tab-content no-overflow">
            <?php
            $tab_count = 0;
            foreach ($options["charts"] as $chart_type => $chart_data): ?>
                <div role="tabpanel" class="tab-pane<?php echo ($chart_type == $active_tab) ? " active" : ""; ?>" id="<?php echo html_encode($chart_type); ?>">
                <?php foreach($chart_data["charts"] as $charts):
                    $lazyload_tab = ($chart_type != $active_tab) ? $chart_type : false;
                    $chart_view->render(array_merge(array("lazyload_tab" => $lazyload_tab, "trends_query_limit" => $options["trends_query_limit"]), $charts));
                endforeach; ?>
                </div>
           <?php
           endforeach;
        else: ?>
            <div class="alert alert-info"><?php echo $translate->_("No data available."); ?></div>
        <?php endif;
    }

    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME trends chart."); ?></strong>
        </div>
        <?php
    }

     protected function renderHead ($trends_query_limit = 40) {
        global $HEAD;

        $HEAD[] = "<script>var ENTRADA_URL='".ENTRADA_URL."';</script>";
        $HEAD[] = "<script type=\"text/javascript\">var trends_query_limit = parseInt('". $trends_query_limit ."');</script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/cbme/trends.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/cbme/cbme.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    }
}