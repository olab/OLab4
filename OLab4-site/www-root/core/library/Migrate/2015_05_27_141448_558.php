<?php
class Migrate_2015_05_27_141448_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `<?php echo AUTH_DATABASE ?>`.`acl_permissions` (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        ('assessmentcomponent', NULL, 'organisation:group', '8:admin', NULL, 1, 1, 1, 1, 'AssessmentComponent'),
        ('assessmentcomponent', NULL, 'organisation:group:role', '8:staff:pcoordinator', NULL, 1, 1, 1, 1, 'AssessmentComponent'),
        ('assessments', NULL, 'organisation:group:role', '8:staff:pcoordinator', NULL, 1, 1, 1, 1, NULL),
        ('assessments', NULL, 'organisation:group', '8:admin', NULL, 1, 1, 1, 1, NULL);

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
        DELETE FROM `<?php echo AUTH_DATABASE ?>`.`acl_permissions` WHERE `resource_type` = 'assessmentcomponent';
        DELETE FROM `<?php echo AUTH_DATABASE ?>`.`acl_permissions` WHERE `resource_type` = 'assessments';
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
        return -1;
    }
}
