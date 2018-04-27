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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine, MedIT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2017 University of British Columbia. All Rights Reserved.
 *
 * This migration enables the units/weeks page in any course community.
 * It may be hidden if the administrator wishes.
 */

class Migrate_2017_07_24_183958_2178 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `community_type_pages`(
                `ctpage_id`,
                `type_id`,
                `type_scope`,
                `parent_id`,
                `page_order`,
                `page_type`,
                `menu_title`,
                `page_title`,
                `page_url`,
                `page_content`,
                `page_active`,
                `page_visible`,
                `allow_member_view`,
                `allow_troll_view`,
                `allow_public_view`,
                `lock_page`,
                `updated_date`,
                `updated_by`
            ) VALUES (NULL, 2, 'organisation', 0, 1, 'course', 'Units', 'Units', 'units', '', 1, 1, 1, 0, 1, 1, 1500937017, 1);
        INSERT INTO `community_type_pages`(
                `ctpage_id`,
                `type_id`,
                `type_scope`,
                `parent_id`,
                `page_order`,
                `page_type`,
                `menu_title`,
                `page_title`,
                `page_url`,
                `page_content`,
                `page_active`,
                `page_visible`,
                `allow_member_view`,
                `allow_troll_view`,
                `allow_public_view`,
                `lock_page`,
                `updated_date`,
                `updated_by`
            ) VALUES (NULL, 2, 'global', 0, 1, 'course', 'Units', 'Units', 'units', '', 1, 1, 1, 0, 1, 1, 1500937017, 1);
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        DELETE FROM `community_type_pages` WHERE `page_url` = 'units' AND `type_id` = 2 AND `updated_date` = 1500937017;
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Optional: PHP that verifies whether or not the changes outlined
     * in "up" are present in the active database.
     *
     * Return Values: -1 (not run) | 0 (changes not present or complete) | 1 (present)
     *
     * @return int
     */
    public function audit() {
        global $db;

        $query = "SELECT COUNT(*) FROM `community_type_pages`
                  WHERE `page_url` = 'units'
                  AND `type_id` = 2
                  AND `updated_date` = 1500937017";

        $results = $db->GetOne($query);

        if ($results) {
            return 1;
        } else {
            return 0;
        }
    }
}
