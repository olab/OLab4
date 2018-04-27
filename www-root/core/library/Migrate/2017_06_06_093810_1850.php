<?php
class Migrate_2017_06_06_093810_1850 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS <?php echo DATABASE_NAME ?>.`objective_history` (
            `objective_history_id` int(12) NOT NULL AUTO_INCREMENT,
            `objective_id` int(10) NOT NULL DEFAULT '0',
            `proxy_id` int(10) NOT NULL DEFAULT '0',
            `history_message` varchar(300) NOT NULL,
            `history_display` int(1) NOT NULL DEFAULT '0',
            `history_timestamp` bigint(64) NOT NULL DEFAULT '0',
            CONSTRAINT `pk_objective_history` PRIMARY KEY (`objective_history_id`)
         ) engine=InnoDB;

        CREATE INDEX `idx_objective_history` ON <?php echo DATABASE_NAME ?>.`objective_history` ( `objective_id` );

        CREATE TABLE IF NOT EXISTS <?php echo DATABASE_NAME ?>.`language` (
            `language_id` int(12) SIGNED NOT NULL AUTO_INCREMENT,
            `iso_6391_code` varchar(2)  NOT NULL  ,
            CONSTRAINT `pk_language` PRIMARY KEY (`language_id`)
         ) engine=InnoDB;

        DROP TABLE IF EXISTS `objective_translation`;
        /*!40101 SET @saved_cs_client     = @@character_set_client */;
        /*!40101 SET character_set_client = utf8 */;
        CREATE TABLE `objective_translation` (
          `objective_translation_id` int(12) NOT NULL AUTO_INCREMENT,
          `objective_id` int(12) NOT NULL,
          `language_id` int(12) NOT NULL,
          `objective_name` text,
          `objective_description` text,
          `updated_date` bigint(20) NOT NULL,
          `updated_by` int(11) NOT NULL,
          PRIMARY KEY (`objective_translation_id`),
          KEY `idx_objective_translation` (`objective_id`),
          KEY `idx_objective_translation_1` (`language_id`),
          CONSTRAINT `fk_objective_translation_0` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
        ) ENGINE=InnoDB AUTO_INCREMENT=11983 DEFAULT CHARSET=utf8;
        /*!40101 SET character_set_client = @saved_cs_client */;

        CREATE TABLE IF NOT EXISTS <?php echo DATABASE_NAME ?>.`objective_status` (
            `objective_status_id` int(12) SIGNED NOT NULL AUTO_INCREMENT,
            `objective_status_description` varchar(50) NOT NULL,
            `updated_date` bigint  NOT NULL ,
            `updated_by` int NOT NULL,
            CONSTRAINT `pk_objective_status` PRIMARY KEY (`objective_status_id`)
         ) engine=InnoDB;

        INSERT INTO <?php echo DATABASE_NAME ?>.`objective_status` (`objective_status_id`,`objective_status_description`,`updated_date`,`updated_by`) VALUES (1,'Draft',0,0);
        INSERT INTO <?php echo DATABASE_NAME ?>.`objective_status` (`objective_status_id`,`objective_status_description`,`updated_date`,`updated_by`) VALUES (2,'Active',0,0);
        INSERT INTO <?php echo DATABASE_NAME ?>.`objective_status` (`objective_status_id`,`objective_status_description`,`updated_date`,`updated_by`) VALUES (3,'Retired',0,0);

        CREATE TABLE IF NOT EXISTS <?php echo DATABASE_NAME ?>.`global_objective_note` (
            `global_objective_note_id` int(12) SIGNED NOT NULL AUTO_INCREMENT,
            `objective_id` int(12) SIGNED NOT NULL,
            `global_objective_note` varchar(600) NOT NULL,
            `updated_date` bigint  NOT NULL,
            `updated_by` int  NOT NULL,
            CONSTRAINT `pk_global_objective_note` PRIMARY KEY (`global_objective_note_id`)
         ) engine=InnoDB;

        CREATE INDEX `idx_global_objective_note` ON <?php echo DATABASE_NAME ?>.`global_objective_note` (`objective_id`);

        CREATE TABLE IF NOT EXISTS <?php echo DATABASE_NAME ?>.`objective_translation_status` (
            `objective_translation_status_id` int(12) SIGNED NOT NULL AUTO_INCREMENT,
            `objective_translation_status_description` varchar(30)  NOT NULL,
            `updated_date` bigint  NOT NULL,
            `updated_by` int NOT NULL,
            CONSTRAINT `pk_objective_translation_status` PRIMARY KEY (`objective_translation_status_id`)
         ) engine=InnoDB;

        ALTER TABLE <?php echo DATABASE_NAME ?>.`objective_translation_status` COMMENT 'A status of requested or completed and possibly not required.';

        ALTER TABLE <?php echo DATABASE_NAME ?>.`global_lu_objectives` ADD COLUMN `non_examinable` int NOT NULL DEFAULT '0' AFTER `objective_active`;
        ALTER TABLE <?php echo DATABASE_NAME ?>.`global_lu_objectives` ADD COLUMN `objective_status_id` int(11) NOT NULL DEFAULT '2' AFTER `non_examinable`;
        ALTER TABLE <?php echo DATABASE_NAME ?>.`global_lu_objectives` ADD COLUMN `admin_notes` varchar(600) AFTER `objective_status_id`;
        ALTER TABLE <?php echo DATABASE_NAME ?>.`global_lu_objectives` ADD COLUMN `objective_translation_status_id` int(12) NOT NULL DEFAULT '0' AFTER `admin_notes`;

        INSERT INTO <?php echo DATABASE_NAME ?>.`language` (`language_id`,`iso_6391_code`) VALUES (1,'en');
        INSERT INTO <?php echo DATABASE_NAME ?>.`language` (`language_id`,`iso_6391_code`) VALUES (2,'fr');

        INSERT INTO `<?php echo DATABASE_NAME ?>`.`objective_translation_status` (`objective_translation_status_id`, `objective_translation_status_description`, `updated_date`, `updated_by`) VALUES ('1', 'Requested', '1', '1');
        INSERT INTO `<?php echo DATABASE_NAME ?>`.`objective_translation_status` (`objective_translation_status_id`, `objective_translation_status_description`, `updated_date`, `updated_by`) VALUES ('2', 'Completed', '1', '1');
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
        DROP TABLE <?php echo DATABASE_NAME ?>.`objective_translation`;
        DROP TABLE <?php echo DATABASE_NAME ?>.`objective_history`;
        DROP TABLE <?php echo DATABASE_NAME ?>.`objective_status`;
        DROP TABLE <?php echo DATABASE_NAME ?>.`objective_translation_status`;
        DROP TABLE <?php echo DATABASE_NAME ?>.`global_objective_note`;
        DROP TABLE <?php echo DATABASE_NAME ?>.`language`;
        ALTER TABLE <?php echo DATABASE_NAME ?>.`global_lu_objectives` DROP COLUMN `non_examinable`;
        ALTER TABLE <?php echo DATABASE_NAME ?>.`global_lu_objectives` DROP COLUMN `objective_status_id`;
        ALTER TABLE <?php echo DATABASE_NAME ?>.`global_lu_objectives` DROP COLUMN `objective_translation_status_id`;
        ALTER TABLE <?php echo DATABASE_NAME ?>.`global_lu_objectives` DROP COLUMN `admin_notes`;
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
        if ($migration->tableExists(DATABASE_NAME, "objective_translation")) {
            if ($migration->tableExists(DATABASE_NAME, "objective_translation_status")) {
                if ($migration->tableExists(DATABASE_NAME, "objective_history")) {
                    if ($migration->tableExists(DATABASE_NAME, "objective_status")) {
                        if ($migration->tableExists(DATABASE_NAME, "language")) {
                            if ($migration->tableExists(DATABASE_NAME, "global_objective_note")) {
                                if ($migration->columnExists(DATABASE_NAME, "global_lu_objectives", "non_examinable")) {
                                    if ($migration->columnExists(DATABASE_NAME, "global_lu_objectives", "objective_status_id")) {
                                        if ($migration->columnExists(DATABASE_NAME, "global_lu_objectives", "objective_translation_status_id")) {
                                            if ($migration->columnExists(DATABASE_NAME, "global_lu_objectives", "objective_translation_status_id")) {
                                                if ($migration->columnExists(DATABASE_NAME, "global_lu_objectives", "objective_translation_status_id")) {
                                                    return 1;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return 0;
    }
}
