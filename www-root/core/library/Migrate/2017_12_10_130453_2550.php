<?php
class Migrate_2017_12_10_130453_2550 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        UPDATE `<?php echo AUTH_DATABASE; ?>`.`acl_permissions`
        SET `entity_type` = 'group:role',
        `entity_value` = 'staff:pcoordinator',
        `create` = 1,
        `read` = 1,
        `update` = 1,
        `delete` = 1
        WHERE `resource_type` = 'gradebook'
        AND `resource_value` IS NULL
        AND `entity_type` = 'role'
        AND `entity_value` = 'pcoordinator'
        AND `app_id` = 1
        AND `create` IS NULL
        AND `read` = 1
        AND `update` IS NULL
        AND `delete` IS NULL
        AND `assertion` = 'GradebookOwner';
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
        UPDATE `<?php echo AUTH_DATABASE; ?>`.`acl_permissions`
        SET `entity_type` = 'role',
        `entity_value` = 'pcoordinator',
        `create` = NULL,
        `read` = 1,
        `update` = NULL,
        `delete` = NULL
        WHERE `resource_type` = 'gradebook'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group:role'
        AND `entity_value` = 'staff:pcoordinator'
        AND `app_id` = 1
        AND `create` = 1
        AND `read` = 1
        AND `update` = 1
        AND `delete` = 1
        AND `assertion` = 'GradebookOwner';
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

        $query = "SELECT * FROM `" . AUTH_DATABASE . "`.`acl_permissions` 
                  WHERE `resource_type` = 'gradebook'
                  AND `resource_value` IS NULL
                  AND `entity_type` = 'role' 
                  AND `entity_value` = 'pcoordinator'
                  AND `app_id` = 1
                  AND `create` IS NULL
                  AND `read` = 1
                  AND `update` IS NULL
                  AND `delete` IS NULL
                  AND `assertion` = 'GradebookOwner'";

        if ($db->GetRow($query)) {
            return 0;
        } else {
            return 1;
        }
    }
}
