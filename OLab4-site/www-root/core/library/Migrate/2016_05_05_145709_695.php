<?php
class Migrate_2016_05_05_145709_695 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        // First, change the assessments table to innodb (allowing the use of foreign keys), then create new table
        ?>
        ALTER TABLE assessments ENGINE = InnoDB;
        CREATE TABLE gradebook_assessment_form_elements (
            gafelement_id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            assessment_id int(10) UNSIGNED, 
            afelement_id int(11) UNSIGNED,
            weight FLOAT,
            PRIMARY KEY (gafelement_id),
            FOREIGN KEY (assessment_id) REFERENCES assessments(assessment_id),
            FOREIGN KEY (afelement_id) REFERENCES cbl_assessment_form_elements(afelement_id)
        )
        ENGINE=INNODB;
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
        DROP TABLE gradebook_assessment_form_elements;
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
        if ($migration->tableExists(DATABASE_NAME, "gradebook_assessment_form_elements")) {
            if ($migration->columnExists(DATABASE_NAME, "gradebook_assessment_form_elements", "gafelement_id")) {
                if ($migration->columnExists(DATABASE_NAME, "gradebook_assessment_form_elements", "assessment_id")) {
                    if ($migration->columnExists(DATABASE_NAME, "gradebook_assessment_form_elements", "afelement_id")) {
                        if ($migration->columnExists(DATABASE_NAME, "gradebook_assessment_form_elements", "weight")) {
                            return 1;
                        }
                    }
                }
            }
        }

        return 0;
    }
}
