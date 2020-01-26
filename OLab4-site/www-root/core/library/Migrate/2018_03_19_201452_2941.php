<?php
class Migrate_2018_03_19_201452_2941 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` (`permission_id`, `resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        (NULL, 'assessments', NULL, 'group:role', 'faculty:director', 1, 1, 1, 1, 1, NULL),
        (NULL, 'assessmentcomponent', NULL, 'group:role', 'faculty:director', 1, 1, 1, 1, 1, 'AssessmentComponent');
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
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions`
        WHERE `resource_type` IN ('assessments', 'assessmentcomponent')
        AND `resource_value` IS NULL
        AND `entity_type` = 'group:role'
        AND `entity_value` = 'faculty:director'
        AND `create` = 1
        AND `read` = 1
        AND `update` = 1
        AND `delete` = 1;
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
                  WHERE `resource_type` IN ('assessments', 'assessmentcomponent')
                  AND `resource_value` IS NULL
                  AND `entity_type` LIKE '%group:role' 
                  AND `entity_value` LIKE '%faculty:director'
                  AND `create` = 1
                  AND `read` = 1
                  AND `update` = 1
                  AND `delete` = 1";
        if ($db->GetRow($query)) {
            return 1;
        }

        return 0;
    }
}
