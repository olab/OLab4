<?php
class Migrate_2017_06_06_125950_1981 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `disclaimers` MODIFY `upon_decline` ENUM('continue','log_out','deny_access');
        ALTER TABLE `disclaimers` ADD COLUMN `trigger_type` ENUM('page_load','course','community') NOT NULL DEFAULT 'page_load' AFTER `upon_decline`;
        CREATE TABLE `disclaimer_trigger` (
        `disclaimer_trigger_id` int(11) NOT NULL AUTO_INCREMENT,
        `disclaimer_id` int(11) NOT NULL,
        `disclaimer_trigger_type` enum('course','community') NOT NULL,
        `disclaimer_trigger_value` varchar(16) NOT NULL,
        `updated_date` bigint(64) NOT NULL,
        `updated_by` int(11) NOT NULL,
        PRIMARY KEY (`disclaimer_trigger_id`),
        KEY `disclaimer_id` (`disclaimer_id`),
        CONSTRAINT `disclaimer_trigger_ibfk_1` FOREIGN KEY (`disclaimer_id`) REFERENCES `disclaimers` (`disclaimer_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
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
        ALTER TABLE `disclaimers` MODIFY `upon_decline` ENUM('continue','log_out');
        ALTER TABLE `disclaimers` DROP COLUMN `trigger_type`;
        DROP TABLE `disclaimer_trigger`;
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
        $meta = $migration->fieldMetadata(DATABASE_NAME, "disclaimers", "upon_decline");
        if (!empty($meta["Type"]) && $meta["Type"] == "enum('continue','log_out','deny_access')") {
            if ($migration->columnExists(DATABASE_NAME, "disclaimers", "trigger_type") && $migration->tableExists(DATABASE_NAME, "disclaimer_trigger")) {
                return 1;
            }
        }

        return 0;
    }
}
