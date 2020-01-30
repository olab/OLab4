<?php
class Migrate_2016_10_20_145751_1126 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo AUTH_DATABASE; ?>`.`user_data` CHANGE `username` `username` VARCHAR(255);
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
        ALTER TABLE `<?php echo AUTH_DATABASE; ?>`.`user_data` CHANGE `username` `username` VARCHAR(25);
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

        $meta = $migration->fieldMetadata(AUTH_DATABASE, "user_data", "username");
        if (!empty($meta)) {
            if (isset($meta["Type"]) && $meta["Type"]) {
                if ($meta["Type"] == "varchar(255)") {
                    return 1;
                }
            }
        }

        return 0;
    }
}
