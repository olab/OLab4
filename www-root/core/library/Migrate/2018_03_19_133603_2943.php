<?php
class Migrate_2018_03_19_133603_2943 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        UPDATE `<?php echo AUTH_DATABASE; ?>`.`acl_permissions`
        SET `update` = 0
        WHERE `resource_type` = 'curriculum'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group:role'
        AND `entity_value` = 'faculty:director'
        AND `create` = 0
        AND `read` = 1
        AND `update` = 1
        AND `delete` = 0;
        UPDATE `<?php echo AUTH_DATABASE; ?>`.`acl_permissions`
        SET `create` = 0, `update` = 0, `delete` = 0
        WHERE `resource_type` IN ('curriculum', 'objective')
        AND `resource_value` IS NULL
        AND `entity_type` = 'group:role'
        AND `entity_value` = 'staff:pcoordinator'
        AND `create` = 1
        AND `read` = 1
        AND `update` = 1
        AND `delete` = 1;
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
        SET `update` = 1
        WHERE `resource_type` = 'curriculum'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group:role'
        AND `entity_value` = 'faculty:director'
        AND `create` = 0
        AND `read` = 1
        AND `update` = 0
        AND `delete` = 0;
        UPDATE `<?php echo AUTH_DATABASE; ?>`.`acl_permissions`
        SET `create` = 1, `update` = 1, `delete` = 1
        WHERE `resource_type` IN ('curriculum', 'objective')
        AND `resource_value` IS NULL
        AND `entity_type` = 'group:role'
        AND `entity_value` = 'staff:pcoordinator'
        AND `create` = 0
        AND `read` = 1
        AND `update` = 0
        AND `delete` = 0;
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
                  WHERE `resource_type` = 'curriculum'
                  AND `resource_value` IS NULL
                  AND `entity_type` = 'group:role' 
                  AND (
                    (`entity_value` = 'faculty:director' AND `create` = 0 AND `read` = 1 AND `update` = 1 AND `delete` = 0)
                    OR (`entity_value` = 'staff:pcoordinator' AND `create` = 1 AND `read` = 1 AND `update` = 1 AND `delete` = 1)
                  )";
        if ($db->GetRow($query)) {
            return 0;
        }

        return 1;
    }
}
