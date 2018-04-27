<?php
class Migrate_2017_11_08_120827_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;

        $this->record();
        ?>
        ALTER TABLE `cbl_learner_objectives_completion` ADD COLUMN `course_id` INT(12) UNSIGNED NOT NULL AFTER `proxy_id`;
        <?php
        $this->stop();

        if(!$this->run()) {
            return false;
        }

        $cperiods = Models_Curriculum_Period::fetchAllCurrentIDs();

        echo "\n\n";

        $query = "  SELECT a.*, b.`organisation_id`
            FROM `cbl_learner_objectives_completion` AS a
            JOIN `objective_organisation` AS b 
            ON a.`objective_id` = b.`objective_id`";

        if ($results = $db->getAll($query)) {
            foreach ($results as $result) {
                if ($courses = Models_Course::getCoursesByProxyIDOrganisationID($result["proxy_id"], $result["organisation_id"], $cperiods)) {
                    if (count($courses) > 1) {
                        $course_ids = array_map(function($course) { return $course["course_id"]; }, $courses);

                        $found = false;
                        foreach ($courses as $course) {
                            if ($contacts = Models_Course_Contact::fetchAllByCourseIDContactType($course["course_id"], "ccmember")) {
                                foreach ($contacts as $contact) {
                                    if ($contact->getProxyID() == $result["created_by"]) {
                                        // Check if he's cc member for another student's course
                                        if (Models_Course_Contact::countIsMemberOf($contact->getProxyID(), $course_ids, "ccmember") === 1) {
                                            $found = true;
                                            $query = "UPDATE `cbl_learner_objectives_completion` SET `course_id` = ? WHERE `lo_completion_id` = ?";
                                            if (!$db->execute($query, array($course["course_id"], $result["lo_completion_id"]))) {
                                                echo "Failed to update record {$result['lo_completion_id']}: " . $db->errorMsg() . "\n";
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ( !$found) {
                            echo "More than one course found for proxy_id: {$result["proxy_id"]}, completion_id: {$result['lo_completion_id']}\n";
                        }
                    } else if (count($courses) == 1) {
                        $query = "UPDATE `cbl_learner_objectives_completion` SET `course_id` = ? WHERE `lo_completion_id` = ?";
                        if (!$db->execute($query, array($courses[0]["course_id"], $result["lo_completion_id"]))) {
                            echo "Failed to update record {$result['lo_completion_id']}: " . $db->errorMsg() . "\n";
                        }
                    } else {
                        echo "Failed to find an active course for proxy id: {$result["proxy_id"]}, completion record: {$result["lo_completion_id"]}\n ";
                    }
                }
            }
        }

        return true;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        ALTER TABLE `cbl_learner_objectives_completion` DROP COLUMN `course_id`;
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

        if ($migration->columnExists(DATABASE_NAME, "cbl_learner_objectives_completion", "course_id")) {
            return 1;
        }

        return 0;
    }
}
