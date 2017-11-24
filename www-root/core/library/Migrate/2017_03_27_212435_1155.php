<?php
class Migrate_2017_03_27_212435_1155 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        UPDATE `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` SET `entity_type` = 'group:role', `entity_value` = 'staff:admin' WHERE `resource_type` = 'assessments' AND `entity_type` = 'role' AND `entity_value` = 'admin' LIMIT 1;
        INSERT INTO `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` (`permission_id`, `resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        (NULL, 'assessments', NULL, 'group', 'student', 1, NULL, 1, NULL, NULL, NULL),
        (NULL, 'assessments', NULL, 'group', 'resident', 1, NULL, 1, NULL, NULL, NULL),
        (NULL, 'assessments', NULL, 'group', 'staff', 1, NULL, 1, NULL, NULL, NULL),
        (NULL, 'assessments', NULL, 'group', 'faculty', 1, NULL, 1, NULL, NULL, NULL);
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
        UPDATE `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` SET `entity_type` = 'role', `entity_value` = 'admin' WHERE `resource_type` = 'assessments' AND `entity_type` = 'group:role' AND `entity_value` = 'staff:admin' LIMIT 1;
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'assessments' AND `entity_type` = 'group' AND `entity_value` = 'student' LIMIT 1;
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'assessments' AND `entity_type` = 'group' AND `entity_value` = 'resident' LIMIT 1;
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'assessments' AND `entity_type` = 'group' AND `entity_value` = 'staff' LIMIT 1;
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'assessments' AND `entity_type` = 'group' AND `entity_value` = 'faculty' LIMIT 1;
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

        $query = "SELECT *
                  FROM `" . AUTH_DATABASE . "`.`acl_permissions`
                  WHERE `resource_type` = 'assessments'
                  AND `entity_type` = 'group:role'
                  AND `entity_value` = 'staff:admin'";
        $result = $db->GetRow($query);
        if ($result) {
            $query = "SELECT COUNT(*) AS `total`
                      FROM `" . AUTH_DATABASE . "`.`acl_permissions`
                      WHERE `resource_type` = 'assessments'
                      AND `entity_type` = 'group'
                      AND `entity_value` IN ('student', 'resident', 'staff', 'faculty')";
            $result = $db->GetOne($query);
            if ($result && ($result >= 4)) {
                return 1;
            }
        }

        return 0;
    }
}
