<?php
class Migrate_2016_10_14_102857_1583 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `global_lu_objectives` MODIFY `objective_name` VARCHAR(240) NOT NULL DEFAULT '';
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
        ALTER TABLE `global_lu_objectives` MODIFY `objective_name` VARCHAR(60) NOT NULL DEFAULT '';
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
        $column = $migration->fieldMetadata(DATABASE_NAME, "global_lu_objectives", "objective_name");
        if ($column && $column["Type"] == "varchar(240)") {
            return 1;
        }
        return 0;
    }
}
