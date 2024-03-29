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
 * Abstract Base class for all view classes. Note that this class cannot
 * be used directly as it is abstract; child views must be extended
 * from specific view class subtypes, e.g. HTML/JSON/HTMLTemplate.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

abstract class Views_Base {

    private $generated_view_output = "";    // The rendered view output (used when buffering).
    private $rendering_errors = array();    // A list of strings describing rendering errors
    private $class_name = "";               // The name of the called class (top level)
    protected $add_errors = true;           // Allow rendering errors to be added to the rendering_errors array?

    /**
     * Sets the options for the class object. For example, $options = array('courses' => '123') sets $this->courses = 123.
     *
     * @param array $options
     */
    public function __construct($options = array()) {
        $this->class_name = get_called_class();
        // Set any options specified as view properties.
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get a generic property.
     *
     * Returns null if no property is set.
     *
     * @param string $property_name
     * @return mixed
     */
    public function get($property_name) {
        if (isset($this->$property_name)) {
            return $this->$property_name;
        } else {
            return null;
        }
    }

    /**
     * Set a generic property.
     *
     * @param string $property_name
     * @param mixed $value
     */
    public function set($property_name, $value) {
        if (isset($this->$property_name)) {
            $this->$property_name = $value;
        }
    }

    /**
     * When a view fails to render, store the information about the failure in the errors array.
     *
     * @param string $string
     * @param bool $force_add
     */
    public function addRenderingError($string, $force_add = false) {
        if ($this->add_errors || $force_add) {
            $this->rendering_errors[] = $string;
        }
    }

    /**
     * Check if there are undisplayed rendering errors.
     *
     * @return bool
     */
    public function hasRenderingErrors() {
        return !empty($this->rendering_errors);
    }

    /**
     * Fetch the rendering error messages.
     *
     * @return array
     */
    public function getRenderingErrors() {
        return $this->rendering_errors;
    }

    /**
     * Check if the options array contains the indexes specified in it, regardless of what they are.
     *
     * @param array $options
     * @param array $primitives_list
     * @return bool
     */
    protected function validateIsSet(&$options, $primitives_list = array()) {
        global $translate;
        if (empty($primitives_list)) {
            $this->addRenderingError(sprintf($translate->_("Unable to render %s: all required values were not specified."),"'{$this->class_name}'"));
            return false;
        }
        if (!is_array($options)) {
            $this->addRenderingError(sprintf($translate->_("Unable to render %s: malformed view options."),"'{$this->class_name}'"));
            return false;
        }
        foreach ($primitives_list as $primitive) {
            if (!array_key_exists($primitive, $options)) {
                $this->addRenderingError(sprintf($translate->_("Unable to render %s: '%s' was not specified."),"'{$this->class_name}'", $primitive));
                return false;
            }
        }
        return true;
    }

    /**
     * Validate that the items in array_list are arrays (be they emtpy or not).
     * This is a wrapper for validateArray.
     *
     * @param $options
     * @param $array_list
     * @return bool
     */
    protected function validateIsArray(&$options, $array_list) {
        return $this->validateArray($options, $array_list);
    }

    /**
     * Validate that the items in array_list are arrays (be they emtpy or not).
     *
     * @param $options
     * @param $array_list
     * @return bool
     */
    protected function validateArray(&$options, $array_list) {
        global $translate;
        if (empty($array_list)) {
            $this->addRenderingError(sprintf($translate->_("Unable to render %s: all required values were not specified."),"'{$this->class_name}'"));
            return false;
        }
        if (!is_array($options)) {
            $this->addRenderingError(sprintf($translate->_("Unable to render %s: malformed view options."),"'{$this->class_name}'"));
            return false;
        }
        foreach ($array_list as $to_check) {
            if (!array_key_exists($to_check, $options)) {
                $this->addRenderingError(sprintf($translate->_("Unable to render %s: '%s' does not exist."),"'{$this->class_name}'", $to_check));
                return false;
            }
            if (!is_array($options[$to_check])) {
                $this->addRenderingError(sprintf($translate->_("Unable to render %s: '%s' failed validation."),"'{$this->class_name}'", $to_check));
                return false;
            }
        }
        return true;
    }

    /**
     * Check if the options array contains the arrays specified in it, and that they are not empty.
     *
     * @param array $options
     * @param array $array_list
     * @return bool
     */
    protected function validateArrayNotEmpty(&$options, $array_list) {
        global $translate;
        if (empty($array_list)) {
            $this->addRenderingError(sprintf($translate->_("Unable to render %s: all required values were not specified."),"'{$this->class_name}'"));
            return false;
        }
        if (!is_array($options)) {
            $this->addRenderingError(sprintf($translate->_("Unable to render %s: malformed view options."),"'{$this->class_name}'"));
            return false;
        }
        foreach ($array_list as $to_check) {
            if (!isset($options[$to_check])) {
                $this->addRenderingError(sprintf($translate->_("Unable to render %s: '%s' does not exist."),"'{$this->class_name}'", $to_check));
                return false;
            }
            if (!is_array($options[$to_check])) {
                $this->addRenderingError(sprintf($translate->_("Unable to render %s: '%s' failed validation (not an array)."),"'{$this->class_name}'", $to_check));
                return false;
            }
            if (empty($options[$to_check])) {
                $this->addRenderingError(sprintf($translate->_("Unable to render %s: '%s' failed validation (array is empty)."),"'{$this->class_name}'", $to_check));

                return false;
            }
        }
        return true;
    }

    /**
     * This is the main point of execution for all views. After instantiation, view objects should call this method to
     * render the relevant output.
     *
     * It is either echoed to context at the time of render, or returned. The default behaviour is to buffer output to the internal
     * output storage property, but it can be overridden by specifying $direct = true. If specified by $direct, output will
     * be dumped directly to current context (ignoring echo flag).
     *
     * The default behaviour is to use output buffering and dump directly to context.
     *
     * @param array $options
     * @param bool $echo
     * @param bool $direct
     * @return string
     */
    public function render($options = array(), $echo = true, $direct = false) {
        if ($direct) {
            // Direct mode means no output buffering, render directly to output context.
            $this->generateOutput($options);
        } else {
            // Default mode: render, save output in object property, echoing if required.
            ob_start();
            $this->generateOutput($options);
            $this->generated_view_output = ob_get_contents();
            ob_end_clean();
            if ($echo) {
                echo $this->generated_view_output;
            }
        }
        return $this->generated_view_output;
    }

    //-- Protected/Overrideable --//

    /**
     * Output generation method. Default logic is to validate options, and then render, either error or the actual view code.
     * This logic can be overridden by the child class, if say, a template is required instead of in-object logic.
     *
     * @param array $options
     */
    protected function generateOutput($options = array()) {
        if ($this->validateOptions($options)) {
            $this->renderView($options); // Execute child-class view sub-type specific code
        } else {
            $this->renderError();
            $this->rendering_errors = array(); // Once rendered, clear the errors.
        }
    }

    /**
     * Perform simple validation on the options array. Should be overridden if validation is necessary.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    //-- Abstract (must be declared in child classes) --//

    /**
     * Abstract implementation of renderView. Each View subtype extended from this class must create their own renderView that
     * contains the logic for rendering the type-specific view.
     *
     * @param array $options
     */
    abstract protected function renderView($options = array());

    /**
     * Render an error message.
     */
    abstract protected function renderError();

}