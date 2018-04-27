<?php
class Migrate_2016_08_02_162650_1023 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`course_units` ADD COLUMN `unit_order` INT(12) NOT NULL DEFAULT 0 AFTER `week_id`;
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
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`course_units` DROP COLUMN `unit_order`;
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
        $unit_order_exists = $migration->columnExists(DATABASE_NAME, "course_units", "unit_order");
        if ($unit_order_exists) {
            return 1;
        } else {
            return 0;
        }
    }
}
