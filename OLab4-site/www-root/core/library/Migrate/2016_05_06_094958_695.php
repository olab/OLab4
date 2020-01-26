<?php
class Migrate_2016_05_06_094958_695 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE gradebook_assessment_item_responses (
            gairesponse_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            assessment_id int(11) UNSIGNED,
            iresponse_id int(11) UNSIGNED,
            score FLOAT,
            PRIMARY KEY (gairesponse_id),
            FOREIGN KEY (assessment_id) REFERENCES assessments(assessment_id),
            FOREIGN KEY (iresponse_id) REFERENCES cbl_assessments_lu_item_responses(iresponse_id)
        )
        ENGINE=InnoDB;
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
        DROP TABLE gradebook_assessment_item_responses;
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
        if ($migration->tableExists(DATABASE_NAME, "gradebook_assessment_item_responses")) {
            if ($migration->columnExists(DATABASE_NAME, "gradebook_assessment_item_responses", "gairesponse_id")) {
                if ($migration->columnExists(DATABASE_NAME, "gradebook_assessment_item_responses", "assessment_id")) {
                    if ($migration->columnExists(DATABASE_NAME, "gradebook_assessment_item_responses", "iresponse_id")) {
                        if ($migration->columnExists(DATABASE_NAME, "gradebook_assessment_item_responses", "score")) {
                            return 1;
                        }
                    }
                }
            }
        }

        return 0;
    }
}
