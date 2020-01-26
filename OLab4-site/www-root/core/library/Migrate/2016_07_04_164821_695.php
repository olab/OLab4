<?php
class Migrate_2016_07_04_164821_695 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `assessment_groups` (
        `agroup_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `cgroup_id` int(11) NOT NULL,
        `assessment_id` int(11) unsigned DEFAULT NULL,
        PRIMARY KEY (`agroup_id`),
        KEY `cgroup_id` (`cgroup_id`),
        KEY `assessment_id` (`assessment_id`),
        CONSTRAINT `assessment_groups_ibfk_1` FOREIGN KEY (`cgroup_id`) REFERENCES `course_groups` (`cgroup_id`),
        CONSTRAINT `assessment_groups_ibfk_2` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`assessment_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
        DROP TABLE `assessment_groups`;
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

        if ($migration->tableExists(DATABASE_NAME, "assessment_groups")) {
            return 1;
        }
        
        return 0;
    }
}
