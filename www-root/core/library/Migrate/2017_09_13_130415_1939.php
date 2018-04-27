<?php
class Migrate_2017_09_13_130415_1939 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `<?php echo AUTH_DATABASE;?>`.`acl_permissions` (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        ('studentadmin', NULL, 'group', 'student', 1, NULL, 1, NULL, NULL, 'StudentAdmin'),
        ('examdashboard', NULL, 'group', 'student', 1, NULL, 1, NULL, NULL, 'StudentAdmin'),
        ('examfolder', NULL, 'group', 'student', 1, NULL, 1, NULL, NULL, 'StudentAdmin'),
        ('examfolder', NULL, 'group', 'student', 1, NULL, NULL, 1, NULL, 'ExamFolderOwner&StudentAdmin'),

        ('examquestionindex', NULL, 'group', 'faculty', 1, NULL, 1, NULL, NULL, NULL),
        ('examquestionindex', NULL, 'group', 'staff', 1, NULL, 1, NULL, NULL, NULL),
        ('examquestionindex', NULL, 'group', 'student', 1, NULL, 1, NULL, NULL, 'StudentAdmin'),
        ('examquestion', NULL, 'group', 'student', 1, 1, 1, NULL, NULL, 'StudentAdmin'),
        ('examquestion', NULL, 'group', 'student', 1, NULL, NULL, 1, 1, 'ExamQuestionOwner&StudentAdmin');

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
        WHERE `resource_type` = 'studentadmin'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group'
        AND `entity_value` = 'student'
        AND `app_id` = 1
        AND `create` IS NULL
        AND `read` = 1
        AND `update` IS NULL
        AND `delete` IS NULL
        AND `assertion` = 'StudentAdmin';

        DELETE FROM `<?php echo AUTH_DATABASE;?>`.`acl_permissions`
        WHERE `resource_type` = 'examdashboard'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group'
        AND `entity_value` = 'student'
        AND `app_id` = 1
        AND `create` IS NULL
        AND `read` = 1
        AND `update` IS NULL
        AND `delete` IS NULL
        AND `assertion` = 'StudentAdmin';

        DELETE FROM `<?php echo AUTH_DATABASE;?>`.`acl_permissions`
        WHERE `resource_type` = 'examfolder'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group'
        AND `entity_value` = 'student'
        AND `app_id` = 1
        AND `create` IS NULL
        AND `read` = 1
        AND `update` IS NULL
        AND `delete` IS NULL
        AND `assertion` = 'StudentAdmin';

        DELETE FROM `<?php echo AUTH_DATABASE;?>`.`acl_permissions`
        WHERE `resource_type` = 'examfolder'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group'
        AND `entity_value` = 'student'
        AND `app_id` = 1
        AND `create` IS NULL
        AND `read` IS NULL
        AND `update` = 1
        AND `delete` IS NULL
        AND `assertion` = 'ExamFolderOwner&StudentAdmin';

        DELETE FROM `<?php echo AUTH_DATABASE;?>`.`acl_permissions`
        WHERE `resource_type` = 'examquestionindex'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group'
        AND `entity_value` = 'faculty'
        AND `app_id` = 1
        AND `create` IS NULL
        AND `read` = 1
        AND `update` IS NULL
        AND `delete` IS NULL
        AND `assertion` IS NULL;

        DELETE FROM `<?php echo AUTH_DATABASE;?>`.`acl_permissions`
        WHERE `resource_type` = 'examquestionindex'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group'
        AND `entity_value` = 'staff'
        AND `app_id` = 1
        AND `create` IS NULL
        AND `read` = 1
        AND `update` IS NULL
        AND `delete` IS NULL
        AND `assertion` IS NULL;

        DELETE FROM `<?php echo AUTH_DATABASE;?>`.`acl_permissions`
        WHERE `resource_type` = 'examquestionindex'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group'
        AND `entity_value` = 'student'
        AND `app_id` = 1
        AND `create` IS NULL
        AND `read` = 1
        AND `update` IS NULL
        AND `delete` IS NULL
        AND `assertion` = 'StudentAdmin';

        DELETE FROM `<?php echo AUTH_DATABASE;?>`.`acl_permissions`
        WHERE `resource_type` = 'examquestion'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group'
        AND `entity_value` = 'student'
        AND `app_id` = 1
        AND `create` = 1
        AND `read` = 1
        AND `update` IS NULL
        AND `delete` IS NULL
        AND `assertion` = 'StudentAdmin';

        DELETE FROM `<?php echo AUTH_DATABASE;?>`.`acl_permissions`
        WHERE `resource_type` = 'examquestion'
        AND `resource_value` IS NULL
        AND `entity_type` = 'group'
        AND `entity_value` = 'student'
        AND `app_id` IS NULL
        AND `create` IS NULL
        AND `read` = 1
        AND `update` = 1
        AND `delete` = 1
        AND `assertion` = 'ExamQuestionOwner&StudentAdmin';

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
                  WHERE `resource_type` = 'studentadmin'
                  AND `resource_value` IS NULL
                  AND `entity_type` = 'group'
                  AND `entity_value` = 'student'
                  AND `app_id` = 1
                  AND `create` IS NULL
                  AND `read` = 1
                  AND `update` IS NULL
                  AND `delete` IS NULL
                  AND `assertion` = 'StudentAdmin';";
        return (int)$db->GetOne($query);
    }
}
