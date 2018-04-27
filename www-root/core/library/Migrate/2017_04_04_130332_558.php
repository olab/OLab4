<?php
class Migrate_2017_04_04_130332_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `global_lu_objective_sets` (`title`, `description`, `shortname`, `start_date`, `end_date`, `standard`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`)
        VALUES ('Contextual Variable Responses', 'Contextual Variable Responses', 'contextual_variable_responses', NULL, NULL, 0,  UNIX_TIMESTAMP(NOW()), 1, NULL, NULL, NULL);
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
        DELETE FROM `global_lu_objective_sets`
        WHERE `shortname` = 'contextual_variable_responses';
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
        $objective_set_model = new Models_ObjectiveSet();
        $contextual_variable = $objective_set_model->fetchRowByShortname("contextual_variable_responses");
        if ($contextual_variable) {
            return 1;
        }
        return 0;
    }
}
