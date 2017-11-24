<?php
class Migrate_2015_10_07_140708_607 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo AUTH_DATABASE ?>`.`user_data` ADD COLUMN `date_of_birth` bigint(64) AFTER `lastname`;
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
        ALTER TABLE `<?php echo AUTH_DATABASE ?>`.`user_data` DROP COLUMN `date_of_birth`;
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
        global $db;
        $query = "SHOW COLUMNS FROM `" . AUTH_DATABASE . "`.`user_data` LIKE 'date_of_birth'";
        $column = $db->GetRow($query);
        if ($column) {
            return 1;
        }
        return 0;
    }
}
