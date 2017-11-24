<?php
class Migrate_2016_03_29_113528_696 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `<?php echo AUTH_DATABASE ?>`.`system_groups`
        ADD
        (
            visible TINYINT(1) NOT NULL DEFAULT '1'
        );

        UPDATE `<?php echo AUTH_DATABASE ?>`.`system_groups`
        SET `visible` = '0'
        WHERE `group_name` = 'guest';

        ALTER TABLE `<?php echo AUTH_DATABASE; ?>`.`user_data`
        ADD COLUMN `created_date` BIGINT(64) NOT NULL AFTER `uuid`,
        ADD COLUMN `created_by`   INT(11)    NOT NULL AFTER `created_date`;
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
        ALTER TABLE `<?php echo AUTH_DATABASE ?>`.`system_groups`
        DROP visible;

        ALTER TABLE `<?php echo AUTH_DATABASE; ?>`.`user_data`
        DROP created_date,
        DROP created_by;
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

        if ($migration->columnExists(AUTH_DATABASE, "system_groups", "visible")) {
            if ($migration->columnExists(AUTH_DATABASE, "user_data", "created_date")) {
                if ($migration->columnExists(AUTH_DATABASE, "user_data", "created_by")) {
                    return 1;
                }
            }
        }

        return 0;
    }
}
