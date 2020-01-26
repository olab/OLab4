<?php
class Migrate_2017_11_29_092935_2520 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {

        ini_set("memory_limit", "-1");

        $this->record();
        ?>
        CREATE TABLE `notices_read` (
        `notice_read_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `proxy_id` int(12) unsigned NOT NULL,
        `notice_id` int(12) unsigned NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        PRIMARY KEY (`notice_read_id`),
        KEY `proxy_notice_id` (`proxy_id`,`notice_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        <?php
        $this->stop();

        $result = $this->run();

        echo "\nMigrating old notice statistics";

        $old_stats = Models_Statistic::fetchAllRecords("notices", "read", "notice_id");
        if ($old_stats) {
            /* @var $old_stat Models_Statistic */
            foreach ($old_stats as $old_stat) {
                Models_Notices_Read::create($old_stat->getActionValue(), $old_stat->getProxyID(), $old_stat->getTimestamp());
            }
        }

        echo "\nFinished migrating old notice statistics";

        return $result;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        DROP TABLE `notices_read`;
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

        if ($migration->tableExists(DATABASE_NAME, "notices_read")) {
            return 1;
        }

        return 0;
    }
}
