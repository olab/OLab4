<?php
class Migrate_2016_06_30_100114_971 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo AUTH_DATABASE; ?>`.`departments` ADD `department_code` VARCHAR(128);
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
        ALTER TABLE `<?php echo AUTH_DATABASE; ?>`.`departments` DROP COLUMN `department_code`;
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
        if ($migration->columnExists(AUTH_DATABASE, "departments", "department_code")) {
            return 1;
        }

        return 0;
    }
}
