<?php
class Migrate_2015_09_10_090232_573 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessments_lu_items`
        ADD COLUMN `comment_type` enum('disabled', 'optional', 'mandatory', 'flagged') NOT NULL DEFAULT 'disabled' AFTER `item_description`;

        ALTER TABLE `cbl_assessments_lu_items`
        DROP COLUMN `allow_comments`;
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
        ALTER TABLE `cbl_assessments_lu_items`
        ADD COLUMN `allow_comments` tinyint(1) NOT NULL DEFAULT 1 AFTER `comment_type`;

        ALTER TABLE `cbl_assessments_lu_items`
        DROP COLUMN `comment_type`;
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
        $query = "SHOW COLUMNS FROM `cbl_assessments_lu_items` LIKE 'comment_type'";
        $column = $db->GetRow($query);
        if ($column) {
            $query = "SHOW COLUMNS FROM `cbl_assessments_lu_items` LIKE 'allow_comments'";
            $column2 = $db->GetRow($query);
            if (!$column2) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}
