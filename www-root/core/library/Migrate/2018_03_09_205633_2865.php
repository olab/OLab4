<?php
class Migrate_2018_03_09_205633_2865 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `courses`
        ADD COLUMN `created_date` BIGINT(64) NOT NULL AFTER `cbme_milestones`,
        ADD COLUMN `created_by` INT(12) NOT NULL AFTER `created_date`,
        ADD COLUMN `updated_date` BIGINT(64) NOT NULL AFTER `created_by`,
        ADD COLUMN `updated_by` INT(12) NOT NULL AFTER `updated_date`;
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
        ALTER TABLE `courses`
        DROP `created_date`,
        DROP `created_by`,
        DROP `updated_date`,
        DROP `updated_by`;
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
        if ($migration->columnExists(DATABASE_NAME, "courses", "created_date")
            && $migration->columnExists(DATABASE_NAME, "courses", "created_by")
            && $migration->columnExists(DATABASE_NAME, "courses", "updated_date")
            && $migration->columnExists(DATABASE_NAME, "courses", "updated_by")) {
            return 1;
        }

        return 0;
    }
}
