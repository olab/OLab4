<?php
class Migrate_2017_09_14_143027_1955 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();

        global $db;

        $query = "SELECT glo.`objective_set_id` , glo2.`objective_set_id` as target_objective_set_id FROM `linked_objectives` as lo
                    INNER JOIN `global_lu_objectives` as glo ON
                    lo.`objective_id` = glo.`objective_id`
                    INNER JOIN `global_lu_objectives` as glo2 ON
                    lo.`target_objective_id` = glo2.`objective_id`
                    WHERE lo.`active` = 1
                    GROUP BY glo.`objective_set_id`, glo2.`objective_set_id`;";
        echo $query;
        $linked_sets = $db->GetAll($query);
        if ($linked_sets) {
            foreach ($linked_sets as $linked_set) {
                $obj_set_id = $linked_set["objective_set_id"];
                $target_set_id = $linked_set["target_objective_set_id"];
                if (Models_ObjectiveSet::fetchRowByID($obj_set_id) && Models_ObjectiveSet::fetchRowByID($target_set_id) && !Models_Objective_TagAttribute::fetchRowByObjectiveSetIdTargetObjectiveSetID($obj_set_id, $target_set_id)) {
                    $tag_attribute = [
                        "objective_set_id" => $obj_set_id,
                        "target_objective_set_id" => $target_set_id,
                        "updated_date" => time(),
                        "updated_by" => 1
                    ];

                    $tag_attribute_model = new Models_Objective_TagAttribute($tag_attribute);
                    $tag_attribute_model->insert();
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
        return -1;
    }
}
