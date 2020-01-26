<?php
class Migrate_2017_06_12_085412_1850 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;
        $group_id=0;
        $group = Models_System_Group::fetchRowByName("staff", 1);
        if ($group && is_array($group) && !empty($group["id"])) {
            $group_id = $group["id"];
        }
        
        $this->record();
        ?>

/* Remove roles from previous objective app that will not be used */
DELETE FROM <?php echo AUTH_DATABASE ?>.system_roles WHERE groups_id=300;

/* Rollback any previously inserted records in the acl_permissions table */
DELETE FROM <?php echo AUTH_DATABASE ?>.acl_permissions 
WHERE resource_type in ('objective', 'objectivehistory','objectivedetails','objectivenotes','objectiveattributes', 'curriculum');

DELETE FROM <?php echo AUTH_DATABASE ?>.system_roles WHERE role_name='translator';
INSERT INTO <?php echo AUTH_DATABASE ?>.system_roles (role_name, groups_id) VALUES ('translator', <?php echo $group_id ?>);

    
/* Resources defined
curriculum: means access to the Manage Curriculum module
objective: means access to the Manage Curriculum Tags sub-module and the objectives themselves
           create means access to the add button on the summary page,
           edit controls the save buttons on the add / edit page
           read gives them access to view the add/edit page in read only mode
objectivehistory: means the history tab on the add/edit faculty objective page
objectivenotes: means the Admin notes tab on the add/edit faculty objective page
objectivedetails: means the description fields of both languages, the status field
*/
INSERT INTO <?php echo AUTH_DATABASE ?>.acl_permissions (resource_type, entity_type, entity_value, app_id, `create`, `read`, `update`, `delete`) VALUES
/*Admin roles staff:admin and medtech:admin already have blanket access to all already in the table
  adding the pcoordinator to have crud access to all */
('curriculum', 'group:role', 'staff:pcoordinator', 1, 1, 1, 1, 1),
('objective', 'group:role', 'staff:pcoordinator', 1, 1, 1, 1, 1),
('objectivehistory', 'group:role', 'staff:pcoordinator', 1, 1, 1, 1, 1),
('objectivedetails', 'group:role', 'staff:pcoordinator', 1, 1, 1, 1, 1),
('objectivenotes', 'group:role', 'staff:pcoordinator', 1, 1, 1, 1, 1),
('objectiveattributes', 'group:role', 'staff:pcoordinator', 1, 1, 1, 1, 1),

/*Leadership roles have read only access to everything */
('curriculum', 'group:role', 'faculty:director', 1, 0, 1, 0, 0),
('objective', 'group:role', 'faculty:director', 1, 0, 1, 0, 0),
('objectivehistory', 'group:role', 'faculty:director', 1, 0, 1, 0, 0),
('objectivedetails', 'group:role', 'faculty:director', 1, 0, 1, 0, 0),
('objectivenotes', 'group:role', 'faculty:director', 1, 0, 1, 0, 0),
('objectiveattributes', 'group:role', 'faculty:director', 1, 0, 1, 0, 0),

('curriculum', 'group:role', 'faculty:faculty', 1, 0, 1, 0, 0),
('objective', 'group:role', 'faculty:faculty', 1, 0, 1, 0, 0),
('objectivedetails', 'group:role', 'faculty:faculty', 1, 0, 1, 0, 0),
('objectiveattributes', 'group:role', 'faculty:faculty', 1, 0, 1, 0, 0),
('objectivehistory', 'group:role', 'faculty:faculty', 1, 0, 0, 0, 0),
('objectivenotes', 'group:role', 'faculty:faculty', 1, 0, 0, 0, 0),


('curriculum', 'group:role', 'faculty:lecturer', 1, 0, 1, 0, 0),
('objective', 'group:role', 'faculty:lecturer', 1, 0, 1, 0, 0),
('objectivedetails', 'group:role', 'faculty:lecturer', 1, 0, 1, 0, 0),
('objectiveattributes', 'group:role', 'faculty:lecturer', 1, 0, 1, 0, 0),
('objectivehistory', 'group:role', 'faculty:lecturer', 1, 0, 0, 0, 0),
('objectivenotes', 'group:role', 'faculty:lecturer', 1, 0, 0, 0, 0),

('curriculum', 'group:role', 'resident:lecturer', 1, 0, 1, 0, 0),
('objective', 'group:role', 'resident:lecturer', 1, 0, 1, 0, 0),
('objectivedetails', 'group:role', 'resident:lecturer', 1, 0, 1, 0, 0),
('objectiveattributes', 'group:role', 'resident:lecturer', 1, 0, 1, 0, 0),
('objectivehistory', 'group:role', 'resident:lecturer', 1, 0, 0, 0, 0),
('objectivenotes', 'group:role', 'resident:lecturer', 1, 0, 0, 0, 0),

('curriculum', 'group:role', 'staff:staff', 1, 0, 1, 0, 0),
('objective', 'group:role', 'staff:staff', 1, 0, 1, 0, 0),
('objectivedetails', 'group:role', 'staff:staff', 1, 0, 1, 0, 0),
('objectiveattributes', 'group:role', 'staff:staff', 1, 0, 1, 0, 0),
('objectivehistory', 'group:role', 'staff:staff', 1, 0, 0, 0, 0),
('objectivenotes', 'group:role', 'staff:staff', 1, 0, 0, 0, 0),

('curriculum', 'group:role', 'medtech:staff', 1, 0, 1, 0, 0),
('objective', 'group:role', 'medtech:staff', 1, 0, 1, 0, 0),
('objectivedetails', 'group:role', 'medtech:staff', 1, 0, 1, 0, 0),
('objectiveattributes', 'group:role', 'medtech:staff', 1, 0, 1, 0, 0),
('objectivehistory', 'group:role', 'medtech:staff', 1, 0, 0, 0, 0),
('objectivenotes', 'group:role', 'medtech:staff', 1, 0, 0, 0, 0),

('curriculum', 'group', 'student', 1, 0, 1, 0, 0),
('objective', 'group', 'student', 1, 0, 1, 0, 0),
('objectivedetails', 'group', 'student', 1, 0, 1, 0, 0),
('objectiveattributes', 'group', 'student', 1, 0, 1, 0, 0),
('objectivehistory', 'group', 'student', 1, 0, 0, 0, 0),
('objectivenotes', 'group', 'student', 1, 0, 0, 0, 0),

('curriculum', 'group:role', 'staff:translator', 1, 0, 1, 0, 0),
('objective', 'group:role', 'staff:translator', 1, 0, 1, 0, 0),
('objectivehistory', 'group:role', 'staff:translator', 1, 0, 1, 0, 0),
('objectivedetails', 'group:role', 'staff:translator', 1, 0, 1, 1, 0),
('objectivenotes', 'group:role', 'staff:translator', 1, 0, 1, 1, 0),
('objectiveattributes', 'group:role', 'staff:translator', 1, 0, 0, 0, 0);

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
        
        /* Rollback any previously inserted records in the acl_permissions table */
        DELETE FROM <?php echo AUTH_DATABASE ?>.acl_permissions 
        WHERE resource_type in ('objective', 'objectivehistory','objectivedetails','objectivenotes','objectiveattributes', 'curriculum');
        
        DELETE FROM <?php echo AUTH_DATABASE ?>.system_roles WHERE role_name = 'translator';
        
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
            WHERE resource_type in ('objective', 'objectivehistory','objectivedetails','objectivenotes','objectiveattributes')";
        
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
