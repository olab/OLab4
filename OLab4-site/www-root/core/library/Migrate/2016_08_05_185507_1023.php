<?php
class Migrate_2016_08_05_185507_1023 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `<?php echo AUTH_DATABASE; ?>`.`acl_permissions`(
            `resource_type`,
            `resource_value`,
            `entity_type`,
            `entity_value`,
            `app_id`,
            `create`,
            `read`,
            `update`,
            `delete`,
            `assertion`
        )
        VALUES
            ('unitcontent', NULL, 'group:role', 'medtech:admin', 1, 1, 1, 1, 1, NULL),
            ('unitcontent', NULL, 'group', 'staff', 1, NULL, NULL, 1, 1, 'CourseUnitOwner'),
            ('unitcontent', NULL, 'group:role', 'staff:pcoordinator', 1, 1, 1, NULL, NULL, NULL),
            ('unitcontent', NULL, 'group:role', 'staff:admin', 1, 1, 1, NULL, NULL, NULL),
            ('unitcontent', NULL, 'group', 'staff', 1, NULL, 1, NULL, NULL, NULL),
            ('unitcontent', NULL, 'group', 'faculty', 1, NULL, NULL, 1, 1, 'CourseUnitOwner'),
            ('unitcontent', NULL, 'group:role', 'faculty:admin', 1, 1, 1, NULL, NULL, NULL),
            ('unitcontent', NULL, 'group:role', 'faculty:director', 1, 1, 1, NULL, NULL, NULL),
            ('unitcontent', NULL, 'group', 'faculty', 1, NULL, 1, NULL, NULL, NULL),
            ('unitcontent', NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, 'NotGuest'),
            ('weekcontent', NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, 'NotGuest');
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
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions`
        WHERE `resource_type` = 'unitcontent'
        AND `app_id` = 1;
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
        $query = "
            SELECT 1
            FROM `".AUTH_DATABASE."`.`acl_permissions`
            WHERE `resource_type` = ?
            AND `app_id` = 1";
        $result = $db->GetRow($query, array('unitcontent'));
        if ($result) {
            return 1;
        } else {
            return 0;
        }
    }
}
