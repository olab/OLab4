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
 * This trait extends dataset functionality to an object. This mechanism
 * defines the dataset array property and an optional limiter array.
 * The implementation of the dataset is class specific.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

namespace Entrada\Traits;

trait Dataset
{
    protected $dataset = [];
    protected $limit_dataset = [];

    /**
     * Return true when the dataset is empty.
     *
     * @return bool
     */
    public function isDatasetEmpty()
    {
        if (is_array($this->dataset) && empty($this->dataset)) {
            return true;
        }
        return false;
    }

    /**
     * Set the stale flag in the dataset, if the dataset exists.
     * Functionality that calls this method should behave as though an empty dataset is stale.
     */
    public function setStale()
    {
        if (is_array($this->dataset) && !empty($this->dataset)) {
            if (array_key_exists('is_stale', $this->dataset)) {
                $this->dataset['is_stale'] = true;
            }
        }
    }

    /**
     * Check if a dataset is stale.
     * Empty datasets are considered to be stale.
     *
     * @return bool
     */
    public function isStale()
    {
        if (is_array($this->dataset) && empty($this->dataset)) {
            return true;
        }
        if (array_key_exists('is_stale', $this->dataset)) {
            return $this->dataset['is_stale'];
        }
        return true;
    }

    /**
     * Wrapper for database transaction start.
     *
     * @param string $errfn
     * @return mixed
     */
    public function startTransaction($errfn = 'ADODB_TransMonitor')
    {
        global $db;
        return $db->StartTrans($errfn);
    }

    /**
     * Wrapper for database transaction commit.
     *
     * @param bool $autoComplete
     * @return mixed
     */
    public function commitTransaction($autoComplete = true)
    {
        global $db;
        return $db->CompleteTrans($autoComplete);
    }

    /**
     * Wrapper for database transaction rollback.
     *
     * @param bool $autoComplete
     */
    public function rollbackTransaction($autoComplete = true)
    {
        global $db;
        // Mark the transaction as failed, and complete the transaction (Rollback is performed by CompleteTrans()
        $db->FailTrans();
        $db->CompleteTrans($autoComplete);
    }

}