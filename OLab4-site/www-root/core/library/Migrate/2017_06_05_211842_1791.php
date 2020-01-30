<?php
class Migrate_2017_06_05_211842_1791 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;
        $query = " SELECT * FROM `" . DATABASE_NAME . "`.`exam_bank_folders`
                    WHERE `folder_type` = \"exam\"";
        $folders = $db->GetAll($query);
        $folder_id = 0;
        if ($folders) {
            $folder_id = $folders[0]["folder_id"];
        }
        $this->record();
        ?>
        ALTER TABLE `exams`
        ADD COLUMN `folder_id` int(12) DEFAULT '<?php echo $folder_id; ?>' AFTER `exam_id`;
        ALTER TABLE `exams`
        MODIFY COLUMN `folder_id` int(12) DEFAULT '0' AFTER `exam_id`;
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
        ALTER TABLE `exams`
        DROP COLUMN `folder_id`;
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
        if ($migration->indexExists(DATABASE_NAME, "exams", "folder_id")) {
            return 1;
        }

        return 0;
    }
}
