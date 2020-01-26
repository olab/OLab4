<?php
class Migrate_2016_06_10_203558_900 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO  `<?php echo AUTH_DATABASE;?>`.`acl_permissions` (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        ('exam', NULL, 'group:role', 'faculty:director', 1, NULL, NULL, 1, 1, 'ExamOwner'),
        ('exam', NULL, 'group:role', 'faculty:admin', 1, NULL, NULL, 1, 1, 'ExamOwner'),
        ('exam', NULL, 'group:role', 'staff:pcoordinator', 1, NULL, NULL, 1, 1, 'ExamOwner'),
        ('exam', NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, 'NotGuest'),
        ('exam', NULL, 'group', 'staff', 1, NULL, 1, NULL, NULL, NULL),
        ('exam', NULL, 'group', 'faculty', 1, NULL, 1, NULL, NULL, NULL),
        ('exam', NULL, 'group:role', 'staff:admin', 1, 1, 1, NULL, NULL, NULL),
        ('exam', NULL, 'group:role', 'staff:pcoordinator', 1, 1, 1, NULL, NULL, NULL),
        ('exam', NULL, 'group:role', 'faculty:admin', 1, 1, 1, NULL, NULL, NULL),
        ('exam', NULL, 'group:role', 'faculty:director', 1, 1, 1, NULL, NULL, NULL),
        ('exam', NULL, 'group:role', 'staff:admin', 1, NULL, NULL, 1, 1, 'ExamOwner'),
        ('examdashboard', NULL, 'group', 'staff', 1, NULL, 1, NULL, NULL, NULL),
        ('examdashboard', NULL, 'group', 'faculty', 1, NULL, 1, NULL, NULL, NULL),
        ('examfolder', NULL, 'group:role', 'staff:admin', 1, NULL, NULL, 1, 1, 'ExamFolderOwner'),
        ('examfolder', NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, 'NotGuest'),
        ('examfolder', NULL, 'group:role', 'faculty:admin', 1, NULL, NULL, 1, 1, 'ExamFolderOwner'),
        ('examfolder', NULL, 'group:role', 'staff:pcoordinator', 1, NULL, NULL, 1, 1, 'ExamFolderOwner'),
        ('examfolder', NULL, 'group:role', 'faculty:director', 1, NULL, NULL, 1, 1, 'ExamFolderOwner'),
        ('examfolder', NULL, 'group', 'staff', 1, NULL, 1, NULL, NULL, NULL),
        ('examfolder', NULL, 'group', 'faculty', 1, NULL, 1, NULL, NULL, NULL),
        ('examgradefnb', NULL, 'group:role', 'faculty:director', 1, 1, 1, 1, 1, NULL),
        ('examgradefnb', NULL, 'group:role', 'staff:pcoordinator', 1, 1, 1, 1, 1, NULL),
        ('examgradefnb', NULL, 'group:role', 'staff:admin', 1, 1, 1, 1, 1, NULL),
        ('examquestion', NULL, 'group:role', 'medtech:admin', 1, 1, 1, 1, 1, NULL),
        ('examquestion', NULL, 'group:role', 'staff:pcoordinator', 1, NULL, NULL, 1, 1, 'ExamQuestionOwner'),
        ('examquestion', NULL, 'group:role', 'staff:admin', 1, NULL, NULL, 1, 1, 'ExamQuestionOwner'),
        ('examquestion', NULL, 'group:role', 'staff:admin', 1, 1, 1, NULL, NULL, NULL),
        ('examquestion', NULL, 'group:role', 'staff:pcoordinator', 1, 1, 1, NULL, NULL, NULL),
        ('examquestion', NULL, 'group:role', 'faculty:admin', 1, 1, 1, NULL, NULL, NULL),
        ('examquestion', NULL, 'group:role', 'faculty:director', 1, 1, 1, NULL, NULL, NULL),
        ('examquestion', NULL, 'group:role', 'faculty:admin', 1, NULL, NULL, 1, 1, 'ExamQuestionOwner'),
        ('examquestion', NULL, 'group', 'faculty', 1, NULL, 1, NULL, NULL, NULL),
        ('examquestion', NULL, 'group:role', 'faculty:director', 1, NULL, NULL, 1, 1, 'ExamQuestionOwner'),
        ('examquestion', NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, 'NotGuest'),
        ('examquestion', NULL, 'group', 'staff', 1, NULL, 1, NULL, NULL, NULL),
        ('examquestiongroup', NULL, 'group:role', 'faculty:director', 1, NULL, NULL, 1, 1, 'ExamQuestionGroupOwner'),
        ('examquestiongroup', NULL, 'group:role', 'faculty:admin', 1, NULL, NULL, 1, 1, 'ExamQuestionGroupOwner'),
        ('examquestiongroup', NULL, 'group:role', 'staff:pcoordinator', 1, NULL, NULL, 1, 1, 'ExamQuestionGroupOwner'),
        ('examquestiongroup', NULL, 'group:role', 'staff:admin', 1, NULL, NULL, 1, 1, 'ExamQuestionGroupOwner'),
        ('examquestiongroup', NULL, 'group:role', 'faculty:director', 1, 1, 1, NULL, NULL, NULL),
        ('examquestiongroup', NULL, 'group:role', 'faculty:admin', 1, 1, 1, NULL, NULL, NULL),
        ('examquestiongroup', NULL, 'group:role', 'staff:pcoordinator', 1, 1, 1, NULL, NULL, NULL),
        ('examquestiongroup', NULL, 'group:role', 'staff:admin', 1, 1, 1, NULL, NULL, NULL),
        ('examquestiongroupindex', NULL, NULL, NULL, 1, NULL, 0, NULL, NULL, NULL),
        ('examquestiongroupindex', NULL, 'role', 'admin', 1, NULL, 1, NULL, NULL, NULL);

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
        WHERE `resource_type` = 'exam'
        OR `resource_type` = 'examdashboard'
        OR `resource_type` = 'examfolder'
        OR `resource_type` = 'examdashboard'
        OR `resource_type` = 'examgradefnb'
        OR `resource_type` = 'examquestion'
        OR `resource_type` = 'examquestiongroup'
        OR `resource_type` = 'examquestiongroupindex';
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
        $query = "  SELECT `resource_type`
                    FROM `" . AUTH_DATABASE . "`.`acl_permissions`
                    WHERE `resource_type` = 'exam'";

        if ($db->GetAll($query)) {
            return 1;
        } else {
            return 0;
        }
    }
}
