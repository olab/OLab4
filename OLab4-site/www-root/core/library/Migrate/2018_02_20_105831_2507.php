<?php
class Migrate_2018_02_20_105831_2507 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;

        $success = 0;
        $fail = 0;

        /**
         * We first look for existing rotationschedule acl's.
         */
        $query = "SELECT * FROM `" . AUTH_DATABASE . "`.`acl_permissions` 
                  WHERE `resource_type` = 'rotationschedule'
                  AND `resource_value` IS NULL
                  AND `entity_type` LIKE '%group:role' 
                  AND (`entity_value` LIKE '%faculty:director' OR `entity_value` LIKE '%staff:pcoordinator')
                  AND `app_id` IS NULL
                  AND `create` = 1
                  AND `read` = 1
                  AND `update` = 1
                  AND `delete` = 1";

        if ($db->GetRow($query)) {
            /**
             * There are already rotationschedule entries. Look for the ones for faculty:director and staff:pcoordinator to make sure they have the required assertion
             */
            $query = "UPDATE `" . AUTH_DATABASE . "`.`acl_permissions`
                  SET `assertion` = 'RotationScheduleOwner'
                  WHERE `resource_type` = 'rotationschedule'
                  AND `resource_value` IS NULL
                  AND `entity_type` LIKE '%group:role' 
                  AND (`entity_value` LIKE '%faculty:director' OR `entity_value` LIKE '%staff:pcoordinator')
                  AND `app_id` IS NULL
                  AND `create` = 1
                  AND `read` = 1
                  AND `update` = 1
                  AND `delete` = 1";
            if ($db->Execute($query)) {
                $success++;
            } else {
                echo "Failed to update Rotation Schedule ACL entries";
                $fail++;
            }
        } else {
            /**
             * None of the required rotationschedule entries were found, so add them
             */
            $query = "INSERT INTO `" . AUTH_DATABASE . "`.`acl_permissions` (`permission_id`, `resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
                      VALUES
                          (NULL, 'rotationschedule', NULL, 'group:role', 'faculty:director', NULL, 1, 1, 1, 1, 'RotationScheduleOwner'),
	                      (NULL, 'rotationschedule', NULL, 'group:role', 'staff:pcoordinator', NULL, 1, 1, 1, 1, 'RotationScheduleOwner')";
            if ($db->Execute($query)) {
                $success++;
            } else {
                echo "Failed to insert Rotation Schedule ACL entries";
                $fail++;
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

        /**
         * Remove the assertion from any rotationschedule entries that are organisation specific
         * Note this is not the exact opposite of the 'up' method, because there is no way to know if the entries existed before
         */
        $query = "UPDATE `" . AUTH_DATABASE . "`.`acl_permissions`
                  SET `assertion` = NULL
                  WHERE `resource_type` = 'rotationschedule'
                  AND `resource_value` IS NULL
                  AND `entity_type` LIKE '%group:role' 
                  AND (`entity_value` LIKE '%faculty:director' OR `entity_value` LIKE '%staff:pcoordinator')
                  AND `app_id` IS NULL
                  AND `create` = 1
                  AND `read` = 1
                  AND `update` = 1
                  AND `delete` = 1";
        if ($db->Execute($query)) {
            $success++;
        } else {
            echo "Failed to update Rotation Schedule ACL entries";
            $fail++;
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

        $query = "SELECT * FROM `" . AUTH_DATABASE . "`.`acl_permissions` 
                  WHERE `resource_type` = 'rotationschedule'
                  AND `resource_value` IS NULL
                  AND `entity_type` LIKE '%group:role' 
                  AND (`entity_value` LIKE '%faculty:director' OR `entity_value` LIKE '%staff:pcoordinator')
                  AND `app_id` IS NULL
                  AND `create` = 1
                  AND `read` = 1
                  AND `update` = 1
                  AND `delete` = 1
                  AND `assertion` = 'RotationScheduleOwner'";

        if ($db->GetRow($query)) {
            return 1;
        }
        return 0;
    }
}
