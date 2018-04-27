<?php
class Migrate_2017_12_12_114856_2507 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `global_lu_sites` (
        `site_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `site_code` varchar(16) NOT NULL DEFAULT '',
        `site_name` varchar(128) NOT NULL DEFAULT '',
        `site_address1` varchar(128) NOT NULL DEFAULT '',
        `site_address2` varchar(128) DEFAULT NULL,
        `site_city` varchar(64) NOT NULL DEFAULT '',
        `site_province_id` int(11) unsigned DEFAULT NULL,
        `site_country_id` int(11) unsigned NOT NULL,
        `site_postcode` varchar(16) NOT NULL DEFAULT '',
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) NOT NULL,
        `updated_by` int(11) NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        `deleted_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`site_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `global_lu_sites_organisation` (
        `site_id` int(11) unsigned NOT NULL,
        `organisation_id` int(11) unsigned NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `settings` (`shortname`, `organisation_id`, `value`) VALUES ('sites_enabled', NULL, '0');

        ALTER TABLE `global_lu_buildings` MODIFY COLUMN `building_address2` varchar(128) DEFAULT NULL;
        ALTER TABLE `global_lu_buildings` ADD COLUMN `building_country_id` int(11) unsigned DEFAULT NULL AFTER `building_country`;
        ALTER TABLE `global_lu_buildings` ADD COLUMN `building_province_id` int(11) unsigned DEFAULT NULL AFTER `building_country`;
        ALTER TABLE `global_lu_buildings` ADD COLUMN `site_id` int(11) unsigned DEFAULT NULL AFTER `organisation_id`;

        <?php

        if ($organisations = Models_Organisation::fetchAllOrganisations()) {
           foreach ($organisations as $organisation) {
               ?>
               INSERT INTO `global_lu_sites` (`site_code`, `site_name`, `site_address1`, `site_address2`, `site_city`, `site_province_id`, `site_country_id`, `site_postcode`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`, `deleted_by`)
               VALUES
               ('', '<?php echo $organisation["organisation_title"]; ?>', '<?php echo $organisation["organisation_address1"]; ?>', '<?php echo $organisation["organisation_address2"]; ?>', '<?php echo $organisation["organisation_city"]; ?>', 0, 39, '<?php echo $organisation["organisation_postcode"]; ?>', <?php echo time(); ?>, 0, <?php echo time(); ?>, 0, NULL, NULL);

               INSERT INTO `global_lu_sites_organisation` (`site_id`, `organisation_id`)
               VALUES
               (LAST_INSERT_ID(), <?php echo $organisation["organisation_id"]; ?>);

               UPDATE `global_lu_buildings` SET `site_id` = (SELECT site_id FROM `global_lu_sites_organisation` WHERE `organisation_id` = <?php echo $organisation["organisation_id"]; ?>) WHERE `organisation_id` = <?php echo $organisation["organisation_id"]; ?>;

               <?php
           }
        }
        ?>
        ALTER TABLE `global_lu_buildings` DROP COLUMN `organisation_id`;

        UPDATE `global_lu_buildings` b
        LEFT JOIN `global_lu_countries` c
        ON c.`country` = b.`building_country`
        LEFT JOIN `global_lu_provinces` p
        ON p.`province` = b.`building_province`
        AND p.`country_id` = c.`countries_id`
        SET `building_province_id` = p.`province_id`, `building_country_id` = c.`countries_id`
        WHERE `building_province_id` IS NULL
        AND `building_country_id` IS NULL;
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
        DROP TABLE `global_lu_sites`;
        DROP TABLE `global_lu_sites_organisation`;
        DELETE FROM `settings` WHERE `shortname` = 'sites_enabled';
        ALTER TABLE `global_lu_buildings` MODIFY COLUMN `building_address2` varchar(128) NOT NULL DEFAULT '';
        ALTER TABLE `global_lu_buildings` DROP COLUMN `building_country_id`;
        ALTER TABLE `global_lu_buildings` DROP COLUMN `building_province_id`;
        ALTER TABLE `global_lu_buildings` DROP COLUMN `site_id`;
        ALTER TABLE `global_lu_buildings` ADD COLUMN `organisation_id` int(11) unsigned NOT NULL AFTER `building_id`;
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
        $settings = new Entrada_Settings();
        $migrate = new Models_Migration();

        if (($settings->read("sites_enabled") !== false) && $migrate->tableExists(DATABASE_NAME, "global_lu_sites") && $migrate->tableExists(DATABASE_NAME, "global_lu_sites_organisation")) {
            if ($migrate->columnExists(DATABASE_NAME, "global_lu_buildings", "building_country_id") && $migrate->columnExists(DATABASE_NAME, "global_lu_buildings", "building_province_id")) {
                return 1;
            }
        }

        return 0;
    }
}
