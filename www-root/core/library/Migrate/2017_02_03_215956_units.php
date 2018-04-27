<?php

class Migrate_2017_02_03_215956_units extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `<?php echo AUTH_DATABASE; ?>`.`acl_permissions`(`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES ('weekcontent', NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, 'NotGuest');
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
        DELETE FROM  `<?php echo AUTH_DATABASE; ?>`.`acl_permissions`
        WHERE `resource_type` = 'weekcontent'
        AND `resource_value` IS NULL
        AND `entity_type` IS NULL
        AND `entity_value` IS NULL
        AND `create` IS NULL
        AND `read` = 1
        AND `update` IS NULL
        AND `delete` IS NULL
        AND `assertion` = 'NotGuest';
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

        $row = $db->GetRow("
            SELECT 1 FROM `".AUTH_DATABASE."`.`acl_permissions`
            WHERE `resource_type` = 'weekcontent'
            AND `resource_value` IS NULL
            AND `entity_type` IS NULL
            AND `entity_value` IS NULL
            AND `create` IS NULL
            AND `read` = 1
            AND `update` IS NULL
            AND `delete` IS NULL
            AND `assertion` = 'NotGuest'");

        if ($row) {
            return 1;
        } else {
            return 0;
        }
    }
}
