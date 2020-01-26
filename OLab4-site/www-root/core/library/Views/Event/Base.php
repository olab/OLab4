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

class Views_Event_Base extends Views_HTML {

    public function renderLI() {
        $event_audience = $this->event_audience;
        $audience_type  = $event_audience->getAudienceType();
        $audience_value = $event_audience->getAudienceValue();
        $audience_name  = $event_audience->getAudienceName();
        $custom_time    = $event_audience->getCustomTime();
        $custom_time_s  = $event_audience->getCustomTimeStart();
        $custom_time_e  = $event_audience->getCustomTimeEnd();
        $li_class       = "";
        $id             = "";
        $data_type      = "";
        $remove_id      = "";
        $remove_type    = "";

        switch($audience_type) {
            case "cohort" :
                $id          = "audience_cohort_" . $audience_value;
                $li_class    = "group";
                $data_type   = "cohort";
                $remove_id   = "cohort_" . $audience_value;
                $remove_type = "cohorts";
                break;
            case "group_id" :
                $id          = "audience_cgroup_" . $audience_value;
                $li_class    = "group";
                $data_type   = "group_id";
                $remove_id   = "cgroup_" . $audience_value;
                $remove_type = "course_groups";
                break;
            case "proxy_id" :
                $id          = "audience_student_" . $audience_value;
                $li_class    = "user";
                $data_type   = "proxy_id";
                $remove_id   = "student_" . $audience_value;
                $remove_type = "students";
                break;
        }

        $badge_on = ($custom_time == 1 ? "badge-success" :  "badge-time-off");

        $custom_time_html = $audience_name;
        $custom_time_html .= "<span class=\"time\">";

        if ($custom_time == 1 && $custom_time_s != 0 && $custom_time_e != 0) {
            $start_time = new DateTime(date("Y-m-d H:i:s", $custom_time_s));
            $custom_time_html .= date("g:i a", $custom_time_s) . " - " . date("g:i a", $custom_time_e);
        };

        $custom_time_html .= "</span>";
        $custom_time_html .= "<span class=\"badge time-badge " . $badge_on . "\">";
        $custom_time_html .= "<i class=\"icon-time icon-white custom_time_icon\" ></i>";
        $custom_time_html .= "</span>";

        $output = "<li class=\"" . $li_class. "\" id=\"" . $id . "\" style=\"cursor: move;\" data-type=\"" . $data_type . "\" data-value=\"" . $audience_value . "\" >";
        $output .= $custom_time_html;
        $output .= "<img class=\"list-cancel-image\" src=\"" . ENTRADA_URL . "/images/action-delete.gif\" onclick=\"removeAudience('" . $remove_id . "', '" . $remove_type . "')\" />";
        $output .= "</li>";

        return $output;
    }

    public function renderTimeRow() {
        $event_audience = $this->event_audience;
        $audience_type  = $event_audience->getAudienceType();
        $audience_value = $event_audience->getAudienceValue();
        $audience_name  = $event_audience->getAudienceName();
        $custom_time    = $event_audience->getCustomTime();
        $custom_time_s  = $event_audience->getCustomTimeStart();
        $custom_time_e  = $event_audience->getCustomTimeEnd();
        $custom_html    = "";
        switch($audience_type) {
            case "cohort" :
                $id          = "cohort_time_" . $audience_value;
                $span_class  = "group";
                $data_type   = "cohort";
                $image_src   = ENTRADA_URL . "/images/list-community.gif";
                break;
            case "group_id" :
                $id          = "group_id_time_" . $audience_value;
                $span_class  = "group";
                $data_type   = "group_id";
                $image_src   = ENTRADA_URL . "/images/list-community.gif";
                break;
            case "proxy_id" :
                $span_class  = "individual";
                $id          = "proxy_id_time_" . $audience_value;
                $data_type   = "proxy_id";
                $image_src   = ENTRADA_URL . "/images/list-user.gif";
                break;
        }

        if ($custom_time == 1 && $custom_time_s != 0 && $custom_time_e != 0) {
            $custom_html        = "<p>" . date("g:i a", $custom_time_s) . " - " . date("g:i a", $custom_time_e) . "</p>";
            $active_time_on     = " active";
            $active_time_off    = "";
        } else {
            $active_time_on     = "";
            $active_time_off    = " active";
        }

        $html = "<tr>";
        $html .= "<td>";
        $html .= "<img src=\"" . $image_src . "\" />";
        $html .= "<span class=\"" . $span_class . "\">";
        $html .= $audience_name;
        $html .= "</span>";
        $html .= "</td>";
        $html .= "<td id=\"" . $id . "\" class=\"slider-text-time\">";
        $html .= $custom_html;
        $html .= "</td>";
        $html .= "<td>";
        $html .= "<div class=\"slider-range\" data-id=\"" . $audience_value . "\" data-type=\"" . $data_type . "\"></div>";
        $html .= "</td>";
        $html .= "</tr>";

        return $html;
    }

}