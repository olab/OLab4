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
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */

class Entrada_Cli_Help extends Entrada_Cli {
    /**
     * @var string The name of the command that was run.
     * @set Set by __construct().
     */
    protected $command = "";

    /**
     * @var array The valid actions that can be run with this utility.
     */
    protected $actions = array();

    public function __construct($command = "") {
        $this->command = $command;

        print "\n";
    }

    /**
     * Used to render the help menu for Entrada CLI.
     */
    public function playHelp() {
        print "The following commands are available in " . $this->color("Entrada CLI " . $this::CLI_VERSION, "yellow") . ":\n\n";

        foreach ($this->commands as $command => $help) {
            $col_pad = str_pad(" ", (15 - strlen($command)));

            print $this->color($command, "purple") . $col_pad . $this->color($help, "grey") . "\n";
        }

        print "\n";
    }
}