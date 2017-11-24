<?php
class Migrate_2016_07_21_102058_1443 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `exam_posts`
        ADD COLUMN `release_incorrect_responses` int(1) DEFAULT '0' AFTER `release_feedback`;
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
        ALTER TABLE `exam_posts`
        DROP COLUMN `release_incorrect_responses`;
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
        $query1 = "
            SELECT 1
            FROM `INFORMATION_SCHEMA`.`COLUMNS`
            WHERE `TABLE_SCHEMA` = ".$db->qstr(DATABASE_NAME)."
            AND `TABLE_NAME` = 'exam_posts'
            AND `COLUMN_NAME` = 'release_incorrect_responses'";

        if ($db->GetOne($query1)) {
            return 1;
        }

        return 0;
    }
}
