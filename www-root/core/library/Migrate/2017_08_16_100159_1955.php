<?php
class Migrate_2017_08_16_100159_1955 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;

        $query = "SELECT * FROM `" . DATABASE_NAME . "`.`global_lu_objective_sets` WHERE `deleted_date` IS NULL";
        $objective_sets = $db->GetAll($query);
        if ($objective_sets) {
            foreach ($objective_sets as $objective_set) {
                $changed = false;
                $parent_objective = Models_Objective::fetchRowBySetIDParentID($objective_set["objective_set_id"], 0);
                if (!$parent_objective) {
                    if (!Models_Objective::fetchRowBySetIDParentID($objective_set["objective_set_id"], 0, NULL, 0)) {
                        $organisations_id = Models_Objective::fetchAllOrganisationBySetID($objective_set["objective_set_id"]);
                        if (is_array($organisations_id) && !empty($organisations_id)) {
                            $objective = array(
                                "objective_code" => $objective_set["code"],
                                "objective_name" => $objective_set["title"],
                                "objective_description" => $objective_set["description"],
                                "objective_parent" => 0,
                                "objective_set_id" => $objective_set["objective_set_id"],
                                "objective_order" => 0,
                                "objective_status_id" => 2,
                                "updated_date" => time(),
                                "updated_by" => 1
                            );
                            $parent_objective = new Models_Objective();
                            $parent_objective->fromArray($objective);
                            if ($parent_objective->insert()) {
                                foreach ($organisations_id as $organisation_id) {
                                    $parent_objective->insertOrganisationId($organisation_id);
                                }
                            }
                        } else {
                            $objective_set["deleted_date"] = time();
                            $changed = true;
                        }
                    } else {
                        $objective_set["deleted_date"] = time();
                        $changed = true;
                    }
                }

                if (is_null($objective_set["languages"]) || empty($objective_set["languages"])) {
                    $json_data = Entrada_Settings::fetchValueByShortname("language_supported");
                    if ($json_data) {
                        $language_supported = json_decode($json_data, true);
                        foreach ($language_supported as $index => $value) {
                            $objective_set["languages"][] = $index;
                            $changed = true;
                        }
                        $objective_set["languages"] = json_encode($objective_set["languages"], JSON_FORCE_OBJECT);
                    }
                }

                if (is_null($objective_set["requirements"]) || empty($objective_set["requirements"])) {
                    $requirements = [];
                    $requirements["code"] = ["required" => false];
                    $requirements["title"] = ["required" => true];
                    $requirements["description"] = ["required" => false];
                    $objective_set["requirements"] = json_encode($requirements);
                    $changed = true;
                }

                if (is_null($objective_set["maximum_levels"]) || empty($objective_set["maximum_levels"]) || $objective_set["maximum_levels"] == 1) {
                    $level = ($parent_objective ? Models_Objective::getObjectiveSetDepth($parent_objective->getID()) : 1);
                    $objective_set["maximum_levels"] = ($level > 0 ? $level : 1);
                    $changed = true;
                }

                if (is_null($objective_set["short_method"]) || empty($objective_set["short_method"])) {
                    $objective_set["short_method"] = "%t";
                    $changed = true;
                }

                if (is_null($objective_set["long_method"]) || empty($objective_set["long_method"])) {
                    $objective_set["long_method"] = "<h4 class=\"tag-title\">%t</h4><p class=\"tag-description\">%d</p>";
                    $changed = true;
                }

                if ($changed) {
                    $db->AutoExecute("global_lu_objective_sets", $objective_set, "UPDATE", "`objective_set_id`=" . $db->qstr($objective_set["objective_set_id"]));
                }
            }
        }

        $this->record();
        ?>
        CREATE TABLE `objective_tag_attributes` (
        `otag_attribute_id` int(11) NOT NULL AUTO_INCREMENT,
        `objective_set_id` int(11) NOT NULL,
        `target_objective_set_id` int(11) NOT NULL,
        `updated_date` bigint(64) NOT NULL,
        `updated_by` int(11) NOT NULL,
        PRIMARY KEY (`otag_attribute_id`),
        KEY `objective_set_id` (`objective_set_id`),
        KEY `target_objective_set_id` (`target_objective_set_id`),
        CONSTRAINT `objective_tag_attributes_ibfk_1` FOREIGN KEY (`objective_set_id`) REFERENCES `global_lu_objective_sets` (`objective_set_id`),
        CONSTRAINT `objective_tag_attributes_ibfk_2` FOREIGN KEY (`target_objective_set_id`) REFERENCES `global_lu_objective_sets` (`objective_set_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `objective_tag_levels` (
        `otag_level_id` int(11) NOT NULL AUTO_INCREMENT,
        `objective_set_id` int(11) NOT NULL,
        `level` int(2) NOT NULL,
        `label` varchar(36) DEFAULT NULL,
        `updated_date` bigint(64) NOT NULL,
        `updated_by` int(11) NOT NULL,
        PRIMARY KEY (`otag_level_id`),
        KEY `objective_set_id` (`objective_set_id`),
        CONSTRAINT `objective_tag_levels_ibfk_1` FOREIGN KEY (`objective_set_id`) REFERENCES `global_lu_objective_sets` (`objective_set_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        ALTER TABLE `global_lu_objective_sets` ADD COLUMN `code` varchar(24) DEFAULT NULL AFTER `objective_set_id`;
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
        DROP TABLE `objective_tag_attributes`;

        DROP TABLE `objective_tag_levels`;

        ALTER TABLE `global_lu_objective_sets` DROP COLUMN `code`;
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
        if ($migration->tableExists(DATABASE_NAME, "objective_tag_attributes") && $migration->tableExists(DATABASE_NAME, "objective_tag_levels") && $migration->columnExists(DATABASE_NAME, "global_lu_objective_sets", "code")) {
            return 1;
        }
        return 0;
    }
}
