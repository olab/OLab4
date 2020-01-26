<?php
class Migrate_2017_02_27_140733_1606 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;

        $this->record();

        $query = "  SELECT `exam_id`, `exam_element_id`
                    FROM `exam_adjustments`";

        $adjust_score = $db->GetAll($query);
        if ($adjust_score) {
            foreach ($adjust_score as $adjust) {
                if (!$adjust["exam_id"]) {
                    // use $adjust["exam_element_id"] to find the exam id
                    $exam_id_query = "SELECT `exam_id`
                                          FROM `exam_elements`
                                          WHERE `exam_element_id` = \"" . (int)$adjust["exam_element_id"] . "\"";
                    $exam_id = $db->GetRow($exam_id_query);
                    if ($exam_id) {
                        //var_dump($exam_id);
                        //
                        ?>
                        UPDATE `exam_adjustments`
                        SET `exam_id` = '<?php echo $exam_id["exam_id"]; ?>'
                        WHERE `exam_element_id` = '<?php echo $adjust["exam_element_id"]; ?>';
                        <?php
                    }
                }
            }
        }
        $this->stop();

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


        $query = "  SELECT `exam_id`, `exam_element_id`, `type`, `value`, `created_date`, `created_by`, `deleted_date`
                  FROM `exam_adjustments`";

        $adjust_score = $db->GetAll($query);
        if ($adjust_score) {
            foreach ($adjust_score as $adjust) {
                if (!$adjust["exam_id"]) {
                    return 0;
                    break;
                }
            }
        }
        return 1;
    }
}
