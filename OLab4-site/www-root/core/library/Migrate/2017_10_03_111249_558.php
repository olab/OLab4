<?php
ini_set("memory_limit", "10G");
class Migrate_2017_10_03_111249_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;

        $this->record();
        ?>
        CREATE TABLE `cbme_procedure_epa_attributes` (
            `epa_attribute_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `course_id` int(12) unsigned NOT NULL,
            `epa_objective_id` int(12) unsigned NOT NULL,
            `attribute_objective_id` int(12) unsigned NOT NULL,
            `created_by` int(12) unsigned NOT NULL,
            `created_date` bigint(64) unsigned NOT NULL,
            `updated_by` int(12) unsigned,
            `updated_date` bigint(64) unsigned,
            `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`epa_attribute_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        <?php
        $this->stop();

        if ($status = $this->run()) {
            $count = array(
                "courses" => 0,
                "attributes" => 0
            );


            echo "\nMigrating previously uploaded attributes: ";

            $query = "  SELECT a.* FROM `cbme_course_objectives` AS a
            JOIN `global_lu_objectives` AS b ON a.`objective_id` = b.`objective_id`
            WHERE b.`objective_code` = 'procedure_attribute'
            AND a.`deleted_date` IS NULL
            AND b.`objective_active` = 1
            AND b.`objective_id` NOT IN (
                SELECT attribute_objective_id FROM `cbme_procedure_epa_attributes` WHERE course_id = a.`course_id`
            )
            ORDER BY `course_id`";

            $attributes = $db->GetAll($query);
            if ($attributes) {
                $course_id = 0;
                $epas = array();
                foreach ($attributes as $attribute) {
                    $count["attributes"]++;
                    if ($course_id != $attribute["course_id"]) {
                        $count["courses"]++;
                        $course_id = $attribute["course_id"];
                        $query = "  SELECT * FROM `cbme_course_objectives` AS a
                        JOIN `global_lu_objectives` AS b ON a.`objective_id` = b.`objective_id`
                        JOIN `global_lu_objective_sets` AS c ON b.`objective_set_id` = c.`objective_set_id`
                        WHERE c.`shortname` = 'epa'
                        AND a.`course_id` = ?
                        AND b.`objective_set_id` = 1
                        AND a.`deleted_date` IS NULL
                        AND b.`objective_active` = 1
                        AND c.`deleted_date` IS NULL;";

                        if (!$epas = $db->getAll($query, array($course_id))) {
                            $epas = array();
                        }
                    }

                    foreach ($epas as $epa) {
                        $proc_epa = new Models_CBME_ProcedureEPAAttribute(array(
                            "course_id" => $course_id,
                            "epa_objective_id" => $epa["objective_id"],
                            "attribute_objective_id" => $attribute["objective_id"],
                            "created_by" => 1,
                            "created_date" => time()
                        ));

                        $proc_epa->insert();
                    }
                }
            }

            print $this->color("SUCCESS", "green");
            echo "\n{$count["attributes"]} attributes for {$count["courses"]} courses associated with its courses' epas\n\n";
        }

        return $status;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        DROP TABLE `cbme_procedure_epa_attributes`;
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
        $migrate = new Models_Migration();

        if ($migrate->tableExists(DATABASE_NAME, "cbme_procedure_epa_attributes")) {
            return 1;
        }

        return 0;
    }
}
