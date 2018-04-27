<?php
class Migrate_2017_06_20_140212_2041 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `assessments_lu_meta_scoring` (
            `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(60) NOT NULL,
            `short_name` varchar(60) NOT NULL,
            `active` tinyint(1) unsigned DEFAULT '1',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        INSERT INTO `assessments_lu_meta_scoring` (`title`, `short_name`, `active`)
        VALUES
            ('Show first score', 'first', 1),
            ('Show highest score', 'highest', 1),
            ('Show average of all scores', 'average', 1),
            ('Show latest score', 'latest', 1)
        ;

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
        DROP TABLE `assessments_lu_meta_scoring`;
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
        if ($migration->tableExists(DATABASE_NAME, "assessments_lu_meta_scoring")) {
            return 1;
        }
        return 0;
    }
}
