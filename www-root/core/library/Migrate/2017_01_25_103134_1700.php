<?php
class Migrate_2017_01_25_103134_1700 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {

        global $db;

        $query = "  SELECT `a`.`qobjective_id`, `a`.`question_id`, `a`.`objective_id`
                    FROM `exam_question_objectives` as `a`
                    WHERE 1 < (
                        SELECT COUNT(`b`.`question_id`)
                        FROM `exam_question_objectives` as `b`
                        WHERE `a`.`question_id` = `b`.`question_id`
                        AND `a`.`objective_id` = `b`.`objective_id`
                        AND `a`.`deleted_date` IS NULL
                        AND `b`.`deleted_date` IS NULL
                    ) AND `a`.`deleted_date` IS NULL
                    ORDER BY `a`.`question_id`, `a`.`objective_id` ASC";

        $rows = $db->GetAll($query);

        if ($rows && is_array($rows) && !empty($rows)) {
            $objectives = array();
            $question_id = "";
            $objective_id = "";
            $question_id_old = "";
            $objective_id_old = "";


            foreach ($rows as $row) {
                $question_id    = $row["question_id"];
                $objective_id   = $row["objective_id"];

                if ($question_id == $question_id_old && $objective_id == $objective_id_old) {
                    // Deactivate the question objective as it's a duplicate
                    $objectives[] = $row["qobjective_id"];
                }
                $question_id_old    = $row["question_id"];
                $objective_id_old   = $row["objective_id"];
            }



            if (is_array($objectives) && !empty($objectives)) {
                $sql = "  UPDATE `exam_question_objectives` 
                    SET `deleted_date` = \"" . time() . "\"
                    WHERE `qobjective_id` IN (" . implode(",", $objectives) . ");";

                $this->record();

                echo $sql;

                $this->stop();
            }
        }

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        -- SQL Downgrade Queries Here;
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

        $query = "  SELECT `a`.`qobjective_id`
                    FROM `exam_question_objectives` as `a`
                    WHERE 1 < (
                        SELECT COUNT(`b`.`question_id`)
                        FROM `exam_question_objectives` as `b`
                        WHERE `a`.`question_id` = `b`.`question_id`
                        AND `a`.`objective_id` = `b`.`objective_id`
                        AND `a`.`deleted_date` IS NULL
                        AND `b`.`deleted_date` IS NULL
                    ) AND `a`.`deleted_date` IS NULL
                    GROUP BY `a`.`question_id`";

        $rows = $db->GetAll($query);

        if ($rows) {
            return 0;
        }

        return 1;
    }
}
