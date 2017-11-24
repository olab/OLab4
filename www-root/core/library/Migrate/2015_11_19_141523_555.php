<?php
class Migrate_2015_11_19_141523_555 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo CLERKSHIP_DATABASE ?>`.`categories`
        ADD
        (
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
        ALTER TABLE `<?php echo CLERKSHIP_DATABASE ?>`.`categories`
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
        if ($migration->columnExists(CLERKSHIP_DATABASE, "categories", "updated_date")) {
            if ($migration->columnExists(CLERKSHIP_DATABASE, "categories", "updated_by")) {
                return 1;
            }
        }

        return 0;
    }
}
