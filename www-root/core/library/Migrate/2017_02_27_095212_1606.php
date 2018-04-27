<?php
class Migrate_2017_02_27_095212_1606 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `exam_creation_history` (
            `exam_history_id` int(12) NOT NULL AUTO_INCREMENT,
            `exam_id` int(12) DEFAULT '0',
            `proxy_id` int(12) NOT NULL DEFAULT '0',
            `action` enum('exam_add', 'exam_edit', 'exam_delete', 'exam_copy', 'exam_settings_edit', 'exam_element_add', 'exam_element_edit', 'exam_element_delete','exam_element_group_add', 'exam_element_group_edit', 'exam_element_group_delete', 'exam_element_order', 'exam_element_points', 'post_exam_add', 'post_exam_edit', 'post_exam_delete', 'adjust_score', 'delete_adjust_score', 'reopen_progress', 'delete_progress', 'report_add', 'report_edit', 'report_delete') NOT NULL,
            `action_resource_id` int(12) DEFAULT NULL,
            `secondary_action` text DEFAULT NULL,
            `secondary_action_resource_id` int(12) DEFAULT NULL,
            `history_message` text DEFAULT NULL,
            `timestamp` bigint(64) NOT NULL DEFAULT '0',
        PRIMARY KEY (`exam_history_id`),
        KEY `timestamp` (`timestamp`),
        KEY `exam_id` (`exam_id`),
        KEY `proxy_id` (`proxy_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
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
        DROP TABLE `exam_creation_history`;
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
        $migration = new Models_Migration();

        if ($migration && $migration->tableExists(DATABASE_NAME, "exam_creation_history")) {
            return 1;
        } else {
            return 0;
        }
    }
}
