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
 * This trait defines a mechanism for an object to report errors.
 * Error messages are stored in an array (that can be pointed
 * to outside of this context) that are localized using the $translate
 * object.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

namespace Entrada\Traits;

trait ErrorMessages
{
    protected $error_messages = []; // a flat list of error messages (strings)

    /**
     * If a parent_object is specified (by reference or directly), then associate our error message container array
     * with the parent's error container array (by reference).
     *
     * @param array $properties
     */
    public function setErrorMessageParent($properties = [])
    {
        $parent_object = array_key_exists('parent_object', $properties) ? $properties['parent_object'] : null;
        if (is_object($parent_object)
            && method_exists($parent_object, 'getErrorMessageContainer')
        ) {
            $this->setErrorMessageContainer($parent_object->getErrorMessageContainer());
        }
    }

    /**
     * Point to the given error container (array). This sets our error messages array to reference an external storage array.
     *
     * @param array $parent_errors_container
     */
    public function setErrorMessageContainer(&$parent_errors_container)
    {
        if (is_array($parent_errors_container)) {
            $this->error_messages = &$parent_errors_container;
        } else {
            // Not setting since the source given is not an array.
            $this->addErrorMessage('Unable to set error message container.');
        }
    }

    /**
     * Get a reference to the error message container. Useful for aggregating error messages across objects.
     *
     * @return array
     */
    public function &getErrorMessageContainer()
    {
        return $this->error_messages;
    }

    /**
     * Return if this object contains an error messages.
     *
     * @return bool
     */
    public function hasError()
    {
        return count($this->error_messages) > 0 ? true : false;
    }

    /**
     * Count the number of error messages we're holding.
     *
     * @return int
     */
    public function errorCount()
    {
        return count($this->error_messages);
    }

    /**
     * Fetch the error messages.
     *
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->error_messages;
    }

    /**
     * Add and translate a single error message.
     * Supports sprintf for variable arguments.
     *
     * @param bool $apply_translation
     * @param string $single_error_string
     * @param array $options
     */
    private function addFormattedMessage($apply_translation = true, $single_error_string, $options = [])
    {
        global $translate;
        if ($apply_translation) {
            $string = $translate->_($single_error_string);
        } else {
            $string = $single_error_string;
        }
        if (count($options)) {
            $this->error_messages[] = call_user_func_array('sprintf', array_merge([$string], $options));
        } else {
            $this->error_messages[] = $string;
        }
    }

    /**
     * Add a translated error message to the errors array.
     *
     * @param string $string
     * @param array ...$options
     */
    public function addErrorMessage($string, ...$options)
    {
        $this->addFormattedMessage(true, $string, $options);
    }

    /**
     * Add an error message to the errors array, untranslated.
     *
     * @param string $string
     * @param array ...$options
     */
    public function addErrorMessageRaw($string, ...$options)
    {
        $this->addFormattedMessage(false, $string, $options);
    }

    /**
     * Add multiple error messages. This method directly adds the given strings.
     * These are presumably the individual results of addErrorMessage().
     *
     * @param array $error_strings
     */
    public function addErrorMessages($error_strings)
    {
        foreach ($error_strings as $error_string) {
            $this->error_messages[] = $error_string;
        }
    }

    /**
     * Clear the stored error messages.
     */
    public function clearErrorMessages()
    {
        $this->error_messages = [];
    }
}