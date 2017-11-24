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
 * Helper class to add a script header delcarations for use with the
 * $HEAD variable. This class allows the use of specific functionality
 * that relies on newer versions of jQuery than the default 1.7.
 *
 *   addjQuery()               loads the latest version of jQuery we have
 *                             access to (3.1.1). All subsequent functions
 *                             rely on this having been called first.
 *
 *   addjQueryLoadTemplate()   loads the loadTemplate jQuery plugin, and
 *                             hooks its methods to the older jQuery version.
 *
 * Other jQuery new-version dependent functionality should be added to this
 * class as needed.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Entrada_Utilities_jQueryHelper {

    static protected $is_new_jquery_loaded = false;

    /**
     * Build script declarations to add the latest jQuery script to the $HEAD global. No Conflict mode.
     *
     * @return string
     */
    public static function addjQuery() {
        ob_start();
        ?>
        <script type="text/javascript" src="<?php echo ENTRADA_URL; ?>/javascript/jquery/jquery-3.1.1.min.js"></script>
        <script type="text/javascript">
            var jQueryVersions = {};
            var previousjQuery = null;
            var latestjQuery = null;

            /**
             * Iterate through the jQueryVersions object and find the highest version stored therein.
             * Reset the global jQuery object to use that version.
             */
            function useLatestjQueryVersion() {
                // Iterate through the jQueryVersions object and change the jQuery object to use the latest one.
                if (typeof jQueryVersions != "undefined") {
                    var highest_version = 0;
                    for (var key in jQueryVersions) {
                        if (parseFloat(key) > parseFloat(highest_version)) {
                            highest_version = key;
                            latestjQuery = jQueryVersions[key];
                        }
                    }
                    if (typeof jQuery != "undefined") {
                        if (jQuery.fn.jquery != jQueryVersions[highest_version].fn.jquery) {
                            previousjQuery = jQuery;
                            jQuery = jQueryVersions[highest_version];
                        }
                    }
                }
            }

            /**
             * Restore the previous jQuery version.
             */
            function restorePreviousjQueryVersion() {
                if (previousjQuery != null) {
                    jQuery = previousjQuery;
                }
            }

            /**
             * Save the the latest version (included above) in the jQueryVersions array.
             */
            var newjQuery = $.noConflict(true);
            if (typeof newjQuery != "undefined") {
                var version_number = parseFloat(newjQuery.fn.jquery);
                // Add this version to the global jQueryVersions object.
                jQueryVersions[version_number] = newjQuery;
            }
        </script>
        <?php
        $header_string = ob_get_contents();
        ob_end_clean();
        self::$is_new_jquery_loaded = true;
        return $header_string;
    }

    /**
     * Add jQuery loadTemplate library, only if newer jQuery is available/loaded.
     * This function swaps the jQuery library as necessary so that the loadTemplate plugin is bound to the appropriate jQuery object.
     *
     * @return string
     */
    public static function addjQueryLoadTemplate() {
        $header_string = "";
        if (self::$is_new_jquery_loaded): ob_start(); ?>
            <script type="text/javascript">
                useLatestjQueryVersion(); // Bind loadTemplate to the latest jQuery version, if newer version is loaded.
            </script>
            <script type="text/javascript" src="<?php echo ENTRADA_URL ?>/javascript/jquery/jquery.loadTemplate.min.js"></script>
            <script type="text/javascript">
                restorePreviousjQueryVersion();
                // In the old jQuery version, add a hook for loadTemplate
                (function ($) {
                    $.fn.loadTemplate = latestjQuery.fn.loadTemplate;
                    $.addTemplateFormatter = latestjQuery.addTemplateFormatter;
                })(jQuery);
            </script>
            <?php
            $header_string = ob_get_contents();
            ob_end_clean();
        endif;
        return $header_string;
    }
}