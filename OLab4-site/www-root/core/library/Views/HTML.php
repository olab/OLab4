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
 * Base view class for rendering php-generated HTML output.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_HTML extends Views_Base {

    protected $id, $class;

    /**
     * Get relevant html ID.
     *
     * @return string
     */
    public function getID() {
        return $this->id;
    }

    /**
     * Set relevant html ID.
     *
     * @param string $id
     */
    public function setID($id) {
        $this->id = $id;
    }

    /**
     * Get relevant html class.
     *
     * @return string
     */
    public function getClass() {
        return $this->class;
    }

    /**
     * Set what the html class will be. Can accept a string or an array that will concatenate with spaces between.
     *
     * @param array|string $class
     */
    public function setClass($class) {
        if (is_array($class)) {
            $class = implode(" ", $class);
        }
        $this->class = $class;
    }

    /**
     * Generates a class declaration string (class="something") for the containing div.
     *
     * @return string
     */
    public function getClassString() {
        return ($this->getClass()) ? " class=\"{$this->getClass()}\"" : "";
    }

    /**
     * Generates an ID declaration string (id="something") for the containing div.
     *
     * @return string
     */
    public function getIDString() {
        return ($this->getID()) ? " id=\"{$this->getID()}\"" : "";
    }

    //-- Overrides --//

    /**
     * Default implementation of renderView for an HTML view, must be overridden by child if the default class behaviour is used.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("HTML View not implemented");?></strong>
        </div>
        <?php
    }

    /**
     * Render a generic error message. If there are rendering error messages to display, then they are
     * each added, otherwise a default message is displayed. If this renderError method is overridden, then the
     * child is responsible for their own error message handling.
     */
    protected function renderError() {
        global $translate; ?>
        <div class="alert alert-danger">
            <ul>
                <?php if ($this->hasRenderingErrors()): ?>
                    <?php foreach($this->getRenderingErrors() as $error_message): ?>
                        <li><strong><?php echo html_encode($error_message) ?></strong></li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li><strong><?php echo html_encode($translate->_("Unable to render HTML view")); ?></strong></li>
                <?php endif; ?>
            </ul>
            <div class="clearfix"></div>
        </div>
        <?php
    }
}