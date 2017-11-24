<?php
class Migrate_2016_03_28_211819_726 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo AUTH_DATABASE; ?>`.`user_data` ADD COLUMN `uuid` varchar(36) DEFAULT NULL AFTER `clinical`;
        UPDATE `<?php echo AUTH_DATABASE; ?>`.`user_data` SET `uuid` = (SELECT UUID());
        ALTER TABLE `<?php echo AUTH_DATABASE; ?>`.`user_data` ADD INDEX `idx_uuid` (`uuid`);
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
        ALTER TABLE `<?php echo AUTH_DATABASE; ?>`.`user_data` DROP `uuid`;
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
        if ($migration->columnExists(AUTH_DATABASE, "user_data", "uuid")) {
            if ($migration->indexExists(AUTH_DATABASE, "user_data", "idx_uuid")) {
                return 1;
            }
        }

        return 0;
    }
}
