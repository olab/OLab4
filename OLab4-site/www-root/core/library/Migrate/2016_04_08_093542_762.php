<?php
class Migrate_2016_04_08_093542_762 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_schedule_lu_block_types` (
        `block_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(128) NOT NULL,
        `number_of_blocks` tinyint(3) NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`block_type_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_schedule_lu_block_types` (`block_type_id`, `name`, `number_of_blocks`, `deleted_date`)
        VALUES
        (1, 'One Week', 52, NULL),
        (2, 'Two Week', 26, NULL),
        (3, 'Four Week', 13, NULL);

        ALTER TABLE `cbl_schedule` DROP COLUMN `stream_block_length`;
        ALTER TABLE `cbl_schedule` ADD COLUMN `block_type_id` INT(11) DEFAULT NULL AFTER `end_date`;
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
        DROP TABLE IF EXISTS `cbl_schedule_lu_block_types`;
        ALTER TABLE `cbl_schedule` DROP COLUMN `block_type_id`;
        ALTER TABLE `cbl_schedule` ADD COLUMN `stream_block_length` INT(2) AFTER `end_date`;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_schedule", "block_type_id")) {
            if (!$migration->columnExists(DATABASE_NAME, "cbl_schedule", "stream_block_length")) {
                if ($migration->tableExists(DATABASE_NAME, "cbl_schedule_lu_block_types")) {
                    return 1;
                }
            }
        }

        return 0;
    }
}
