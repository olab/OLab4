<?php
class Migrate_2016_07_25_093116_1028 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessment_report_caches` (
        `arcache_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `report_key` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
        `report_param_hash` varchar(32) COLLATE utf8_unicode_ci DEFAULT '',
        `report_meta_hash` varchar(32) COLLATE utf8_unicode_ci DEFAULT '',
        `target_type` enum('proxy_id','organisation_id','cgroup_id','group_id','course_id','adtarget_id','schedule_id','eventtype_id') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'proxy_id',
        `target_value` int(11) NOT NULL,
        `created_date` int(64) DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`arcache_id`),
        KEY `report_key` (`report_key`),
        KEY `proxy_id_target_type_target_value_report_meta_hash` (`target_type`,`target_value`,`report_meta_hash`),
        KEY `proxy_id_target_type_target_value_report_param_hash` (`target_type`,`target_value`,`report_param_hash`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
        DROP TABLE `cbl_assessment_report_caches`;
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
        $migrate = new Models_Migration();
        if ($migrate->tableExists(DATABASE_NAME, "cbl_assessment_report_caches")) {
            return 1;
        } else {
            return 0;
        }
    }
}
