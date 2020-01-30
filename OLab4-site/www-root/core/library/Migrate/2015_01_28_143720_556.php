<?php
class Migrate_2015_01_28_143720_556 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `migrations` (
        `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `batch` int(11) NOT NULL,
        `success` int(4) NOT NULL DEFAULT '0',
        `fail` int(4) NOT NULL DEFAULT '0',
        `updated_date` bigint(64) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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
        DROP TABLE IF EXISTS `migrations`;
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
        if ($migration->tableExists(DATABASE_NAME, "migrations")) {
            return 1;
        }

        return 0;
    }
}
