<?php
class Migrate_2016_01_29_151435_666 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        -- SQL Upgrade Queries Here;
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`groups`
        ADD COLUMN `created_date` BIGINT(64) NOT NULL AFTER `group_active`,
        ADD COLUMN `created_by`   INT(11)    NOT NULL AFTER `created_date`;

        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`group_organisations`
        ADD COLUMN    `created_date` BIGINT(64) NOT NULL AFTER `organisation_id`,
        ADD COLUMN    `created_by`   INT(11)    NOT NULL AFTER `created_date`,
        MODIFY COLUMN `updated_date` BIGINT(64) NOT NULL AFTER `created_by`,
        MODIFY COLUMN `updated_by`   INT(11)    NOT NULL AFTER `updated_date`;

        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`group_members`
        ADD COLUMN `created_date` BIGINT(64) NOT NULL AFTER `entrada_only`,
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
        -- SQL Downgrade Queries Here;
        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`groups`
        DROP created_date,
        DROP created_by;

        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`group_organisations`
        DROP created_date,
        DROP created_by,
        MODIFY COLUMN `updated_date` BIGINT(64) DEFAULT NULL AFTER `updated_by`,
        MODIFY COLUMN `updated_by`   INT(11)    DEFAULT NULL AFTER `organisation_id`;

        ALTER TABLE `<?php echo DATABASE_NAME; ?>`.`group_members`
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
        if ($migration->columnExists(DATABASE_NAME, "groups", "created_date") && $migration->columnExists(DATABASE_NAME, "groups", "created_by")) {
            if ($migration->columnExists(DATABASE_NAME, "group_organisations", "created_date") && $migration->columnExists(DATABASE_NAME, "group_organisations", "created_by")) {
                if ($migration->columnExists(DATABASE_NAME, "group_members", "created_date") && $migration->columnExists(DATABASE_NAME, "group_members", "created_by")) {
                    return 1;
                }
            }
        }

        return 0;
    }
}
