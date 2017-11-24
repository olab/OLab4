<?php
class Migrate_2016_11_15_172222_532 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`courses` ADD COLUMN `course_color` VARCHAR(20) NULL;
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`events` ADD COLUMN `event_color` VARCHAR(20) NULL AFTER `audience_visible`;
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
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`courses` DROP COLUMN `course_color`;
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`events` DROP COLUMN `event_color`;
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
        if ($migration->columnExists(DATABASE_NAME, "courses", "course_color") &&
            $migration->columnExists(DATABASE_NAME, "events", "event_color")) {
            $meta1 = $migration->fieldMetadata(DATABASE_NAME, "courses", "course_color");
            if (!empty($meta1["Type"]) && $meta1["Type"] == "varchar(20)") {
                $meta2 = $migration->fieldMetadata(DATABASE_NAME, "events", "event_color");
                if (!empty($meta2["Type"]) && $meta2["Type"] == "varchar(20)") {
                    return 1;
                }
            }
        }
        return 0;
    }
}
