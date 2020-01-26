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
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Views_Event_Audience extends Views_Event_Base {
    protected $default_fieldset = array(
        "eaudience_id",
        "event_id",
        "audience_type",
        "audience_value",
        "custom_time",
        "custom_time_start",
        "custom_time_end",
        "updated_date",
        "updated_by"
    );

    protected $table_name               = "event_audience";
    protected $primary_key              = "eaudience_id";
    protected $default_sort_column      = "event_id";

    protected $match, $question_version, $short_name;

    public function __construct(Models_Event_Audience $event_audience) {
        $this->event_audience = $event_audience;
    }

}