<?php
class Migrate_2017_12_05_122229_2550 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `<?php echo AUTH_DATABASE ?>`.`acl_permissions` (`permission_id`, `resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        (NULL, 'course', NULL, 'group:role', 'staff:pcoordinator', 1, NULL, 1, 1, NULL, 'CourseOwner');
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
        DELETE FROM `<?php echo AUTH_DATABASE ?>`.`acl_permissions`
        WHERE `resource_type` = 'course'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group:role'
        AND `entity_value` = 'staff:pcoordinator'
        AND `app_id` = 1
        AND `create` IS NULL
        AND `read` = 1
        AND `update` = 1
        AND `delete` IS NULL
        AND `assertion` = 'CourseOwner';
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
                  WHERE `resource_type` = 'course'
                  AND `resource_value` IS NULL
                  AND `entity_type` LIKE '%role' 
                  AND `entity_value` LIKE '%pcoordinator'
                  AND `app_id` = 1
                  AND `read` = 1
                  AND `update` = 1
                  AND `assertion` = 'CourseOwner'";
        
        if ($db->GetRow($query)) {
            return 1;
        } else {
            return 0;
        }
    }
}
