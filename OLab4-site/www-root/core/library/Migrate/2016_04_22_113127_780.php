<?php
class Migrate_2016_04_22_113127_780 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;

        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `assessment_grading_scale` (
        `agscale_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `organisation_id` int(11) DEFAULT NULL,
        `title` varchar(256) DEFAULT NULL,
        `applicable_from` bigint(64) DEFAULT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        `deleted_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`agscale_id`),
        KEY `applicable_from` (`applicable_from`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `assessment_grading_range` (
        `agrange_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `agscale_id` int(11) unsigned NOT NULL,
        `numeric_grade_min` int(11) DEFAULT NULL,
        `letter_grade` varchar(128) DEFAULT NULL,
        `gpa` decimal(5,2) DEFAULT NULL,
        `notes` varchar(128) DEFAULT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        `deleted_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`agrange_id`),
        KEY `lgs_id` (`agscale_id`),
        CONSTRAINT `assessment_grading_range_ibfk_1` FOREIGN KEY (`agscale_id`) REFERENCES `assessment_grading_scale` (`agscale_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        <?php
        $this->stop();

        $sql_result = $this->run();

        /**
         * Add a default Assessment Grading Scale for each organization currently in the database
         * unless there are already grading scale entries present (possible if the tables already existed for some reason)
         */

        $class_name = get_class($this);
        
        $organisations = Models_Organisation::fetchAllOrganisations();         
        if (is_array($organisations) and count($organisations) > 0) {

            print "\n";
            print $class_name . ": Task: " . $this->color("Adding default Grading Scale for ". count($organisations) ." organisations in the database.", "pink");

            foreach ($organisations as $organisation) {
                $default_scale = Models_Gradebook_Grading_Scale::addDefaultScaleForOrganisation($organisation["organisation_id"]);
                if (!$default_scale) {
                    print "\n";
                    print $class_name . ": " . $this->color("Failed to add default grading scale for organisation: " . $organisation->getOrganisationTitle() . " DB error: " .$db->ErrorMsg(), "red");
                }
            }
        }
        return $sql_result;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        DROP TABLE IF EXISTS `assessment_grading_range`;
        DROP TABLE IF EXISTS `assessment_grading_scale`;
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
        if ($migration->tableExists(DATABASE_NAME, "assessment_grading_scale")) {
            if ($migration->tableExists(DATABASE_NAME, "assessment_grading_range")) {
                return 1;
            }
        }
        return 0;
    }
}
