<?php
class Migrate_2016_04_05_145610_747 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        DELETE FROM `<?php echo AUTH_DATABASE ?>`.`acl_permissions`
            WHERE `resource_type` = 'poll'
            AND `entity_type` = 'role'
            AND `entity_value` = 'admin'
            AND `create` = 1
            AND `read` IS NULL
            AND `update` = 1
            AND `delete` = 1
            AND `assertion` IS NULL;
            
        INSERT INTO `<?php echo AUTH_DATABASE ?>`.`acl_permissions` 
            (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
            VALUES
            ('poll', NULL, 'group:role', 'faculty:admin', 1, 1, NULL, 1, 1, NULL);
            
        INSERT INTO `<?php echo AUTH_DATABASE ?>`.`acl_permissions` 
            (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
            VALUES
            ('poll', NULL, 'group:role', 'staff:admin', 1, 1, NULL, 1, 1, NULL);
        
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
        INSERT INTO `<?php echo AUTH_DATABASE ?>`.`acl_permissions` 
            (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
            VALUES
            ('poll', NULL, 'role', 'admin', 1, 1, NULL, 1, 1, NULL);
            
        DELETE FROM `<?php echo AUTH_DATABASE ?>`.`acl_permissions`
            WHERE `resource_type` = 'poll'
            AND `entity_type` = 'group:role'
            AND `entity_value` = 'faculty:admin'
            AND `create` = 1
            AND `read` IS NULL
            AND `update` = 1
            AND `delete` = 1
            AND `assertion` IS NULL;
            
        DELETE FROM `<?php echo AUTH_DATABASE ?>`.`acl_permissions`
            WHERE `resource_type` = 'poll'
            AND `entity_type` = 'group:role'
            AND `entity_value` = 'staff:admin'
            AND `create` = 1
            AND `read` IS NULL
            AND `update` = 1
            AND `delete` = 1
            AND `assertion` IS NULL;
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
        
        $continue = true;
        $query = "SELECT * FROM `" . AUTH_DATABASE . "`.`acl_permissions` WHERE `resource_type` = 'poll'
            AND `entity_type` = 'role'
            AND `entity_value` = 'admin'
            AND `create` = 1
            AND `read` IS NULL
            AND `update` = 1
            AND `delete` = 1
            AND `assertion` IS NULL";
        $rowresult = $db->GetRow($query);
        $continue = empty($rowresult);
        
        if ($continue) {
            $query = "SELECT * FROM `" . AUTH_DATABASE . "`.`acl_permissions` WHERE `resource_type` = 'poll'
            AND `entity_type` = 'group:role'
            AND `entity_value` = 'faculty:admin'
            AND `create` = 1
            AND `read` IS NULL
            AND `update` = 1
            AND `delete` = 1
            AND `assertion` IS NULL";
            $rowresult = $db->GetRow($query);
            $continue = !empty($rowresult);  
        }

        if ($continue) {
            $query = "SELECT * FROM `" . AUTH_DATABASE . "`.`acl_permissions` WHERE `resource_type` = 'poll'
            AND `entity_type` = 'group:role'
            AND `entity_value` = 'staff:admin'
            AND `create` = 1
            AND `read` IS NULL
            AND `update` = 1
            AND `delete` = 1
            AND `assertion` IS NULL";
            $rowresult = $db->GetRow($query);
            $continue = !empty($rowresult);
        }
        
        if ($continue) {
            return 1;
        }
        
        return 0;
    }
}
