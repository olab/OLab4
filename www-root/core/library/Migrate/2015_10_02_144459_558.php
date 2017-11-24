<?php
class Migrate_2015_10_02_144459_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'assessor' AND `assertion` IS NULL;

        INSERT INTO `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        ('assessor', NULL, NULL, NULL, 1, 1, 1, 1, NULL, 'Assessor'),
        ('assessmentprogress', NULL, NULL, NULL, 1, 1, 1, 1, 1, 'AssessmentProgress'),
        ('assessmentresult', NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, 'AssessmentResult');
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
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'assessor' AND `assertion` = 'Assessor';
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'assessmentprogress' AND `assertion` = 'AssessmentProgress';
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'assessmentresult' AND `assertion` = 'AssessmentResult';
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

        $query = "SELECT * FROM `".AUTH_DATABASE."`.`acl_permission` WHERE `resource_type` = 'assessor' AND `assertion` = 'Assessor'";
        $first_record = $db->GetRow($query);
        if ($first_record) {
            $query = "SELECT * FROM `".AUTH_DATABASE."`.`acl_permission` WHERE `resource_type` = 'assessmentprogress' AND `assertion` = 'AssessmentProgress'";
            $second_record = $db->GetRow($query);
            if ($second_record) {
                $query = "SELECT * FROM `".AUTH_DATABASE."`.`acl_permission` WHERE `resource_type` = 'assessmentresult' AND `assertion` = 'AssessmentResult'";
                $third_record = $db->GetRow($query);
                if ($third_record) {
                    return 1;
                }
            }
        }

        return 0;
    }
}
