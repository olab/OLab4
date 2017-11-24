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
 */

class Views_Event_Draft_Audience extends Views_Event_Base {
    protected $default_fieldset = array(
        "daudience_id",
        "eaudience_id",
        "devent_id",
        "event_id",
        "audience_type",
        "audience_value",
        "custom_time",
        "custom_time_start",
        "custom_time_end",
        "updated_date",
        "updated_by"
    );

    protected $table_name               = "draft_audience";
    protected $primary_key              = "daudience_id";
    protected $default_sort_column      = "eaudience_id";


    public function __construct(Models_Event_Draft_Event_Audience $event_audience) {
        $this->event_audience = $event_audience;
    }

}