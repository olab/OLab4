<?php
class Migrate_2017_08_02_130152_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
            CREATE TABLE `cbl_learner_objectives_completion` (
                `lo_completion_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
                `proxy_id` int(12) unsigned NOT NULL,
                `objective_id` int(12) unsigned NOT NULL,
                `created_date` bigint(64) unsigned NOT NULL,
                `created_by` int(12) unsigned NOT NULL,
                `created_reason` text,
                `deleted_date` bigint(64) unsigned DEFAULT NULL,
                `deleted_by` int(12) unsigned DEFAULT NULL,
                `deleted_reason` text,
            PRIMARY KEY (`lo_completion_id`),
            KEY `proxy_id` (`proxy_id`,`objective_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `<?php echo AUTH_DATABASE ?>`.`acl_permissions` (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        ('competencycommittee', NULL, 'group', 'faculty', 1, NULL, 1, NULL, NULL, 'CompetencyCommittee'),
        ('competencycommittee', NULL, 'group:role', 'staff:pcoordinator', 1, NULL, 1, NULL, NULL, 'CompetencyCommittee'),
        ('competencycommittee', NULL, 'group:role', 'staff:admin', 1, NULL, 1, NULL, NULL, 'CompetencyCommittee');
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
            DROP TABLE `cbl_learner_objectives_completion`;
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

        if ($migration->tableExists(DATABASE_NAME, "cbl_learner_objectives_completion")) {
            return 1;
        }

        return 0;
    }
}
