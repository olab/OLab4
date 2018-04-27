<?php
class Migrate_2017_02_28_213416_1606 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;
        $query = "  SELECT  `exam_id`, `created_by`, `created_date`, `updated_date`, `updated_by`, `deleted_date`
                    FROM `exams`";
        $this->record();
        $exams = $db->GetAll($query);
        if ($exams) {
            foreach ($exams as $exam) {
                ?>
                INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `timestamp`)
                VALUES ('<?php echo $exam["exam_id"];?>', '<?php echo $exam["created_by"];?>', 'exam_add', '<?php echo $exam["created_date"];?>');
                <?php
                if (isset($exam["created_date"]) && isset($exam["updated_date"]) && $exam["created_date"] != $exam["updated_date"]) {
                    ?>
                    INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `timestamp`)
                    VALUES ('<?php echo $exam["exam_id"];?>', '<?php echo $exam["updated_by"];?>', 'exam_edit', '<?php echo $exam["updated_date"];?>');
                    <?php
                }
                if (isset($exam["deleted_date"])) {
                    ?>
                    INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `timestamp`)
                    VALUES ('<?php echo $exam["exam_id"];?>', '<?php echo $exam["updated_by"];?>', 'exam_delete', '<?php echo $exam["deleted_date"];?>');
                    <?php
                }
            }
        }

        $query = "  SELECT `exam_id`, `post_id`, `target_type`, `target_id`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`
                    FROM `exam_posts`
                    WHERE `target_type` != 'preview'";

        $exam_posts = $db->GetAll($query);
        if ($exam_posts) {
            foreach ($exam_posts as $exam) {
                if (isset($exam["created_by"])) {
                    ?>
                    INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `action_resource_id`, `timestamp`)
                    VALUES ('<?php echo $exam["exam_id"];?>', '<?php echo $exam["created_by"];?>', 'post_exam_add', '<?php echo (int) $exam["post_id"];?>', '<?php echo $exam["created_date"];?>');
                    <?php
                }
                ?>
                INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `action_resource_id`, `timestamp`)
                VALUES ('<?php echo $exam["exam_id"];?>', '<?php echo $exam["updated_by"];?>', 'post_exam_edit', '<?php echo (int) $exam["post_id"];?>', '<?php echo $exam["updated_date"];?>');
                <?php
                if (isset($exam["deleted_date"])) {
                    ?>
                    INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `action_resource_id`, `timestamp`)
                    VALUES ('<?php echo $exam["exam_id"];?>', '<?php echo $exam["updated_by"];?>', 'post_exam_delete', '<?php echo (int) $exam["post_id"];?>', '<?php echo $exam["deleted_date"];?>');
                    <?php
                }
            }
        }

        $query = "  SELECT `exam_id`, `element_type`, `exam_element_id`, `element_id`, `group_id`, `updated_date`, `updated_by`, `deleted_date`
                    FROM `exam_elements`";

        $exam_elements = $db->GetAll($query);
        if ($exam_elements) {
            foreach ($exam_elements as $exam_element) {
                $secondary_action = NULL;
                $secondary_action_id = NULL;
                if ($exam_element["element_type"] == "question") {
                    $secondary_action = "version_id";
                    $secondary_action_id = $exam_element["element_id"];
                } else {
                    $secondary_action = NULL;
                    $secondary_action_id = NULL;
                }

                if (isset($exam_element["group_id"])) {
                    $secondary_action = "group";
                    $secondary_action_id = $exam_element["group_id"];
                    ?>
                    INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `action_resource_id`,`secondary_action`, `secondary_action_resource_id`, `timestamp`)
                    VALUES ('<?php echo $exam_element["exam_id"];?>', '<?php echo $exam_element["updated_by"];?>', 'exam_element_group_add', '<?php echo (int) $exam_element["exam_element_id"];?>', '<?php echo $secondary_action;?>', '<?php echo (int) $secondary_action_id;?>', '<?php echo $exam_element["updated_date"];?>');
                    <?php
                    if (isset($exam_element["deleted_date"])) {
                        ?>
                        INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `action_resource_id`, `secondary_action`, `secondary_action_resource_id`, `timestamp`)
                        VALUES ('<?php echo $exam_element["exam_id"];?>', '<?php echo $exam_element["updated_by"];?>', 'exam_element_group_delete', '<?php echo (int) $exam_element["exam_element_id"];?>','<?php echo $secondary_action;?>', '<?php echo (int) $secondary_action_id;?>', '<?php echo $exam_element["updated_date"];?>');
                        <?php
                    }
                } else {
                    ?>
                    INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `action_resource_id`, `secondary_action`, `secondary_action_resource_id`, `timestamp`)
                    VALUES ('<?php echo $exam_element["exam_id"];?>', '<?php echo $exam_element["updated_by"];?>', 'exam_element_add', '<?php echo (int) $exam_element["exam_element_id"];?>','<?php echo $secondary_action;?>', '<?php echo (int) $secondary_action_id;?>', '<?php echo $exam_element["updated_date"];?>');
                    <?php
                }
                if (isset($exam_element["deleted_date"])) {
                    ?>
                    INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `action_resource_id`, `secondary_action`, `secondary_action_resource_id`, `timestamp`)
                    VALUES ('<?php echo $exam_element["exam_id"];?>', '<?php echo $exam_element["updated_by"];?>', 'exam_element_delete', '<?php echo (int) $exam_element["element_id"];?>','<?php echo $secondary_action;?>', '<?php echo (int) $secondary_action_id;?>', '<?php echo $exam_element["updated_date"];?>');
                    <?php
                }

            }
        }

        $query = "  SELECT `exam_id`, `exam_element_id`, `type`, `value`, `created_date`, `created_by`, `deleted_date`
                    FROM `exam_adjustments`";

        $adjust_score = $db->GetAll($query);
        if ($adjust_score) {
            foreach ($adjust_score as $adjust) {
                ?>
                INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `action_resource_id`, `secondary_action`, `secondary_action_resource_id`, `timestamp`)
                VALUES ('<?php echo $adjust["exam_id"];?>', '<?php echo $adjust["created_by"];?>', 'adjust_score', '<?php echo $adjust["exam_element_id"];?>', '<?php echo $adjust["type"];?>', '<?php echo (int) $adjust["value"];?>', '<?php echo $adjust["created_date"];?>');
                <?php
                if (isset($adjust["deleted_date"])) {
                    ?>
                    INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `action_resource_id`, `secondary_action`, `secondary_action_resource_id`, `timestamp`)
                    VALUES ('<?php echo $adjust["exam_id"];?>', '<?php echo $adjust["created_by"];?>', 'delete_adjust_score', '<?php echo $adjust["exam_element_id"];?>', '<?php echo $adjust["type"];?>', '<?php echo (int) $adjust["value"];?>', '<?php echo $adjust["created_date"];?>');
                    <?php
                }
            }
        }

        $query = "  SELECT `exam_id`, `post_id`, `category_id`, `updated_date`, `updated_by`, `deleted_date`
                    FROM `exam_category`";

        $reports = $db->GetAll($query);
        if ($reports) {
            foreach ($reports as $report) {
                ?>
                INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `action_resource_id`, `timestamp`)
                VALUES ('<?php echo $report["exam_id"];?>', '<?php echo $report["updated_by"];?>', 'report_edit', '<?php echo (int) $report["category_id"];?>', '<?php echo $report["updated_date"];?>');
                <?php
                if (isset($report["deleted_date"])) {
                    ?>
                    INSERT INTO `exam_creation_history` (`exam_id`, `proxy_id`, `action`, `action_resource_id`, `timestamp`)
                    VALUES ('<?php echo $report["exam_id"];?>', '<?php echo $report["updated_by"];?>', 'report_delete', '<?php echo (int) $report["category_id"];?>', '<?php echo $report["deleted_date"];?>');
                    <?php
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
        DELETE FROM `exam_creation_history`;
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

        $query = "  SELECT `created_by`, `created_date`, `exam_id`
                    FROM `exams`";
        $exams = $db->GetAll($query);

        if ($exams && is_array($exams) && !empty($exams)) {
            $query = "  SELECT *
                    FROM `exam_creation_history`
                    WHERE `action` = 'exam_add'";

            $rows = $db->GetAll($query);
            if ($rows) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 1;
        }
    }
}
