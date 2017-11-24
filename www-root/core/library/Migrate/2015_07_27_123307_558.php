<?php
class Migrate_2015_07_27_123307_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `notification_users` ADD COLUMN `notification_user_type` enum('proxy_id','external_assessor_id') NOT NULL DEFAULT 'proxy_id' AFTER `proxy_id`;
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
        ALTER TABLE `notification_users` DROP COLUMN `notification_user_type`;
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

        $query = "SHOW COLUMNS FROM `notification_users` LIKE 'notification_user_type'";
        $column = $db->GetRow($query);
        if ($column) {
            return 1;
        } else {
            return 0;
        }
    }
}
