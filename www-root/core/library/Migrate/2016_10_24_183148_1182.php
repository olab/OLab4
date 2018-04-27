<?php
class Migrate_2016_10_24_183148_1182 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        DELETE el1 FROM `event_linked_objectives` AS el1
        INNER JOIN (
            SELECT `event_id`, `linked_objective_id`, MIN(`updated_date`) AS `updated_date`
            FROM `event_linked_objectives`
            GROUP BY `event_id`, `linked_objective_id`
            HAVING COUNT(*) > 1
        ) el2 ON el2.`event_id` = el1.`event_id` AND el2.`linked_objective_id` = el1.`linked_objective_id`
        WHERE el1.`updated_date` > el2.`updated_date`;
        DELETE cul1 FROM `course_unit_linked_objectives` AS cul1
        INNER JOIN (
            SELECT `cunit_id`, `linked_objective_id`, MIN(`updated_date`) AS `updated_date`
            FROM `course_unit_linked_objectives`
            GROUP BY `cunit_id`, `linked_objective_id`
            HAVING COUNT(*) > 1
        ) cul2 ON cul2.`cunit_id` = cul1.`cunit_id` AND cul2.`linked_objective_id` = cul1.`linked_objective_id`
        WHERE cul1.`updated_date` > cul2.`updated_date`;
        DELETE cl1 FROM `course_linked_objectives` AS cl1
        INNER JOIN (
            SELECT `course_id`, `linked_objective_id`, MIN(`updated_date`) AS `updated_date`
            FROM `course_linked_objectives`
            GROUP BY `course_id`, `linked_objective_id`
            HAVING COUNT(*) > 1
        ) cl2 ON cl2.`course_id` = cl1.`course_id` AND cl2.`linked_objective_id` = cl1.`linked_objective_id`
        WHERE cl1.`updated_date` > cl2.`updated_date`;
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
        $query = "
            SELECT 1
            FROM `event_linked_objectives`
            GROUP BY `event_id`, `linked_objective_id`
            HAVING COUNT(*) > 1
            UNION ALL
            SELECT 1
            FROM `course_unit_linked_objectives`
            GROUP BY `cunit_id`, `linked_objective_id`
            HAVING COUNT(*) > 1
            UNION ALL
            SELECT 1
            FROM `course_linked_objectives`
            GROUP BY `course_id`, `linked_objective_id`
            HAVING COUNT(*) > 1
            LIMIT 1";
        if (!$db->GetOne($query)) {
            return 1;
        } else {
            return 0;
        }
    }
}
