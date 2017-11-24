<?php
class Migrate_2016_05_24_105810_695 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `assessment_grade_form_elements` 
        ADD COLUMN `assessment_id` int(11) UNSIGNED NULL AFTER `gairesponse_id`,
        ADD FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (assessment_id);
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        global $db;

        $this->record();

        $constraints_query = "SELECT CONSTRAINT_NAME
                                FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE`
                                WHERE `TABLE_SCHEMA` = '" . DATABASE_NAME . "'
                                AND `TABLE_NAME` = 'assessment_grade_form_elements' 
                                AND `referenced_column_name` = 'assessment_id'";
        $constraint = $db->getRow($constraints_query);
        if ($constraint && $constraint["CONSTRAINT_NAME"]) {
            ?>
            ALTER TABLE `assessment_grade_form_elements`
            DROP FOREIGN KEY `<?php echo $constraint["CONSTRAINT_NAME"]; ?>`,
            DROP COLUMN `assessment_id`;
            <?php
        }
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
        if ($migration->columnExists(DATABASE_NAME, "assessment_grade_form_elements", "assessment_id")) {
            if ($migration->indexExists(DATABASE_NAME, "assessment_grade_form_elements", "assessment_id")) {
                return 1;
            }
        }

        return 0;
    }
}
