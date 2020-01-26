<?php
class Migrate_2016_08_22_153916_1529 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `<?php echo AUTH_DATABASE;?>`.`acl_permissions` (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        ('secure', NULL, 'group', 'faculty', 1, NULL, 1, NULL, NULL, NULL);

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
        DELETE FROM `<?php echo AUTH_DATABASE;?>`.`acl_permissions`
        WHERE `resource_type` = 'secure'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group'
        AND `entity_value` = 'faculty'
        AND `app_id` = 1
        AND `create` IS NULL
        AND `read` = 1
        AND `update` IS NULL
        AND `delete` IS NULL
        AND `assertion` IS NULL;";
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
        $query = "SELECT 1 FROM `".AUTH_DATABASE."`.`acl_permissions`
                  WHERE `resource_type` = 'secure'
                  AND `resource_value` IS NULL
                  AND `entity_type` = 'group'
                  AND `entity_value` = 'faculty'
                  AND `app_id` = 1
                  AND `create` IS NULL
                  AND `read` = 1
                  AND `update` IS NULL
                  AND `delete` IS NULL
                  AND `assertion` IS NULL;";
        return (int)$db->GetOne($query);
    }
}
