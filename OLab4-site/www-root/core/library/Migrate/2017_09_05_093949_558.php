<?php
class Migrate_2017_09_05_093949_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distribution_targets` CHANGE `target_type` `target_type` ENUM('proxy_id','group_id','cgroup_id','course_id','schedule_id','organisation_id','self','external_hash','eventtype_id')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'proxy_id';
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
        ALTER TABLE `cbl_assessment_distribution_targets` CHANGE `target_type` `target_type` ENUM('proxy_id','group_id','cgroup_id','course_id','schedule_id','organisation_id','self','external_hash')  CHARACTER SET utf8  COLLATE utf8_general_ci  NOT NULL  DEFAULT 'proxy_id';
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
        $metadata = $migrate->fieldMetadata(DATABASE_NAME, "cbl_assessment_distribution_targets", "target_type");
        if (strstr($metadata["Type"], "'eventtype_id'") === false) {
            return 0;
        } else {
            return 1;
        }
    }
}
