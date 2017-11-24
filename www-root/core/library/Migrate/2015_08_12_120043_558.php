<?php
class Migrate_2015_08_12_120043_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_lu_leave_tracking_types` (
        `type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `type_value` varchar(128) NOT NULL DEFAULT '',
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`type_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_lu_leave_tracking_types` (`type_value`, `updated_date`, `updated_by`, `created_date`, `created_by`, `deleted_date`) VALUES
        ('Absence', NULL, NULL, 0, 1, NULL),
        ('Academic half day', NULL, NULL, 0, 1, NULL),
        ('Conference', NULL, NULL, 0, 1, NULL),
        ('Education days', NULL, NULL, 0, 1, NULL),
        ('Elective', NULL, NULL, 0, 1, NULL),
        ('Interview', NULL, NULL, 0, 1, NULL),
        ('Maternity', NULL, NULL, 0, 1, NULL),
        ('Medical', NULL, NULL, 0, 1, NULL),
        ('Other', NULL, NULL, 0, 1, NULL),
        ('Paternity', NULL, NULL, 0, 1, NULL),
        ('Professional development', NULL, NULL, 0, 1, NULL),
        ('Research', NULL, NULL, 0, 1, NULL),
        ('Sick', NULL, NULL, 0, 1, NULL),
        ('Stat', NULL, NULL, 0, 1, NULL),
        ('Study days', NULL, NULL, 0, 1, NULL),
        ('Vacation', NULL, NULL, 0, 1, NULL);

        ALTER TABLE `cbl_leave_tracking` ADD COLUMN `type_id` int(12) DEFAULT NULL AFTER `proxy_id`;
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
        ALTER TABLE `cbl_leave_tracking` DROP FOREIGN KEY leave_type_id;
        ALTER TABLE `cbl_leave_tracking` DROP COLUMN `type_id`;
        DROP TABLE IF EXISTS `cbl_lu_leave_tracking_types`;
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
        $query = "SHOW TABLES FROM " . DATABASE_NAME . " LIKE 'cbl_lu_leave_tracking_types'";
        $table = $db->GetRow($query);
        if ($table) {
            $query = "SHOW COLUMNS FROM `cbl_leave_tracking` LIKE 'type_id'";
            $column2 = $db->GetRow($query);
            if ($column2) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}
