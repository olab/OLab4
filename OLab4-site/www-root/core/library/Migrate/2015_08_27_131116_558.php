<?php
class Migrate_2015_08_27_131116_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distributions` ADD COLUMN `assessor_option` enum('faculty','learner','individual_users') NOT NULL DEFAULT 'individual_users' AFTER `course_id`;
        <?php
        $this->stop();
        $result = false;
        if ($this->run()) {
            $result = true;
            $query = "SELECT * FROM `cbl_assessment_distributions`";
            $distributions = $db->GetAll($query);
            if ($distributions) {
                foreach ($distributions as $distribution) {
                    $query = "SELECT `assessor_type`, `assessor_role` FROM `cbl_assessment_distribution_assessors`
                          WHERE `adistribution_id` = ?
                          GROUP BY `assessor_type`, `assessor_role`";
                    $distribution_assessors = $db->GetAll($query, array($distribution["adistribution_id"]));
                    if ($distribution_assessors) {
                        $assessor_option = false;
                        foreach ($distribution_assessors as $distribution_assessor) {
                            if ($distribution_assessor == "external_hash") {
                                $assessor_option = "individual_user";
                            } elseif (in_array($distribution_assessor["assessor_type"], array("cgroup_id", "group_id", "course_id", "organisation_id"))) {
                                $assessor_option = "grouped_learners";
                            }
                            if (!$assessor_option && $distribution_assessor["assessor_role"] != "any") {
                                $assessor_option = $distribution_assessor["assessor_role"];
                            } elseif ($assessor_option && in_array($assessor_option, array("faculty", "learner")) && $assessor_option != $distribution_assessor["assessor_role"]) {
                                $assessor_option = "individual_user";
                            } else {
                                $assessor_option = "individual_user";
                            }
                        }
                        if ($assessor_option == "grouped_learners") {
                            $assessor_option = "learner";
                        }
                        if (!$db->Execute("UPDATE `cbl_assessment_distributions` SET `assessor_option` = ? WHERE `adistribution_id` = ?", array($assessor_option, $distribution["adistribution_id"]))) {
                            //Not sure if we should call the entire attempt a failure in this case or not, or maybe just log the error and leave it as is.
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distributions` DROP COLUMN `assessor_option`;
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
        $query = "SHOW COLUMNS FROM `cbl_assessment_distributions` LIKE 'assessor_option'";
        $column = $db->GetRow($query);
        if ($column) {
            return 1;
        } else {
            return 0;
        }
    }
}
