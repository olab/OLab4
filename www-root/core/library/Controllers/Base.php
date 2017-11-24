<?php
/**
 *
 * Entrada [ http://www.entrada-project.org ]
 *
 * Base Controller class that provides common methods and information to all Controllers.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
class Controllers_Base {
    protected $database_name                = DATABASE_NAME;
    protected $default_error_msg            = "Please ensure you have provided a valid <strong>%s</strong> before continuing.";
    protected $default_group_error_msg      = "Please ensure you have provided at least %d of the following fields before continuing: <strong>%s</strong>";
    protected $validated_data               = array();
    protected $preprocessed_data            = array();
    protected $clean_validation_rules       = array();
    protected $validation_rules             = array();
    protected $required_fields              = array();
    protected $required_field_groups        = array();
    protected $required_field_groups_lookup = array();
    protected $errors                       = array();

    public function __construct($request_data = array(), $preprocessed_data = array(), $native_error_handling = false, $steps = false) {
        global $translate;

        if (isset($request_data) && $request_data) {
            if (is_array($preprocessed_data) && @count($preprocessed_data) >= 1) {
                $this->preprocessed_data = $preprocessed_data;
            }
            if (!is_array($this->clean_validation_rules) || @count($this->clean_validation_rules) < 1) {
                $this->clean_validation_rules = $this->validation_rules;
            }
            if (!empty($steps) && !is_array($steps) && ((int)$steps)) {
                $steps = array((int)$steps);
            }
            foreach ($this->validation_rules as $validation_rule_key => $rule) {

                /**
                 * Simple addition of rules to the "required" fields list, which may dynamically
                 * change depending on what fields are supplied in processing, and even which values
                 * are selected in each field if the "required_when" key for that validation rule.
                 */

                if ($this->isRequired($validation_rule_key) && !in_array($validation_rule_key, $this->required_fields)) {
                    $this->required_fields[] = $validation_rule_key;
                }

                if (!$steps || !array_key_exists("step", $rule) || in_array($rule["step"], $steps)) {
                    if (array_key_exists($validation_rule_key, $request_data)) {
                        $tmp_input = $request_data[$validation_rule_key];

                        /**
                         * Return an appropriate error if an input is required but not provided
                         */
                        if ($this->isRequired($validation_rule_key) && $tmp_input !== false && empty($tmp_input)) {

                            //Adds the key of the rule and the (lack of) data for the error list so verbose errors can be built later
                            $this->errors["required_data"][$validation_rule_key] = $tmp_input;

                        } elseif (!empty($tmp_input) || $tmp_input == false) {
                            /**
                             * At least one value was provided, so we make sure the value(s) are
                             * in an array, letting us run the same logic for arrays of values as individual values.
                             */

                            if (!is_array($tmp_input)) {
                                $tmp_input = array($tmp_input);
                            }

                            foreach ($tmp_input as $tmp_value) {
                                $tmp_value = clean_input($tmp_value, $rule["sanitization_params"]);

                                /**
                                 * If an allowed values directive exists, this will be used as a whitelist for the values of the input
                                 */

                                if (isset($rule["allowed_values"]) && is_array($rule["allowed_values"])) {
                                    if (in_array($tmp_value, $rule["allowed_values"])) {
                                        $this->setData($validation_rule_key, $tmp_value);
                                    } else {
                                        $this->errors["allowed_values"][$validation_rule_key] = $tmp_value;
                                    }
                                } else {
                                    $this->setData($validation_rule_key, $tmp_value);
                                }
                            }

                        }
                    } elseif ($this->isRequired($validation_rule_key)) {
                        $this->errors["required_data"][$validation_rule_key] = $tmp_input;
                    }
                }
            }

            if (isset($this->required_field_groups) && @count($this->required_field_groups) >= 1) {
                foreach ($this->required_field_groups as $group_id => $required_field_group) {
                    if (isset($required_field_group["number_required"]) && ((int)$required_field_group["number_required"])) {
                        $number_required = ((int)$required_field_group["number_required"]);
                    } else {
                        $number_required = @count($required_field_group["fields"]);
                    }
                    $required_fields_found = array_intersect(array_keys($this->validated_data), $required_field_group["fields"]);
                    if ((!is_array($required_fields_found) || !@count($required_fields_found)) || $number_required > @count($required_fields_found)) {
                        if (@count($required_field_group) > 1) {
                            $this->errors["required_data_groups"][$group_id] = $required_field_group;
                        } else {
                            $this->errors["required_data"][$required_field_group["fields"][0]] = null;
                        }
                    } elseif (is_array($required_fields_found) && @count($required_fields_found) && $number_required <= @count($required_fields_found)) {
                        foreach ($required_fields_found as $required_field) {
                            $this->validation_rules[$required_field]["required"] = true;
                        }
                    }
                }
            }

            foreach ($this->validation_rules as $validation_rule_key => $rule) {
                if ((!$steps || !array_key_exists("step", $rule) || in_array($rule["step"], $steps))
                    && $rule["required"]
                    && (!is_array($this->validated_data)
                        || !array_key_exists($validation_rule_key, $this->validated_data))
                    && (!array_key_exists("required_data", $this->errors)
                        || !is_array($this->errors["required_data"])
                        || !array_key_exists($validation_rule_key, $this->errors["required_data"]))
                ) {
                    $this->errors["required_data"][$validation_rule_key] = false;
                }
            }

            if (isset($native_error_handling) && $native_error_handling) {
                foreach ($this->errors as $error_type => $errors) {
                    if ($error_type == "required_data_groups") {
                        foreach ($errors as $field_group) {
                            if (isset($field_group["error_msg"]) && $field_group["error_msg"]) {
                                add_error($translate->_($field_group["error_msg"]));
                            } else {
                                $fields_list_string = "";
                                $length = count($field_group["fields"]);
                                $counter = 1;
                                foreach ($field_group["fields"] as $tmp_fieldname) {
                                    $fields_list_string .= ($fields_list_string ? ($counter == $length ? ", or " : ", ") : "") . $this->validation_rules[$tmp_fieldname]["label"];
                                    $counter++;
                                }
                                add_error(sprintf($translate->_($this->default_group_error_msg), (isset($field_group["number_required"]) && $field_group["number_required"] ? (int)$field_group["number_required"] : @count($field_group["fields"])), $fields_list_string));
                            }
                        }
                    } else {
                        foreach ($errors as $fieldname => $value) {
                            if (array_key_exists($fieldname, $this->validation_rules)) {
                                if (array_key_exists("error_msgs", $this->validation_rules[$fieldname])) {
                                    if (is_array($this->validation_rules[$fieldname]["error_msgs"])) {
                                        if (array_key_exists($error_type, $this->validation_rules[$fieldname]["error_msgs"])) {
                                            add_error($translate->_($this->validation_rules[$fieldname]["error_msgs"][$error_type]));
                                        } elseif (array_key_exists("default", $this->validation_rules[$fieldname]["error_msgs"])) {
                                            add_error($translate->_($this->validation_rules[$fieldname]["error_msgs"]["default"]));
                                        }
                                    } elseif ((string)$this->validation_rules[$fieldname]["error_msgs"]) {
                                        add_error(sprintf($translate->_($this->validation_rules[$fieldname]["error_msgs"]), (isset($this->validation_rules[$fieldname]["label"]) && $this->validation_rules[$fieldname]["label"] ? $this->validation_rules[$fieldname]["label"] : "")));
                                    }
                                } else {
                                    $tmp_label = (isset($this->validation_rules[$fieldname]["label"]) && $this->validation_rules[$fieldname]["label"] ? $this->validation_rules[$fieldname]["label"] : ucwords(str_replace(array("_", "-"), " ", $fieldname)));
                                    add_error(sprintf($translate->_($this->default_error_msg), $tmp_label));
                                }
                            }
                        }
                    }
                }
            }


            $this->setDefaults();
        }
    }

    private function setDefaults($steps = false) {
        if ($steps && !is_array($steps)) {
            $steps = array($steps);
        }
        foreach ($this->validation_rules as $rule_key => $validation_rule) {
            if ((empty($steps) || !isset($validation_rule["step"]) || in_array($validation_rule["step"], $steps)) && !array_key_exists($rule_key, $this->validated_data) && array_key_exists("default", $validation_rule)) {
                $this->validated_data[$rule_key] = $validation_rule["default"];
            }
        }
    }

    private function setData($validation_rule_key, $values) {
        if (!is_array($values)) {
            $values = array($values);
        }
        foreach ($values as $value) {
            if (isset($this->validation_rules[$validation_rule_key]["array"]) && $this->validation_rules[$validation_rule_key]["array"]) {
                $this->validated_data[$validation_rule_key][] = $value;
            } else {
                $this->validated_data[$validation_rule_key] = $value;
            }
            /**
             * If the rule exists, that rule has a requirement matrix value set, and the value is in
             * that matrix, then add all of the values defined by that key to the list of required fields,
             * and update the data rule itself for this pass-through.
             */
            if (array_key_exists($validation_rule_key, $this->validation_rules)) {
                if (array_key_exists("requirement_matrix", $this->validation_rules[$validation_rule_key])) {
                    if (array_key_exists($value, $this->validation_rules[$validation_rule_key]["requirement_matrix"])) {
                        $this->processRequirementsMatrix($this->validation_rules[$validation_rule_key]["requirement_matrix"][$value]);
                    } elseif (($this->validation_rules[$validation_rule_key]["required"] || !isset($this->validation_rules[$validation_rule_key]["cascade_when_unrequired"]) || $this->validation_rules[$validation_rule_key]["cascade_when_unrequired"] == true) && array_key_exists("%*%", $this->validation_rules[$validation_rule_key]["requirement_matrix"])) {
                        $this->processRequirementsMatrix($this->validation_rules[$validation_rule_key]["requirement_matrix"]["%*%"]);
                    }
                }

                if (array_key_exists("cascading_requirements", $this->validation_rules[$validation_rule_key])) {
                    foreach ($this->validation_rules[$validation_rule_key]["cascading_requirements"] as $cascading_requirement) {
                        if (array_key_exists($cascading_requirement, $this->validation_rules)) {
                            $this->validation_rules[$cascading_requirement]["required"] = true;
                        }
                    }
                }
            }
        }
        return $values;
    }

    public function isRequired($rule_key) {
        if (array_key_exists($rule_key, $this->validation_rules)) {
            if ((array_key_exists("required", $this->validation_rules[$rule_key]) && $this->validation_rules[$rule_key]["required"]) || array_key_exists($rule_key, $this->required_fields)) {
                return true;
            } else {
                return $this->checkRequiredWhen($rule_key);
            }

        } else {
            return null;
        }
    }

    public function setRequired($rule_key, $is_required) {
        $this->validation_rules[$rule_key]["required"] = ((bool) $is_required);
        return $this;
    }

    public function processRequirementsMatrix($requirements) {
        $tmp_requirements = $requirements;
        if (!is_array($tmp_requirements)) {
            $tmp_requirements = array($tmp_requirements);
        }
        $requirements = array();

        foreach ($tmp_requirements as $requirement) {
            if (!is_array($requirement)) {
                $requirements[] = array("fields" => array($requirement));
            } else {
                $requirements[] = $requirement;
            }
        }
        foreach ($requirements as $requirements_group) {
            if (@count($requirements_group["fields"]) > 1) {
                $group_key = count($this->required_field_groups);
                $this->required_field_groups[$group_key] = $requirements_group;
                foreach ($requirements_group["fields"] as $field) {
                    if (!array_key_exists($field, $this->required_field_groups_lookup)) {
                        $this->required_field_groups_lookup[$field] = array();
                    }
                    $this->required_field_groups_lookup[$field][] = $group_key;
                }
            } elseif (isset($requirements_group["fields"]) && array_key_exists($requirements_group["fields"][0], $this->validation_rules)) {
                $this->validation_rules[$requirements_group["fields"][0]]["required"] = true;
            }
        }

    }

    public function checkRequiredWhen($rule_key) {
        //First thing's first, does it even have a "required_when" value?
        if (array_key_exists("required_when", $this->validation_rules[$rule_key])) {
            /**
             * We need all of the already validated data from this step, as well as any
             * available data from earlier steps if it has been provided, so we can look
             * back and determine if any requirements have been brought forward.
             */
            $all_processed_data = array_merge($this->preprocessed_data, $this->validated_data);

            /**
             * Make a counter to check that we've met all the constraints we need to (usually it'll be one, but that's okay)
             *
             * Also: Yeah, I named it that.
             */
            $required_whens_met = 0;

            /**
             * We are going to mess around with the format of this data, as it's easier to just treat
             * everything like it's in the same format than to run two parallel streams of logic in situations
             * where it *is* an array of constraints vs. isn't. After that, we're gonna iterate through
             * them so we can check each individually.
             */
            $tmp_required_when = $this->validation_rules[$rule_key]["required_when"];
            if (array_key_exists("values", $tmp_required_when)) {
                $tmp_required_when = array($tmp_required_when);
            }
            foreach ($tmp_required_when as $required_when) {

                /**
                 * This is why the above variable is named a bit strangely; we actually have to evaluate
                 * whether a constraint has been met at this level before we can even say whether it has
                 * been met for the requirements logic, as there *may* be cases where multiple different possible values
                 * will initiate a constraint, or even an array of values that must *all* be matched in the processed
                 * data.
                 *
                 * Technically it would be trivial to add the ability to specifically say how many of the values must
                 * be matched, but I think that's overkill, as the same functionality can easily be achieved with
                 * "required_when" constraints that share the same key and different values.
                 */
                $requirements_met = 0;

                //We're just determining how many of the constraints must be matched here, which basically means only one, or all of them
                switch ($required_when["type"]) {
                    case "variable" :
                    default :
                        if (isset($required_when["number_required"]) && $required_when["number_required"]) {
                            $number_required = $required_when["number_required"];
                        } else {
                            $number_required = 1;
                        }
                    break;
                    case "all" :
                        $number_required = count($required_when["values"]);
                    break;
                }

                //Now we iterate through all the constraint values to find how many matches there are in the processed data
                foreach ($required_when["values"] as $required_rule_key => $required_value) {

                    //Look for the key in the processed data
                    if (array_key_exists($required_rule_key, $all_processed_data)) {
                        //I'll stop commenting on this now, but I pretty consistently make everything into arrays
                        if (!is_array($all_processed_data[$required_rule_key])) {
                            $tmp_validated_data = array($all_processed_data[$required_rule_key]);
                        }
                        $constraints_required = 1;
                        if (is_array($required_value)) {
                            $tmp_required_value = $required_value;
                            if (!isset($required_when["strict"]) || $required_when["strict"]) {
                                $constraints_required = @count($required_value);
                            }
                        } else {
                            $tmp_required_value = array($required_value);
                        }

                        /**
                         * Now that everything is in arrays, I can just do an associated array intersect
                         * and that will return every record that the key and value both batch from both arrays:
                         * representing the number of constraints in this "required_when" that have been met
                         */
                        $found_values = array_intersect_assoc($tmp_required_value, $tmp_validated_data);
                        if (!empty($found_values) && @count($found_values) >= $constraints_required) {
                            $requirements_met++;
                            if ($requirements_met >= $number_required) {
                                break;
                            }
                        }
                    }
                }

                //Now I just need to make sure the number of requirements met is enough for this to evaluate as true
                if ($requirements_met >= $number_required) {
                    $required_whens_met++;
                    //Now, if we've met enough of the "required when" constraints, we can return true
                    if ($required_whens_met >= @count($tmp_required_when)) {
                        return true;
                    }
                } else {
                    //If this isn't true, we haven't met enough of the "required when" constraints (i.e. all of them), so we can break out of the loop and return false
                    break;
                }
            }
        }

        //At no point were enough "required when" constraints met, or maybe none existed, so we reached here and are telling the app this isn't required
        return false;
    }

    public function getErrors() {
        if (empty($this->errors)) {
            return false;
        }
        return $this->errors;
    }

    public function getValidatedData($database_identifiers = false) {
        $output = $this->validated_data;
        if ($database_identifiers) {
            $output = array();
            foreach ($this->validated_data as $rule_key => $value) {
                $output[(array_key_exists("db_fieldname", $this->validation_rules[$rule_key]) && $this->validation_rules[$rule_key]["db_fieldname"] ? $this->validation_rules[$rule_key]["db_fieldname"] : $rule_key)] = $value;
            }
        }

        return $output;
    }

    public function loadRecordAsValidatedData($data_record, $validated_data = array()) {
//        $validated_data = array_merge($validated_data, static::_loadRecordAsValidatedData($data_record, get_class($this)));
//        $validated_data =
//        return $output;
    }

    protected static function _loadRecordAsValidatedData($data_record, $class) {
        $output = array();
        if (class_exists($class)) {
            $object = new $class;
            $rule_field_map = array();
            foreach ($object->validation_rules as $key => $rule) {
                if (isset($rule["db_fieldname"]) && $rule["db_fieldname"]) {
                    $field_key = $rule["db_fieldname"];
                } else {
                    $field_key = $key;
                }
                if (!isset($rule_field_map[$field_key]) || !is_array($rule_field_map[$field_key])) {
                    $rule_field_map[$field_key] = array();
                }
                $rule_field_map[$field_key][] = $key;
            }
            foreach ($data_record as $fieldname => $data) {
                if (array_key_exists($fieldname, $rule_field_map)) {
                    foreach ($rule_field_map[$fieldname] as $rulename) {
                        $output[$rulename] = $data;
                    }
                } elseif (!array_key_exists($fieldname, $output)) {
                    $output[$fieldname] = $data;
                }
            }
        }
        return $output;
    }
    
    public function getValidationRules(){
        return $this->validation_rules;
    }

}