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
 * Displays a summary of distribution schedules.
 *
 * @author Organization: Queen's University.
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Reports_DistributionScheduleSummaryDataTable extends Views_Assessments_Base
{
    /**
     * Perform options validation
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array())
    {
        if (!isset($options["datatable_formatted_data"])) {
            return false;
        }
        return true;
    }

    /**
     * Render the table.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate, $HEAD;

        $options["header_text"] = array_key_exists("header_text", $options) ? $options["header_text"] : null;

        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQuery();
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/datatables.1.10.16.withExportButtons.min.js\"></script>";
        $HEAD[] = "<script type=\"text/javascript\">
                        jQuery(document).ready(function() {
                            var table = jQuery('#distribution-schedule-datatable').DataTable(
                                {
                                    'sPaginationType': 'full_numbers',
                                    'bInfo': false,
                                    'bAutoWidth': false,
                                    'bServerSide': false,
                                    'bProcessing': true,
                                    'columnDefs': [
                                        { 'targets': [0], 'visible': false, 'searchable': false },
                                        { 'targets': [1], 'visible': false, 'searchable': false }
                                    ],
                                    'createdRow': function(row, data, index) {
                                        // Check to see if the distribution deleted date (second data value, hidden) is set.
                                        if (data[1] != null) {
                                            jQuery(row).addClass('distribution-deleted');
                                        }
                                    },
                                    'order': [[5, 'asc']],
                                    'oLanguage': {
                                        'sEmptyTable': '". $translate->_("There were no distributions found.") ."',
                                        'sZeroRecords': '". $translate->_("There were no distributions found.") ."'
                                    },
                                    'dom': 'Blfrtip',
                                    'buttons': [
                                        {
                                            'extend': 'copy',
                                            'text': '" . $translate->_("Copy to clipboard") . "',
                                            'exportOptions': {
                                                'columns': ':visible'
                                            }
                                        }, 
                                        {
                                            'extend': 'csv',
                                            'exportOptions': {
                                                'columns': ':visible'
                                            } 
                                        }, 
                                        {
                                            'extend': 'pdf',
                                            'exportOptions': {
                                                'columns': ':visible'
                                            } 
                                        },
                                        {
                                            'extend': 'print',
                                            'exportOptions': {
                                                'columns': ':visible'
                                            } 
                                        }
                                    ],
                                    'lengthMenu': [10, 25, 50, 100, 1000],
                                    'pageLength': 100,
                                    'data': " . json_encode($options["datatable_formatted_data"]) . "
                                },
                            );
                            
                            jQuery('#distribution-schedule-datatable tbody').on('click', 'tr', function () {
                                var row_data = table.row(this).data();    
                                // Distribution ID is the value of the first column (hidden).
                                var id = row_data[0];
                                // Open distribution page in new window.
                                window.open(ENTRADA_URL + '/admin/assessments/distributions?section=progress&active_tab=completed&adistribution_id=' + id);
                            }); 
                        });                      
                    </script>";

        if ($options["header_text"]): ?>
            <div class="space-below">
                <h1><?php echo $options["header_text"]; ?></h1>
            </div>
        <?php endif;

        if ($options["description"]): ?>
            <div class="space-below">
                <h4><?php echo $translate->_("Description"); ?></h4>
                <ul>
                    <li><h4><?php echo ucfirst($options["description"]); ?></h4></li>
                </ul>
            </div>
        <?php endif;

        if ($options["start-date"] && $options["end-date"]): ?>
            <div>
                <h4><?php echo $translate->_("Report Date Range"); ?></h4>
                <ul>
                    <li>
                        <h4 class="start-date space-right inline"><?php echo date("Y-m-d", $options["start-date"]); ?></h4>
                        <h4 class="space-right inline"><?php echo " to "?></h4>
                        <h4 class="end-date inline"><?php echo date("Y-m-d", $options["end-date"])  ?></h4>
                    </li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="alert alert-info space-below space-above">
            <div class="row-fluid">
                <?php echo $translate->_("Please note that the data in this report is updated overnight. Any changes made today will not be reflected until tomorrow."); ?>
            </div>
        </div>

        <div class="row-fluid">
            <table id="distribution-schedule-datatable" class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th class="hidden"></th>
                    <th class="hidden"></th>
                    <th><?php echo $translate->_("Distribution"); ?></th>
                    <th><?php echo $translate->_("Course"); ?></th>
                    <th><?php echo $translate->_("Form"); ?></th>
                    <th><?php echo $translate->_("Start Date"); ?></th>
                    <th><?php echo $translate->_("End Date"); ?></th>
                    <th><?php echo $translate->_("Delivery Date"); ?></th>
                    <th><?php echo $translate->_("Expiry Date"); ?></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <?php
    }

}