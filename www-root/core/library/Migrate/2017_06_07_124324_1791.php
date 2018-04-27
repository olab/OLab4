<?php
class Migrate_2017_06_07_124324_1791 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `exam_creation_history`
        MODIFY COLUMN `action` enum('exam_add','exam_edit','exam_delete','exam_copy','exam_move','exam_settings_edit','exam_element_add','exam_element_edit','exam_element_delete','exam_element_group_add','exam_element_group_edit','exam_element_group_delete','exam_element_order','exam_element_points','post_exam_add','post_exam_edit','post_exam_delete','adjust_score','delete_adjust_score','reopen_progress','delete_progress','report_add','report_edit','report_delete') NOT NULL;
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
        ALTER TABLE `exam_creation_history`
        MODIFY COLUMN `action` enum('exam_add','exam_edit','exam_delete','exam_copy','exam_settings_edit','exam_element_add','exam_element_edit','exam_element_delete','exam_element_group_add','exam_element_group_edit','exam_element_group_delete','exam_element_order','exam_element_points','post_exam_add','post_exam_edit','post_exam_delete','adjust_score','delete_adjust_score','reopen_progress','delete_progress','report_add','report_edit','report_delete') NOT NULL;
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
        $query = "  SELECT `COLUMN_TYPE`
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = " . DATABASE_NAME . "
                    AND TABLE_NAME = 'exam_creation_history'
                    AND COLUMN_NAME = 'action'";

        $row = $db->GetRow($query);
        if ($row && $row["COLUMN_TYPE"] == "enum('exam_add','exam_edit','exam_delete','exam_copy','exam_move','exam_settings_edit','exam_element_add','exam_element_edit','exam_element_delete','exam_element_group_add','exam_element_group_edit','exam_element_group_delete','exam_element_order','exam_element_points','post_exam_add','post_exam_edit','post_exam_delete','adjust_score','delete_adjust_score','reopen_progress','delete_progress','report_add','report_edit','report_delete')" ) {
            return 1;
        }
        return 0;
    }
}
