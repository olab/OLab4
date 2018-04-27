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
 * Class for storing datasets in memory to reduce the number of repeated database queries.
 * This class supports local-scope data storage, that is, within the scope of the
 * calling object (e.g., saving DB queries in a local for-loop), or globally (e.g.,
 * saving a dataset for use between multiple objects).
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Entrada_Utilities_MemoryStorage extends Entrada_Base {

    protected $disable_internal_storage = false;          // A hard override for disabling the isInStorage mechanism check (makes it always return false)
    protected $global_storage = "Entrada_GlobalStorage";  // The name of a variable to place the internal storage data (global-scoped). If this is null, then global_storage is disabled.
    private $local_data_storage = array();                // Local-scope data storage to limit DB hits

    /**
     * Search for an item in the storage. If an index is specified, use that to find if it (or anything at that index) exists.
     * If the item is specified, do an exact comparison.
     *
     * This method will optionally use a global-scope variable to store the cached records. If the name of the variable is not
     * specified, then the default internal storage is used. The global scope variable is referenced by its
     * name, e.g. "DATA_STORAGE", via the $this->global_storage property. Note that if a variable that is specified does not exist,
     * PHP will create it (e.g., if $DATA_STORAGE does not exist, but you pass that string in to the constructor of this object,
     * then $DATA_STORAGE will then exist in global-scope).
     *
     * @param string $type
     * @param string $index
     * @param mixed $item
     * @return bool
     */
    public function isInStorage($type, $index = null, $item = null) {
        if ($this->disable_internal_storage) {
            return false;
        }
        if ($this->global_storage) {
            global ${$this->global_storage};
            if (!is_array(${$this->global_storage})) {
                return false;
            }
            // Both are set, return true
            if ($index !== null && $item !== null) {
                if (${$this->global_storage}[$type][$index] == $item) {
                    return true;
                }
            } else if ($index === null && $item !== null) {
                // Search for the exact item
                foreach (${$this->global_storage}[$type] as $potential_match) {
                    if ($potential_match == $item) {
                        return true;
                    }
                }
                // The index is set, so something is there
            } else if ($index !== null && $item === null) {
                if (isset(${$this->global_storage}[$type][$index])) {
                    return true;
                }
            }
        } else {
            // Both are set, return true
            if ($index !== null && $item !== null) {
                if ($this->local_data_storage[$type][$index] == $item) {
                    return true;
                }
            } else if ($index === null && $item !== null) {
                // Search for the exact item
                foreach ($this->local_data_storage[$type] as $potential_match) {
                    if ($potential_match == $item) {
                        return true;
                    }
                }
                // The index is set, so something is there
            } else if ($index !== null && $item === null) {
                if (isset($this->local_data_storage[$type][$index])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Put an item in the storage, optionally at a specified index.
     *
     * @param string $type
     * @param mixed $item
     * @param string $index
     */
    public function addToStorage($type, $item, $index = null) {
        if ($this->global_storage) {
            global ${$this->global_storage};
            if ($index) {
                ${$this->global_storage}[$type][$index] = $item;
            } else {
                ${$this->global_storage}[$type][] = $item;
            }
        } else {
            if ($index) {
                $this->local_data_storage[$type][$index] = $item;
            } else {
                $this->local_data_storage[$type][] = $item;
            }
        }
    }

    /**
     * Fetch the item from the specified index in storage. Returns false if the item is not in storage.
     *
     * @param string $type
     * @param string $index
     * @return mixed|bool
     */
    public function fetchFromStorage($type, $index) {
        if ($this->global_storage) {
            global ${$this->global_storage};
            if (!is_array(${$this->global_storage})) {
                return false;
            }
            if (array_key_exists($type, ${$this->global_storage})) {
                if (array_key_exists($index, ${$this->global_storage}[$type])) {
                    return ${$this->global_storage}[$type][$index];
                }
            }
        } else {
            if (array_key_exists($type, $this->local_data_storage)) {
                if (array_key_exists($index, $this->local_data_storage[$type])) {
                    return $this->local_data_storage[$type][$index];
                }
            }
        }
        return false;
    }

    /**
     * Remove the item(s) in storage for the given index.
     *
     * @param $type
     * @param $index
     * @return bool
     */
    public function removeFromStorage($type, $index = null) {
        if ($this->global_storage) {
            global ${$this->global_storage};
            if (!is_array(${$this->global_storage})) {
                return false;
            }
            if (array_key_exists($type, ${$this->global_storage})) {
                if ($index === null) {
                    unset(${$this->global_storage}[$type]); // Clear all of this type
                    return true;
                } else {
                    if (array_key_exists($index, ${$this->global_storage}[$type])) {
                        unset(${$this->global_storage}[$type][$index]); // Clear only this index
                        return true;
                    }
                }
            }
        } else {
            if (array_key_exists($type, $this->local_data_storage)) {
                if ($index === null) {
                    unset($this->local_data_storage[$type]);
                    return true;
                } else {
                    if (array_key_exists($index, $this->local_data_storage[$type])) {
                        unset($this->local_data_storage[$type][$index]);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Fetch the entire storage array.
     *
     * @return array
     */
    public function fetchStorage() {
        return $this->local_data_storage;
    }

    /**
     * Clear all existing storage.
     *
     * @param bool $local
     * @param bool $global
     */
    public function clearStorage($local = true, $global = true) {
        if ($local) {
            $this->local_data_storage = array();
        }
        if ($global) {
            global ${$this->global_storage};
            ${$this->global_storage} = null;
        }
    }


}