<?php
class Migrate_2017_02_17_150147_1646 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `sandbox` (
        `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(128) NOT NULL DEFAULT '',
        `description` text,
        `created_date` bigint(64) unsigned DEFAULT NULL,
        `created_by` int(12) unsigned DEFAULT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(12) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_by` int(12) unsigned DEFAULT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `sandbox_contacts` (
        `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `sandbox_id` int(12) unsigned,
        `proxy_id` int(12) unsigned,
        PRIMARY KEY (`id`),
        KEY `sandbox_id` (`sandbox_id`),
        KEY `proxy_id` (`proxy_id`),
        CONSTRAINT `sandbox_ibfk_1` FOREIGN KEY (`sandbox_id`) REFERENCES `sandbox` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` (`permission_id`, `resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        (NULL, 'sandbox', NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, 'NotGuest'),
        (NULL, 'sandbox', NULL, 'group:role', 'staff:admin', 1, 1, NULL, 1, 1, NULL);
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
        SET FOREIGN_KEY_CHECKS=0; 
        
        DROP TABLE `sandbox_contacts`;  

        DROP TABLE `sandbox`;

        SET FOREIGN_KEY_CHECKS=1;

        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'sandbox';
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
        if ($migration->tableExists(DATABASE_NAME, "sandbox")) {
            if ($migration->tableExists(DATABASE_NAME, "sandbox_contacts")) {
                return 1;
            }
        }

        return 0;
    }
}
