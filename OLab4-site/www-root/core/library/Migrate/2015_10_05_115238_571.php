<?php
class Migrate_2015_10_05_115238_571 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        -- SQL Upgrade Queries Here;
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`assessments`
        ADD
        (
            created_date BIGINT(64) NOT NULL,
            created_by   INT(11)    NOT NULL,
            updated_date BIGINT(64) NOT NULL,
            updated_by   INT(11)    NOT NULL
        );
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
        -- SQL Downgrade Queries Here;
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`assessments`
        DROP created_date,
        DROP created_by,
        DROP updated_date,
        DROP updated_by;
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
        if ($migration->columnExists(DATABASE_NAME, "assessments", "created_date")) {
            if ($migration->columnExists(DATABASE_NAME, "assessments", "created_by")) {
                if ($migration->columnExists(DATABASE_NAME, "assessments", "updated_date")) {
                    if ($migration->columnExists(DATABASE_NAME, "assessments", "updated_by")) {
                        return 1;
                    }
                }
            }
        }

        return 0;
    }
}
