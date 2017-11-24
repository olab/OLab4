<?php
class Migrate_2016_06_10_201813_900 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `exam_lu_question_bank_folder_images` (`image_id`, `file_name`, `color`, `order`, `deleted_date`)
        VALUES
        (1,'list-folder-1.png','light blue',1,NULL),
        (2,'list-folder-2.png','medium blue',2,NULL),
        (3,'list-folder-3.png','teal',3,NULL),
        (4,'list-folder-4.png','yellow green',4,NULL),
        (5,'list-folder-5.png','medium green',5,NULL),
        (6,'list-folder-6.png','dark green',6,NULL),
        (7,'list-folder-7.png','light yellow',7,NULL),
        (8,'list-folder-8.png','yellow',8,NULL),
        (9,'list-folder-9.png','orange',9,NULL),
        (10,'list-folder-10.png','dark orange',10,NULL),
        (11,'list-folder-11.png','red',11,NULL),
        (12,'list-folder-12.png','magenta',12,NULL),
        (13,'list-folder-13.png','light pink',13,NULL),
        (14,'list-folder-14.png','pink',14,NULL),
        (15,'list-folder-15.png','light purple',15,NULL),
        (16,'list-folder-16.png','purple',16,NULL),
        (17,'list-folder-17.png','cream',17,NULL),
        (18,'list-folder-18.png','light brown',18,NULL),
        (19,'list-folder-19.png','medium brown',19,NULL),
        (20,'list-folder-20.png','dark blue',20,NULL);

        INSERT INTO `exam_lu_questiontypes` (`questiontype_id`, `shortname`, `name`, `description`, `order`, `deleted_date`)
        VALUES
        (1,'mc_h','Multiple Choice Horizontal','A Multiple Choice Question layed out horizontaly',3,NULL),
        (2,'mc_v','Multiple Choice Vertical','A Multiple Choice Question layed out verticaly',1,NULL),
        (3,'short','Short Answer','A Short Answer or Fill in the bank question, correct answers can be added to the system.',5,NULL),
        (4,'essay','Essay','A long form essay question, graded manually',6,NULL),
        (5,'match','Matching','A question type where you identify from a list of options',7,NULL),
        (6,'text','Text','Instructional or Information text to display to the student, no answer.',10,NULL),
        (7,'mc_h_m','Multiple Choice Horizontal (multiple responses)','A Multiple Choice Question layed out horizontaly, with checkboxes for multipule anwsers.',4,NULL),
        (8,'mc_v_m','Multiple Choice Vertical (multiple responses)','A Multiple Choice Question layed out verticaly, with checkboxes for multipule anwsers.',2,NULL),
        (9,'drop_s','Drop Down','The dropdown allows students to answer each question by choosing one of up to 100 options which have been provided to populate a select box.',8,1441323576),
        (10,'drop_m','Drop Down (multiple responses)','The dropdown allows students to answer each question by choosing multiple options which have been provided to populate a select box.',9,1441323576),
        (11,'fnb','Fill in the Blank','A question type composed of short answers in a paragraph form with predefined correct options for the short answers.',11,NULL);
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
        DELETE FROM `exam_lu_questiontypes`
        WHERE `shortname` = 'mc_h'
        OR `shortname` = 'mc_v'
        OR `shortname` = 'short'
        OR `shortname` = 'essay'
        OR `shortname` = 'match'
        OR `shortname` = 'text'
        OR `shortname` = 'mc_h_m'
        OR `shortname` = 'mc_v_m'
        OR `shortname` = 'drop_s'
        OR `shortname` = 'drop_m'
        OR `shortname` = 'fnb';

        DELETE FROM `exam_lu_question_bank_folder_images`
        WHERE `file_name` = 'list-folder-1.png'
        OR `file_name` = 'list-folder-1.png'
        OR `file_name` = 'list-folder-2.png'
        OR `file_name` = 'list-folder-3.png'
        OR `file_name` = 'list-folder-4.png'
        OR `file_name` = 'list-folder-5.png'
        OR `file_name` = 'list-folder-6.png'
        OR `file_name` = 'list-folder-7.png'
        OR `file_name` = 'list-folder-8.png'
        OR `file_name` = 'list-folder-9.png'
        OR `file_name` = 'list-folder-10.png'
        OR `file_name` = 'list-folder-11.png'
        OR `file_name` = 'list-folder-12.png'
        OR `file_name` = 'list-folder-13.png'
        OR `file_name` = 'list-folder-14.png'
        OR `file_name` = 'list-folder-15.png'
        OR `file_name` = 'list-folder-16.png'
        OR `file_name` = 'list-folder-17.png'
        OR `file_name` = 'list-folder-18.png'
        OR `file_name` = 'list-folder-19.png'
        OR `file_name` = 'list-folder-20.png';
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
        $query = "  SELECT `shortname`
                    FROM `exam_lu_questiontypes`
                    WHERE `shortname` = 'mc_h'";

        if ($db->GetAll($query)) {
            return 1;
        } else {
            return 0;
        }
    }
}
