<?php
class Migrate_2016_09_21_110533_494 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo AUTH_DATABASE;?>`.`user_data` ADD COLUMN `suffix_gen` varchar(15) NOT NULL DEFAULT '' AFTER `prefix`;
        ALTER TABLE `<?php echo AUTH_DATABASE;?>`.`user_data` ADD COLUMN `suffix_post_nominal` varchar(15) NOT NULL DEFAULT '' AFTER `prefix`;
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
        ALTER TABLE `<?php echo AUTH_DATABASE;?>`.`user_data` DROP COLUMN `suffix_gen`;
        ALTER TABLE `<?php echo AUTH_DATABASE;?>`.`user_data` DROP COLUMN `suffix_post_nominal`;
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
        if ($migration->columnExists(AUTH_DATABASE, "user_data", "suffix_gen")) {
            if ($migration->columnExists(AUTH_DATABASE, "user_data", "suffix_post_nominal")) {
                return 1;
            }
        }
        return 0;
    }
}
