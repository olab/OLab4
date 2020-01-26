<?php
class Migrate_2017_08_08_135144_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_academic_advisor_meetings`
        ADD `deleted_by` int(12) DEFAULT NULL;

        INSERT INTO `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        ('cbmemeeting', NULL, 'group', 'student', 1, NULL, 1, NULL, NULL, 'MeetingOwner');
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
        ALTER TABLE `cbl_academic_advisor_meetings`
        DROP COLUMN `deleted_by`;

        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'cbmemeeting' AND `assertion` = 'MeetingOwner';
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
        $migration = new Models_Migration();
        if ($migration->columnExists(DATABASE_NAME, "cbl_academic_advisor_meetings", "deleted_by")) {
            $query = "SELECT * FROM `".AUTH_DATABASE."`.`acl_permissions` WHERE `resource_type` = 'cbmemeeting' AND `assertion` = 'MeetingOwner'";
            $result = $db->GetAll($query);
            if ($result) {
                return 1;
            }
        }
        return 0;
    }
}
