<?php
class Migrate_2017_05_05_112829_1855 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`portfolio_entries` ADD COLUMN `is_assessable` TINYINT(1) UNSIGNED DEFAULT 1 AFTER `flagged_date`;
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`portfolio_entries` ADD COLUMN `is_assessable_set_by` INT(10) UNSIGNED AFTER `is_assessable`;
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`portfolio_entries` ADD COLUMN `is_assessable_set_date` BIGINT(64) UNSIGNED AFTER `is_assessable_set_by`;
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
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`portfolio_entries` DROP `is_assessable`;
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`portfolio_entries` DROP `is_assessable_set_by`;
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`portfolio_entries` DROP `is_assessable_set_date`;
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
        if ($migration->columnExists(DATABASE_NAME, "portfolio_entries", "is_assessable")
         && $migration->columnExists(DATABASE_NAME, "portfolio_entries", "is_assessable_set_by")
         && $migration->columnExists(DATABASE_NAME, "portfolio_entries", "is_assessable_set_date")) {
            return 1;
        }

        return 0;
    }
}
