<?php
class Migrate_2017_07_17_203832_191 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE INDEX post_index_1
        ON `exam_posts` (`exam_id`, `post_id`, `deleted_date`);

        CREATE INDEX ee_index_1
        ON `exam_elements` (`exam_id`, `deleted_date`);

        CREATE INDEX e_index_1
        ON `exams` (`folder_id`);

        CREATE INDEX ebf_index_1
        ON `exam_bank_folders` (`parent_folder_id`);

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
        ALTER TABLE exam_posts DROP INDEX post_index_1;
        ALTER TABLE exam_elements DROP INDEX ee_index_1;
        ALTER TABLE exams DROP INDEX e_index_1;
        ALTER TABLE exam_bank_folders DROP INDEX ebf_index_1;
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
        $column = $migration->indexExists(DATABASE_NAME, "exam_posts", "post_index_1");
        if ($column) {
            $column = $migration->indexExists(DATABASE_NAME, "exam_elements", "ee_index_1");
            if ($column) {
                $column = $migration->indexExists(DATABASE_NAME, "exams", "e_index_1");
                if ($column) {
                    $column = $migration->indexExists(DATABASE_NAME, "exam_bank_folders", "ebf_index_1");
                    if ($column) {
                        return 1;
                    }
                }
            }
        }
        return 0;


    }
}
