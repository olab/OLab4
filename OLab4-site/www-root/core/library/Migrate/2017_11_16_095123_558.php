<?php
class Migrate_2017_11_16_095123_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;

        $success = 0;
        $fail = 0;

        $query = "SELECT `form_type_id` 
                  FROM `cbl_assessments_lu_form_types` 
                  WHERE `shortname` IN ('cbme_ppa_form', 'cbme_rubric')
                  AND `form_type_id` NOT IN (
                    SELECT `form_type_id` 
                    FROM `cbl_assessments_form_type_component_settings`
                    WHERE `component_order` = 1
                    AND `deleted_date` IS NULL
                    AND `settings` = ?
                  )";

        if ($form_types = $db->getCol($query, array('{"component_header":"Select the scale to use for the Entrustment Question","allow_default_response":false,"mode":"form"}'))) {
            foreach ($form_types as $form_type) {
                $query = "INSERT INTO `cbl_assessments_form_type_component_settings` 
                            (`form_type_id`, `component_order`, `settings`, `created_date`, `created_by`)
                          VALUES (?, 1, ?, ?, 1)";

                if (!$db->query($query, array($form_type,'{"component_header":"Select the scale to use for the Entrustment Question","allow_default_response":false,"mode":"form"}',time()))) {
                    echo "Failed to insert component settings for form type {$form_type}";
                    $fail++;
                } else {
                    $success++;
                }
            }
        }

        return array("success" => $success, "fail" => $fail);
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        global $db;

        $success = 0;
        $fail = 0;

        $query = "DELETE FROM `cbl_assessments_form_type_component_settings` 
                  WHERE `component_order` = 1  
                  AND `form_type_id` IN (
                    SELECT `form_type_id` 
                    FROM `cbl_assessments_lu_form_types` 
                    WHERE `shortname` IN ('cbme_ppa_form', 'cbme_rubric')
                  )
                  AND `settings` = ?";

        if (!$db->Execute($query, array('{"component_header":"Select the scale to use for the Entrustment Question","allow_default_response":false,"mode":"form"}'))) {
            $fail++;
        } else {
            $success++;
        }

        return array("success" => $success, "fail" => $fail);
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

        $query = "SELECT count(*) 
                  FROM `cbl_assessments_lu_form_types` 
                  WHERE `shortname` IN ('cbme_ppa_form', 'cbme_rubric')
                  AND `form_type_id` NOT IN (
                    SELECT `form_type_id` 
                    FROM `cbl_assessments_form_type_component_settings`
                    WHERE `component_order` = 1
                    AND `settings` = ?
                    AND `deleted_date` IS NULL
                  )";

        $result = $db->getOne($query, array('{"component_header":"Select the scale to use for the Entrustment Question","allow_default_response":false,"mode":"form"}'));

        if (intval($result) > 0) {
            return 0;
        }

        return 1;
    }
}
