<?php
class Migrate_2017_07_20_111114_1850 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        -- SQL Upgrade Queries Here;
        UPDATE <?php echo AUTH_DATABASE ?>.acl_permissions SET `update`=1 WHERE resource_type='curriculum' AND entity_value='faculty:director';
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
        UPDATE <?php echo AUTH_DATABASE ?>.acl_permissions SET `update`=0 WHERE resource_type='curriculum' AND entity_value='faculty:director';
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
        
        $query = 
            "SELECT COUNT(*) as tableCount FROM " . AUTH_DATABASE . ".acl_permissions 
            WHERE resource_type='curriculum' AND entity_value='faculty:director' AND `update`=1";
        
        $rowresult = $db->GetRow ( $query );
        
        if ($rowresult) {
            $continue = ($rowresult['tableCount'] > 0);
        } else {
            $continue = false;
        }
        
        if ($continue) {
            return 1;
        }
        
        return 0;
    }
}
