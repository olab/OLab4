<?php
class Migrate_2015_12_23_000231_648 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo DATABASE_NAME ?>`.`event_lti_consumers` ADD COLUMN `release_date` int(12) NOT NULL DEFAULT '0' AFTER `lti_notes`;
        ALTER TABLE `<?php echo DATABASE_NAME ?>`.`event_lti_consumers` ADD COLUMN `release_until` int(12) NOT NULL DEFAULT '0' AFTER `release_date`;
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
        ALTER TABLE `<?php echo DATABASE_NAME ?>`.`event_lti_consumers` DROP COLUMN `release_date`;
        ALTER TABLE `<?php echo DATABASE_NAME ?>`.`event_lti_consumers` DROP COLUMN `release_until`;
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
        if ($migration->columnExists(DATABASE_NAME, "event_lti_consumers", "release_date")) {
            if ($migration->columnExists(DATABASE_NAME, "event_lti_consumers", "release_until")) {
                return 1;
            }
        }

        return 0;
    }
}
