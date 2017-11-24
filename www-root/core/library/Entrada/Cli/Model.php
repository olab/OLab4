<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Cli_Model extends Entrada_Cli {
    protected $command = "";

    /**
     * @var array The valid actions that can be run with this utility.
     */
    protected $actions = array(
        "create" => "\tCreate a new Entrada database model using an interactive session.\n\t\t\tThe table to be modelled must be present in the database.",
        "help" => "\tSpit out this very brief help information.",
    );

    private $description;
    private $fullname;
    private $email;
    private $organisation;
    private $primary_id = false;
    private $variables;
    private $table_name;
    private $active_field = false;
    private $sort_column;
    private $class_name;
    private $class_folders;
    private $class_contents;
    private $database_name;
    private $database_display_name;

    private static $header = <<<HEADERMESSAGE
<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * %DESCRIPTION%
 *
 * @author Organisation: %ORGANISATION%
 * @author Developer: %FULLNAME% <%EMAIL%>
 * @copyright Copyright %YEAR% %ORGANISATION%. All Rights Reserved.
 */
HEADERMESSAGE;

    public function playCreate() {
        $this->getTable();
        $this->getActiveField();
        $this->getModelName();
        $this->getAuthorDetails();
        $this->getModelDetails();
        $this->createModel();
    }

    public function playHelp() {
        $this->renderActionHelp($this->command, $this->actions);
    }
    
    protected function getTable() {
        global $db;

        print "\nStep 1 - Please select the database which contains the table you would like to model [or leave blank to use `".DATABASE_NAME."`]: ";
        print "\n";
        print "\n   1) DATABASE_NAME      [" . DATABASE_NAME . "] ";
        print "\n   2) AUTH_DATABASE      [" . AUTH_DATABASE . "] ";
        print "\n   3) CLERKSHIP_DATABASE [" . CLERKSHIP_DATABASE . "] ";
        print "\n\nEnter a number between 1 and 3 or press enter to accept the default of \"" . DATABASE_NAME . "\": ";

        fscanf(STDIN, "%i\n", $active_field_index);

        switch ($active_field_index) {
            case 1:
            default:
                $this->database_display_name = "DATABASE_NAME";
                $this->database_name = DATABASE_NAME;
                break;
            case 2:
                $this->database_display_name = "AUTH_DATABASE";
                $this->database_name = AUTH_DATABASE;
                break;
            case 3:
                $this->database_display_name = "CLERKSHIP_DATABASE";
                $this->database_name = CLERKSHIP_DATABASE;
        }

        print "\n          Please enter the full name of the database table you want to create a model for: ";
        fscanf(STDIN, "%s\n", $this->table_name);
        $this->table_name = clean_input($this->table_name, array("trim", "module"));
        $table_fields = $db->getAll("DESCRIBE `".$this->database_name."`.`".$this->table_name."`");
        while (!$table_fields) {
            print "\n[Error] Please ensure you enter a table which already exists. The table you have entered was not found: ";
            fscanf(STDIN, "%s\n", $this->table_name);
            $this->table_name = clean_input($this->table_name, array("trim", "module"));
            $table_fields = $db->getAll("DESCRIBE `".$this->database_name."`.`".$this->table_name."`");
        }

        if ($table_fields) {
            $variables = array();
            foreach ($table_fields as $table_field) {
                if (@count($variables) && !isset($this->sort_column)) {
                    $this->sort_column = $table_field["Field"];
                }
                if ($table_field["Key"] == "PRI" && !$this->primary_id) {
                    $this->primary_id = $table_field["Field"];
                }
                $variables[] = $table_field["Field"];
            }

            if (isset($variables) && $variables) {
                $this->variables = $variables;
            }
            $this->class_folders = array();
            $folders = explode("_", $this->table_name);
            foreach ($folders as $folder) {
                $this->class_folders[] = ucfirst($folder);
            }
            $this->class_name = "Models_".str_replace(" ", "_", ucwords(str_replace("_", " ", $this->table_name)));
        }
    }

    protected function getActiveField() {
        print "\nStep 2 - Please select which of the following fields indicates a record is active (if none exists, just press enter)";
        $count = 0;
        foreach ($this->variables as $variable) {
            $count++;
            print "\n   ".$count.") ".$variable;
        }
        print "\n\nEnter a number between 0 and ".$count.": ";
        fscanf(STDIN, "%i\n", $active_field_index);

        while ($active_field_index != 0 && !array_key_exists(($active_field_index - 1), $this->variables)) {
            print print_r($this->variables, 1).($active_field_index - 1)."\n[ERROR] Please ensure you select which field indicates a record is active (if none exists, just press enter)";
            $count = 0;
            foreach ($this->variables as $variable) {
                $count++;
                print "\n   ".$count.") ".$variable;
            }
            print "\n\nEnter a number between 0 and ".$count.": ";
            fscanf(STDIN, "%i\n", $active_field_index);
        }

        if ($active_field_index) {
            $this->active_field = $this->variables[($active_field_index - 1)];
        }
    }

    protected function getModelName() {
        print "\nStep 3 - Please confirm whether the current location where the Model will be created is correct.\n";
        print "\n         ".ENTRADA_ABSOLUTE."/core/library/Models/".implode("/", $this->class_folders).".php\n";
        print "\n         [Yes to continue, or No to enter a different location]: ";
        fscanf(STDIN, "%s\n", $folders_correct);
        while (strtolower($folders_correct) != "y" && strtolower($folders_correct) != "yes" && strtolower($folders_correct) != "n" && strtolower($folders_correct) != "no") {
            print "\n         [Please enter Yes to continue, or No to enter a different location]: ";
            fscanf(STDIN, "%s\n", $folders_correct);
        }
        if (strtolower($folders_correct) != "y" && strtolower($folders_correct) != "yes") {
            print "\nStep 3.5 - Please enter a new name for the model being created.\n";
            print "             This will be used to build the path where the model will be created, where each word will be a folder under '".ENTRADA_ABSOLUTE."/core/library/', except for the last word- which will be the name of the model file itself.\n";
            print "             [eg. Models_Event_Audience will create the following file: '".ENTRADA_ABSOLUTE."/core/library/Models/Event/Audience.php']: ";
            fscanf(STDIN, "%s\n", $new_class_name);
            if ($new_class_name && strstr($new_class_name, "Models_") !== false) {
                $this->setModelName($new_class_name);
            }
            while (strtolower($new_class_name) != strtolower($this->class_name) || @file_exists(ENTRADA_ABSOLUTE."/".implode("/", $this->class_folders).".php")) {
                if (strtolower($new_class_name) != strtolower($this->class_name)) {
                    print "\nPlease ensure that the new model name starts with 'Models_' so that it can be loaded automatically when used: ";
                } else {
                    print "\nA model already exists at '".ENTRADA_ABSOLUTE."/".implode("/", $this->class_folders).".php', please enter a new model name: ";
                }
                fscanf(STDIN, "%s\n", $new_class_name);
                if ($new_class_name && strstr($new_class_name, "Models_") !== false) {
                    $this->setModelName($new_class_name);
                }
            }
        }
    }

    protected function setModelName($new_class_name) {
        if (strstr($new_class_name, "Models_") !== false) {
            $new_folders = explode("_", $new_class_name);
            $models_skipped = false;
            $this->class_folders = array();
            $this->class_name = "Models";
            foreach ($new_folders as $new_folder) {
                if ($models_skipped) {
                    $this->class_folders[] = ucfirst($new_folder);
                    $this->class_name .= "_".ucfirst($new_folder);
                } else {
                    $models_skipped = true;
                }
            }
        }
    }

    protected function getAuthorDetails() {
        print "\nStep 4 - Please enter some personal information to be used in the comment block.";
        print "\n         Full name [e.g. Joe Developer]: ";
        $fullname = trim(fgets(STDIN));
        print "\n         Email Address [e.g. joe.developer@yourschool.edu]: ";
        fscanf(STDIN, "%s\n", $email);
        print "\n         Organisation [e.g. Your University]: ";
        $organisation = trim(fgets(STDIN));
        $this->fullname = ($fullname ? $fullname : NULL);
        $this->email = ($email ? $email : NULL);
        $this->organisation = ($organisation ? $organisation : NULL);
    }

    protected function getModelDetails() {
        print "\nStep 5 - Please enter some additional (optional) information about the model being created.";
        print "\n         Description (to be used in the comment block) [e.g. 'A model for handling Event Audiences']: ";
        $description = trim(fgets(STDIN));
        $this->description = ($description ? $description : NULL);
        print "\n         Default column to order by [Currently ".$this->sort_column.", leave blank to use this]: ";
        fscanf(STDIN, "%s\n", $sort_column);
        while ($sort_column && !in_array($sort_column, $this->variables)) {
            print "\n         Please ensure you enter a column from the table, or simply press enter to use the default [available fields: ".implode(", ", $this->variables)."]: ";
            fscanf(STDIN, "%s\n", $sort_column);
        }
        $this->sort_column = ($sort_column ? $sort_column : $this->sort_column);
    }

    protected function createModel() {$search = array(
        "%DESCRIPTION%",
        "%FULLNAME%",
        "%EMAIL%",
        "%YEAR%",
        "%ORGANISATION%"
    );
        $replace = array(
            $this->description,
            $this->fullname,
            $this->email,
            date("Y"),
            $this->organisation
        );

        $this->class_contents = str_replace($search, $replace, $this::$header);
        $this->class_contents .= "\n\n";
        $this->class_contents .= "class ".$this->class_name." extends Models_Base {\n\n";
        foreach ($this->variables as $variable) {
            $this->class_contents .= "    protected \$".$variable.";\n";
        }
        $this->class_contents .= "\n";
        if (!empty($this->database_name) && !empty($this->database_display_name)) {
            $this->class_contents .= "    protected static \$database_name = " . $this->database_display_name . ";\n";
        }
        $this->class_contents .= "    protected static \$table_name = \"".$this->table_name."\";\n";
        $this->class_contents .= "    protected static \$primary_key = \"".$this->primary_id."\";\n";
        $this->class_contents .= "    protected static \$default_sort_column = \"".$this->sort_column."\";\n\n";
        $this->class_contents .= "    public function __construct(\$arr = NULL) {\n";
        $this->class_contents .= "        parent::__construct(\$arr);\n";
        $this->class_contents .= "    }\n\n";
        // write a getter for the primary id, unless there is a field 'id' already in the database
        if (!in_array("id", $this->variables, true)) {
            $this->class_contents .= "    public function getID() {\n";
            $this->class_contents .= "        return \$this->".$this->primary_id.";\n";
            $this->class_contents .= "    }\n\n";
        }
        foreach ($this->variables as $variable) {
            $variable_name_formatted = str_replace(" ", "", preg_replace("/ Id$/", "ID", ucwords(str_replace("_", " ", $variable))));
            // getter
            $this->class_contents .= "    public function get".$variable_name_formatted."() {\n";
            $this->class_contents .= "        return \$this->".$variable.";\n";
            $this->class_contents .= "    }\n\n";
            // setter
            $this->class_contents .= "    public function set".$variable_name_formatted."(\$".$variable.") {\n";
            $this->class_contents .= "        \$this->".$variable." = \$".$variable.";\n";
            $this->class_contents .= "    }\n\n";
        }
        if ($this->primary_id) {
            $this->class_contents .= "    public static function fetchRowByID(\$".$this->primary_id.($this->active_field ? ", $".$this->active_field : "").") {\n";
            $this->class_contents .= "        \$self = new self();\n";
            $this->class_contents .= "        return \$self->fetchRow(array(\n";
            $this->class_contents .= "            array(\"key\" => \"".$this->primary_id."\", \"method\" => \"=\", \"value\" => \$".$this->primary_id.")".($this->active_field ? "," : "")."\n";
            if ($this->active_field) {
                $this->class_contents .= "            array(\"key\" => \"".$this->active_field."\", \"method\" => \"=\", \"value\" => \$".$this->active_field.")\n";
            }
            $this->class_contents .= "        ));\n";
            $this->class_contents .= "    }\n\n";
        }
        $this->class_contents .= "    public static function fetchAllRecords(".($this->active_field ? "\$".$this->active_field : "").") {\n";
        $this->class_contents .= "        \$self = new self();\n";
        $this->class_contents .= "        return \$self->fetchAll(array(".($this->active_field ? "array(\"key\" => \"".$this->active_field."\", \"method\" => \"=\", \"value\" => \$".$this->active_field.")" : "array(\"key\" => \"".$this->primary_id."\", \"method\" => \">=\", \"value\" => 0)")."));\n";
        $this->class_contents .= "    }\n\n";
        if (in_array("deleted_date", $this->variables, true)) {
            $this->class_contents .= "    public function delete() {\n";
            if (in_array("deleted_by", $this->variables, true) || in_array("updated_by", $this->variables, true)) {
                $this->class_contents .= "        global \$ENTRADA_USER;\n\n";
            }
            $this->class_contents .= "        \$this->deleted_date = time();\n";
            if (in_array("deleted_by", $this->variables, true)) {
                $this->class_contents .= "        \$this->deleted_by = \$ENTRADA_USER->getActiveId();\n";
            }
            if (in_array("updated_date", $this->variables, true)) {
                $this->class_contents .= "        \$this->updated_date = time();\n";
            }
            if (in_array("updated_by", $this->variables, true)) {
                $this->class_contents .= "        \$this->updated_by = \$ENTRADA_USER->getActiveId();\n";
            }
            $this->class_contents .= "\n        return \$this->update();\n";
            $this->class_contents .= "    }\n\n";
        }
        $this->class_contents .= "}";
        print "\nFinal Step - The contents of the model file have been created, please confirm you would like to create this model. [Yes to confirm, No to cancel, or View to output the contents of the file that will be created]: ";
        fscanf(STDIN, "%s\n", $create);
        while (strtolower($create) != "y" && strtolower($create) != "yes" && strtolower($create) != "n" && strtolower($create) != "no") {
            if (strtolower($create) == "v" || strtolower($create) == "view") {
                echo "\n\n".$this->class_contents."\n\n";
            }
            print "\nYou may now finish the automated creation of your new model, or cancel. [Yes to confirm, No to cancel, or View to output the contents of the file that will be created]:";
            fscanf(STDIN, "%s\n", $create);
        }
        if (strtolower($create) == "y" || strtolower($create) == "yes") {
            $folder_path = ENTRADA_ABSOLUTE."/core/library/Models";
            foreach ($this->class_folders as $key => $folder) {
                $folder_path .= "/".$folder;
                if ($key < (count($this->class_folders) - 1) && !@is_dir($folder_path)) {
                    mkdir($folder_path);
                } elseif ($key == (count($this->class_folders) - 1)) {
                    $proceed = true;
                    if (@file_exists($folder_path.".php")) {
                        print "\nWarning - the Model file you have requested already exists. Do you want to overwrite? [Yes to confirm, No to cancel]: ";
                        fscanf(STDIN, "%s\n", $overwrite);
                        if (!(strtolower($create) == "y" || strtolower($create) == "yes")) {
                            $proceed = false;
                        }
                    }
                    if ($proceed) {
                        if (file_put_contents($folder_path.".php", $this->class_contents)) {
                            print "\nNew Model file [".$folder_path.".php] created successfully!\n";
                        } else {
                            print "\nAn error was encountered and the new Model file [".$folder_path.".php] could not be created.\n";
                        }
                    } else {
                        print "\nThe Model was not written, as requested. ";
                    }

                }
            }
        }
    }
}
