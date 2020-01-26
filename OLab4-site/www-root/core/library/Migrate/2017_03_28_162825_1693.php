<?php
class Migrate_2017_03_28_162825_1693 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `cbl_schedule_lu_block_types` (`block_type_id`, `name`, `number_of_blocks`, `deleted_date`)
        VALUES
        (1, '1 Week', 52, NULL),
        (2, '2 Week', 26, NULL),
        (3, '4 Week', 13, NULL);
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
        DELETE FROM `cbl_schedule_lu_block_types`
        WHERE
        (`name` = '1 Week' AND `number_of_blocks` = 52 AND `deleted_date` IS NULL) OR
        (`name` = '2 Week' AND `number_of_blocks` = 26 AND `deleted_date` IS NULL) OR
        (`name` = '4 Week' AND `number_of_blocks` = 13 AND `deleted_date` IS NULL);
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
        global $db;

        // If there is anything already in the table, we do not run the migration
        $block_types = Models_BlockType::fetchAllRecords();
        if (!empty($block_types)) {
            return 1;
        } else {
            return 0;
        }
    }
}
