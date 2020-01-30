<?php
class Migrate_2017_06_29_145530_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_item_objectives` ADD `objective_metadata` TEXT AFTER `cbme_objective_tree_id`;
        <?php
        $this->stop();

        $this->run();

        $query = "SELECT * FROM `cbl_assessment_item_objectives` WHERE `cbme_objective_tree_id` IS NOT NULL LIMIT 50";

        while($rows = $db->getAll($query)) {
            foreach ($rows as $row) {
                $json = json_encode(array("tree_node_id" => $row["cbme_objective_tree_id"]));
                $sql = "UPDATE `cbl_assessment_item_objectives`
                        SET `objective_metadata` = ?, `cbme_objective_tree_id` = NULL 
                        WHERE `aiobjective_id` = ?";

                if (!$db->execute($sql, array($json, $row["aiobjective_id"]))) {
                    echo "Update failed: ".$db->ErrorMsg()."\n";
                }
            }
        }

        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_item_objectives` DROP `cbme_objective_tree_id`;
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        global $db;
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_item_objectives` ADD `cbme_objective_tree_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `objective_id`;
        <?php
        $this->stop();

        $this->run();

        $query = "SELECT * FROM `cbl_assessment_item_objectives` WHERE `objective_metadata` IS NOT NULL LIMIT 50";

        while($rows = $db->getAll($query)) {
            foreach ($rows as $row) {
                $json = json_decode($row["objective_metadata"]);
                $sql = "UPDATE `cbl_assessment_item_objectives`
                        SET `cbme_objective_tree_id` = ?, `objective_metadata` = NULL 
                        WHERE `aiobjective_id` = ?";

                if (!$db->execute($sql, array($json->tree_node_id, $row["aiobjective_id"]))) {
                    echo "Update failed: ".$db->ErrorMsg()."\n";
                }
            }
        }

        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_item_objectives` DROP `objective_metadata`;
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

        if ($migration->columnExists(DATABASE_NAME, "cbl_assessment_item_objectives", "objective_metadata")) {
            return 1;
        }

        return 0;
    }
}
