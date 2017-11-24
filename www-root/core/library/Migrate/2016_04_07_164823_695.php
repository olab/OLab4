<?php
class Migrate_2016_04_07_164823_695 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;

        $this->record();
        ?>
        ALTER TABLE `assessments` ADD COLUMN `cperiod_id` int(11) NULL AFTER `cohort`;
        <?php
        $this->stop();

        $sql_result = $this->run();

        $class_name = get_class($this);

        /**
         * Transform existing assessments data - populate Curriculum Period (curriculum_periods.cperiod_id)
         * by looking up the course_id and cohort in the course audience table, which will yield a cperiod_id
         *
         * Note: this may not transform all entries, because:
         *  - courses may no longer be active, and have no audience entries, so no mapping to Curriculum Period
         *  - courses may be set up without Curriculum Periods and rely completely on cohorts
         *  - a cohort may be assigned to multiple Curriculum Periods in the course
         *      this ambiguous condition will result in a warning so it can be manually repaired
         */

        $curriculum_period_query = "SELECT a.`course_id`, a.`cohort`, b.`cperiod_id`
                                    FROM `assessments` AS a
                                    JOIN `course_audience` AS b ON a.`course_id` = b.`course_id`
                                    WHERE b.`audience_type` = 'group_id' AND b.`audience_value` = a.`cohort` 
                                    GROUP BY a.`course_id`, a.`cohort`, b.`cperiod_id`";

        $curriculum_period_results = $db->GetAll($curriculum_period_query);
        if ($curriculum_period_results) {
            print "\n";
            print $class_name . ": Task: " . $this->color("Migrating Assessments table cohorts to curriculum periods.", "pink");

            // see if there are multiple curriculum periods for each unique course_id / cohort. This would mean that the curriculum period is not unique
            $filtered_results = array();
            foreach ($curriculum_period_results as $result) {
                $filtered_results[$result["course_id"]][$result["cohort"]][] = $result["cperiod_id"];
            }

            foreach ($filtered_results as $course_id => $group_array) {
                foreach ($filtered_results[$course_id] as $group_id => $cperiod_array) {
                    if (count($cperiod_array) > 1) {
                        print "\n";
                        print $class_name . ": " . $this->color("Warning: course_id " . $course_id . " has multiple Curriculum Periods using cohort(group_id) " . $group_id . " This must be fixed manually.", "red");
                    } else {
                        // update the assessment table
                        $update_query = "UPDATE `assessments` SET `cperiod_id` = " . $cperiod_array[0] . " WHERE `course_id` = " . $course_id . " AND `cohort` = " . $group_id;
                        $db->Execute($update_query);
                    }
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
        ALTER TABLE `assessments` DROP COLUMN `cperiod_id`;
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
        if ($migration->columnExists(DATABASE_NAME, "assessments", "cperiod_id")) {
            return 1;
        }

        return 0;
    }
}
