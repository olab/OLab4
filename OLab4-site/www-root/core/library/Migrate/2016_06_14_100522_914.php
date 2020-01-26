<?php
class Migrate_2016_06_14_100522_914 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `<?php echo AUTH_DATABASE ?>`.`acl_permissions` (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`) VALUES ('gradebook', NULL, 'group', 'student', 1, 1, 1, 1, 1, 'GradebookTA');
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
        DELETE FROM `<?php echo AUTH_DATABASE ?>`.`acl_permissions` WHERE `resource_type` = 'gradebook' AND `entity_type` = 'group' AND `entity_value` = 'student' AND `assertion` = 'GradebookTA';
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

        $query = "SELECT * FROM `" . AUTH_DATABASE . "`.`acl_permissions` WHERE `resource_type` = 'gradebook' AND `entity_type` = 'group' AND `entity_value` = 'student' AND `assertion` = 'GradebookTA'";
        $record = $db->GetRow($query);
        if ($record) {
            return 1;
        }
        return 0;
    }
}
