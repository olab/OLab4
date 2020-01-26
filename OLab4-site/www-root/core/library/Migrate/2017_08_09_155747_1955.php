<?php
ini_set("memory_limit", "4096M");

class Migrate_2017_08_09_155747_1955 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;

        $this->record();

        /**
         * This will create an entry in global_lu_objective_sets for every in global_lu_objectives with a
         * objective_parent = 0 that does not have a corresponding global_lu_objective_sets.
         */
        $query = "SELECT *
                  FROM `" . DATABASE_NAME . "`.`global_lu_objectives`
                  WHERE `objective_parent` = 0";
        $objectives = $db->GetAll($query);
        if ($objectives) {
            foreach ($objectives as $objective) {
                $objective_set_id = 0;

                if ($objective["objective_set_id"] == 0) {
                    $suffix = 0;
                    do {
                        if ($objective["objective_name"]) {
                            $shortname = clean_input($objective["objective_name"], ["alphanumeric", "lower"]) . (($suffix > 0) ? "_" . $suffix : "");
                        } else {
                            $shortname = clean_input("empty", ["alphanumeric", "lower"]) . (($suffix > 0) ? "_" . $suffix : "");
                        }

                        $query = "SELECT * FROM `" . DATABASE_NAME . "`.`global_lu_objective_sets` WHERE `shortname` = ?;";
                        $result = $db->GetRow($query, [$shortname]);
                        if ($result) {
                            $shortname = false;

                            $suffix++;
                        }
                    } while(!$shortname);

                    if ($objective["objective_active"] == 0) {
                        $deleted_date = $objective["updated_date"];
                    } else {
                        $deleted_date = NULL;
                    }

                    $record = [
                        "title" => $objective["objective_name"],
                        "description" => $objective["objective_description"],
                        "shortname" => $shortname,
                        "start_date" => NULL,
                        "end_date" => NULL,
                        "standard" => 0,
                        "created_date" => $objective["updated_date"],
                        "created_by" => $objective["updated_by"],
                        "updated_date" => $objective["updated_date"],
                        "updated_by" => $objective["updated_by"],
                        "deleted_date" => $deleted_date
                    ];

                    $objective_set = new Models_ObjectiveSet($record);

                    if ($objective_set->insert()) {
                        $objective_set_id = $objective_set->getID();

                        $query = "UPDATE `" . DATABASE_NAME . "`.`global_lu_objectives`
                                SET `objective_set_id` = ?
                                WHERE `objective_id` = ?
                                AND `objective_set_id` = 0";
                        $db->Execute($query, [$objective_set_id, $objective["objective_id"]]);
                    }
                } else {
                    $objective_set_id = $objective["objective_set_id"];
                }

                Models_Objective::UpdateObjectiveSetIDs($objective_set_id, $objective["objective_id"]);
            }
        }

        /**
         * Run migration only if the above code found anything to do.
         */
        if ($this->stop() > 0) {
            return $this->run();
        } else {
            return true;
        }
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        global $db;

        $this->record();

        $query = "SELECT * FROM `" . DATABASE_NAME . "`.`global_lu_objectives` WHERE `objective_parent` = 0";
        $objectives = $db->GetAll($query);
        if ($objectives) {
            foreach ($objectives as $objective) {
                $objective_set_id = $objective["objective_set_id"];
                if ($objective_set_id != 0) {
                    ?>
                    UPDATE `<?php echo DATABASE_NAME; ?>`.`global_lu_objectives`
                    SET `objective_set_id` = 0
                    WHERE `objective_id` = <?php echo (int) $objective["objective_id"]; ?>;

                    DELETE FROM `<?php echo DATABASE_NAME; ?>`.`global_lu_objective_sets`
                    WHERE `objective_set_id` = <?php echo (int) $objective_set_id; ?>;
                    <?php
                }
            }
        }

        /**
         * Run migration only if the above code found anything to do.
         */
        if ($this->stop() > 0) {
            return $this->run();
        } else {
            return true;
        }
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
