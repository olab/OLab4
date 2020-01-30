<?php
class Migrate_2017_05_17_105328_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo AUTH_DATABASE ?>`.`user_data` CHANGE `pin` `pin` VARCHAR(40)  NULL  DEFAULT NULL;
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
        ALTER TABLE `<?php echo AUTH_DATABASE ?>`.`user_data` CHANGE `pin` `pin` VARCHAR(32)  NULL  DEFAULT NULL;
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
        $meta = $migration->fieldMetadata(AUTH_DATABASE, "user_data", "pin");
        if ($meta["Type"] != "varchar(40)") {
            return 0;
        }
        return 1;
    }
}
