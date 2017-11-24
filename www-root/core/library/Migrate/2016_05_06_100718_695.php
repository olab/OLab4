<?php
class Migrate_2016_05_06_100718_695 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE assessment_grade_form_elements (
            agfelement_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            gairesponse_id int(11) UNSIGNED,
            proxy_id int(12) UNSIGNED,
            score FLOAT,
            PRIMARY KEY (agfelement_id),
            FOREIGN KEY (gairesponse_id) REFERENCES gradebook_assessment_item_responses(gairesponse_id)
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
        DROP TABLE assessment_grade_form_elements;
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
        if ($migration->tableExists(DATABASE_NAME, "assessment_grade_form_elements")) {
            if ($migration->columnExists(DATABASE_NAME, "assessment_grade_form_elements", "agfelement_id")) {
                if ($migration->columnExists(DATABASE_NAME, "assessment_grade_form_elements", "gairesponse_id")) {
                    if ($migration->columnExists(DATABASE_NAME, "assessment_grade_form_elements", "proxy_id")) {
                        if ($migration->columnExists(DATABASE_NAME, "assessment_grade_form_elements", "score")) {
                            return 1;
                        }
                    }
                }
            }
        }

        return 0;
    }
}
