<?php
class Migrate_2016_10_14_104207_1583 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        global $db;
        $query = "SELECT * FROM `global_lu_objectives`
              WHERE `objective_parent` = 0
              AND `objective_active` = 1
              ORDER BY `objective_name` ASC";
        $top_level_objectives = $db->GetAll($query);
        $name = "";

        if ($top_level_objectives && is_array($top_level_objectives)) {
            foreach ($top_level_objectives as $top_level_objective) {

                $query = "SELECT * FROM `global_lu_objectives`
                      WHERE `objective_parent` = " . $top_level_objective["objective_id"] . "
                      AND `objective_active` = 1
                      ORDER BY `objective_name` ASC";
                $sub_level_objectives = $db->GetAll($query);

                foreach ($sub_level_objectives as $key => $sub_level_objective) {
                    if ($key == 0) {
                        $new_objective_id = $sub_level_objective["objective_id"];
                    } else {
                        $objective_id = $sub_level_objective["objective_id"];

                        if ($name == $sub_level_objective["objective_name"]) {
                            // search all questions for this $objective_id
                            $questions = Models_Exam_Question_Objectives::fetchAllRecordsByObjectiveID($objective_id);

                            if ($questions && is_array($questions)) {

                                foreach ($questions as $question) {
                                    ?>
                                    UPDATE `exam_question_objectives`
                                    SET `objective_id` = '<?php echo $new_objective_id;?>'
                                    WHERE `objective_id` = '<?php echo $question->getObjectiveID();?>'
                                    AND `question_id` = '<?php echo $question->getQuestionID();?>';
                                    <?php
                                }
                            }
                            ?>
                            UPDATE `global_lu_objectives`
                            SET `objective_active` = 0
                            WHERE `objective_id` = '<?php echo $objective_id;?>';
                            <?php
                        } else {
                            $name = $sub_level_objective["objective_name"];
                        }
                    }
                }
            }
        }
        ?>
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

        $query = "SELECT * FROM `global_lu_objectives`
                  WHERE `objective_parent` = 0
                  ORDER BY `objective_name` ASC";
        $top_level_objectives = $db->GetAll($query);
        $name = "";

        if ($top_level_objectives && is_array($top_level_objectives)) {
            foreach ($top_level_objectives as $top_level_objective) {
                $query = "SELECT * FROM `global_lu_objectives`
                      WHERE `objective_parent` = " . $top_level_objective["objective_id"] . "
                      ORDER BY `objective_name` ASC";
                $sub_level_objectives = $db->GetAll($query);
                foreach ($sub_level_objectives as $key => $sub_level_objective) {
                    if ($key == 0) {
                        $new_objective_id = $sub_level_objective["objective_id"];
                    }

                    $objective_id = $sub_level_objective["objective_id"];

                    if ($name == $sub_level_objective["objective_name"]) {
                        // search all questions for this $objective_id
                        $questions = Models_Exam_Question_Objectives::fetchAllRecordsByObjectiveID($objective_id);
                        if ($questions && is_array($questions)) {
                            return 0;
                        }
                    } else {
                        $name = $sub_level_objective["objective_name"];
                    }
                }
            }
        }

        return 1;
    }
}
