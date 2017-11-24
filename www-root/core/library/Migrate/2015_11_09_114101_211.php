<?php
class Migrate_2015_11_09_114101_211 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `course_objectives` ADD FULLTEXT `ft_objective_details` (`objective_details`);
        ALTER TABLE `event_objectives` ADD FULLTEXT `ft_objective_details` (`objective_details`);
        ALTER TABLE `global_lu_objectives` ADD FULLTEXT `ft_objective_search` (`objective_code`, `objective_name`, `objective_description`);
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
        ALTER TABLE `course_objectives` DROP INDEX `ft_objective_details`;
        ALTER TABLE `event_objectives` DROP INDEX `ft_objective_details`;
        ALTER TABLE `global_lu_objectives` DROP INDEX `ft_objective_search`;
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
        if ($migration->indexExists(DATABASE_NAME, "course_objectives", "ft_objective_details")) {
            if ($migration->indexExists(DATABASE_NAME, "event_objectives", "ft_objective_details")) {
                if ($migration->indexExists(DATABASE_NAME, "global_lu_objectives", "ft_objective_search")) {
                    return 1;
                }
            }
        }

        return 0;
    }
}
