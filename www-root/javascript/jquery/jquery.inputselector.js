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
 *
 *
 */
;(function($) {

    $.fn.inputSelector = function(options) {

        /*
         *
         *  Input Selector settings
         *
         */

        var self = this;

        var settings = $.extend({
            "rows": "",
            "columns": "",
            "data_text": "",
            "header": "",
            "label": "",
            "type": "",
            "form_name": "",
            "modal" : 0,
            "value": $(self).val(),
            "open": 0
        }, options);

        $(this).data("settings", settings);

        /*
         *
         *  Advanced search event listeners
         *
         */

        self.on("click", function (e) {
            e.preventDefault();
            toggleSelectorMenu();

            setTimeout(function () {
                settings.open = 1;
            }, 500);
        });

        /*
        changes the active value
         */

        $(settings.form_name).on("click", ".selector-menu td.ui-timefactor-cell", function (e) {
            e.preventDefault();
            updateValue($(this));

            setTimeout(function () {
                closeSelectorMenu();
            }, 200);
        });


        $(document).mouseup(function (e) {
            var selector_menu = $(".selector-menu");

            if ($(selector_menu).length > 0 && settings.open === 1) {
                if (!selector_menu.is(e.target) && selector_menu.has(e.target).length === 0) {
                    closeSelectorMenu();
                }
            }
        });

        function updateValue(clicked) {
            var type = settings.type;
            $(".ui-timefactor-cell a").removeClass("ui-state-active");

            clicked.find("a").addClass("ui-state-active");
            var text = clicked.find("a").text();

            if (type === "button") {
                $(self).text(text + " - " + settings.label);
                self.data("value", text);
            }
            $(self).val(text);
        }

        function toggleSelectorMenu () {
            if ($(".selector-menu").length > 0) {
                closeSelectorMenu();
            } else {
                settings.value = $(self).val();
                buildSelectorMenu();
            }
        }

        function buildSelectorMenu () {
            var active_value    = $(self).val();
            var number_rows     = settings.rows;
            var number_columns  = settings.columns;
            var data            = settings.data_text;
            var header          = settings.header;
            var modal           = settings.modal;

            var selector_container = $(document.createElement("div")).addClass("selector-menu");
            var table       = $(document.createElement("table")).addClass("ui-timefactor-table").addClass("ui-widget-content").addClass("ui-corner-all");
            var tr_head     = $(document.createElement("tr"));
            var td_head     = $(document.createElement("td")).addClass("ui-timefactor");
            var div         = $(document.createElement("div")).addClass("ui-timefactor-title").addClass("ui-widget-header").addClass("ui-helper-clearfix").addClass("ui-corner-all").text(header);
            var sub_table   = $(document.createElement("table")).addClass("ui-timefactor");

            var item_count = 0;
            for (var i = 0; i < number_rows; i++) {
                var tr = $(document.createElement("tr"));

                for (var j = 0; j < number_columns; j++) {
                    var item_value = data[item_count];
                    var td = $(document.createElement("td")).addClass("ui-timefactor-cell");

                    var a = $(document.createElement("a")).addClass("ui-state-default");
                    if (typeof active_value != "undefined" && active_value != "") {
                        if (active_value == item_value) {
                            a.addClass("ui-state-active");
                        }
                    }
                    var text = $(document.createTextNode(item_value));

                    a.append(text);
                    td.append(a);
                    tr.append(td);
                    item_count++;
                }

                sub_table.append(tr);
            }

            td_head.append(div);
            td_head.append(sub_table);

            tr_head.append(td_head);
            table.append(tr_head);
            selector_container.append(table);

            if (modal) {
                var parent_container = self.parent().parent();
                var position = self.position();
                var left = position.left + parent_container.offset().left - 70;
                var top = parent_container.offset().top + jQuery(self).height() + 10;
            } else {
                var parent_container = self.parent();
                var position = self.position();
                var left = parent_container.offset().left;
                var top = position.top + jQuery(self).height() + 10;
            }

            parent_container.after(selector_container);
            selector_container.offset({left: left, top: top});
        }

        function closeSelectorMenu () {
            if ($(".selector-menu").length > 0) {
                $(".selector-menu").remove();
                settings.open = 0;
            }
        }

        function getMaxZIndex() {
            var maxZ = Math.max.apply(null,
                $.map($('body *'), function(e,n) {
                    if ($(e).css('position') != 'static')
                        return parseInt($(e).css('z-index')) || 1;
                }))
            return maxZ;
        }
    };
} (jQuery));


