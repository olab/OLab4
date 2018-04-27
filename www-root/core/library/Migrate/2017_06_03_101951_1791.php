<?php
class Migrate_2017_06_03_101951_1791 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        RENAME TABLE
        exam_question_bank_folders TO exam_bank_folders,
        exam_question_bank_folder_organisations TO exam_bank_folder_organisations,
        exam_question_bank_folder_authors TO exam_bank_folder_authors,
        exam_lu_question_bank_folder_images TO exam_lu_bank_folder_images;
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
        RENAME TABLE
        exam_bank_folders TO exam_question_bank_folders,
        exam_bank_folder_organisations TO exam_question_bank_folder_organisations,
        exam_bank_folder_authors TO exam_question_bank_folder_authors,
        exam_lu_bank_folder_images TO exam_lu_question_bank_folder_images;
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
        if ($migration->tableExists(DATABASE_NAME, "exam_bank_folders")) {
            if ($migration->tableExists(DATABASE_NAME, "exam_bank_folder_organisations")) {
                if ($migration->tableExists(DATABASE_NAME, "exam_bank_folder_authors")) {
                    if ($migration->tableExists(DATABASE_NAME, "exam_lu_bank_folder_images")) {
                        return 1;
                    }
                }
            }
        }

        return 0;
    }
}
