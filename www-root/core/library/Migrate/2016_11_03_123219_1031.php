<?php
class Migrate_2016_11_03_123219_1031 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        UPDATE `<?php echo AUTH_DATABASE ?>`.`acl_permissions` SET `entity_type` = 'role', `entity_value` = 'admin'
        WHERE (`resource_type` = 'assessmentcomponent' OR `resource_type` = 'assessments') AND `entity_type` = 'organisation:group' AND `entity_value` = '8:admin';
        UPDATE `<?php echo AUTH_DATABASE ?>`.`acl_permissions` SET `entity_type` = 'group:role', `entity_value` = 'staff:pcoordinator'
        WHERE (`resource_type` = 'assessmentcomponent' OR `resource_type` = 'assessments') AND `entity_type` = 'organisation:group:role' AND `entity_value` = '8:staff:pcoordinator';
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
        UPDATE `<?php echo AUTH_DATABASE ?>`.`acl_permissions` SET `entity_type` = 'organisation:group', `entity_value` = '8:admin'
        WHERE (`resource_type` = 'assessmentcomponent' OR `resource_type` = 'assessments') AND `entity_type` = 'role' AND `entity_value` = 'admin';
        UPDATE `<?php echo AUTH_DATABASE ?>`.`acl_permissions` SET `entity_type` = 'organisation:group:role', `entity_value` = '8:staff:pcoordinator'
        WHERE (`resource_type` = 'assessmentcomponent' OR `resource_type` = 'assessments') AND `entity_type` = 'group:role' AND `entity_value` = 'staff:pcoordinator';
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
