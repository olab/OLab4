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
* Renders an assessment portfolio folder artifact entry comment
*
* @author Organization: Elson S. Floyd College of Medicine
* @author Developer: Sean Girard <sean.girard@wsu.edu>
* @copyright Copyright 2017 Washington State University. All Rights Reserved.
*
*/

class Views_Profile_Portfolio_Pulse extends Views_Profile_Portfolio_Base {

    protected $portfolio_id, $pfolder_id, $proxy_id;

    protected $cutoff_success = 80;
    protected $cutoff_warning = 60;

    public function renderView($options = []) {
        if ( $this->proxy_id ) {
            if ( $this->portfolio_id ) {
                $data = $this->getPortfolioPulse();
            } elseif ($this->pfolder_id) {
                $data = $this->getFolderPulse();

            }
        }
        if ( $data ) {
            if ('json' == $options['type']) {
                $this->renderViewJSON($data);
            } elseif ('html' == $options['type']) {
                $this->renderViewHTML($data);
            }
        }
    }

    protected function renderViewJSON($data=null) {
        echo json_encode(array("status" => "success", "data" => $data));
    }

    protected function renderViewHTML($data=null) {
        if ($this->portfolio_id) {
            $this->renderViewPortfolioHTML($data);
        } elseif ($this->pfolder_id) {
            $this->renderViewFolderHTML($data);
        }
    }

    protected function renderViewPortfolioHTML($data=null) {
        global $translate;

        if ($data && $data['pf_data']) {

            if ($data['pfa_required']) {
                //$html[] = '<div class="card card-block card-outline-danger">';
                $html[] = '<h2 class="text-error">';
                //$html[] = '<i class="fa fa-bell-o"></i> ';
                $html[] = $translate->_('Some folders have required entries');
                $html[] = '<small>';
                $html[] = $translate->_('please review the following folders') . '&hellip;';
                $html[] = '</small>';
                $html[] = '</h2>';

                $html[] = '<ul class="">';
                foreach ($data['pf_data'] as $folder) {
                    if (0 < $folder['fa_required_incomplete']) {
                        if ($folder['data_folder'] && $folder['data_folder']['title']) {
                            $html[] = '<li><b>' . $folder['data_folder']['title'] . '</b></li>';
                        }
                    }
                }
                $html[] = '</ul>';
                //$html[] = '</div>';
            }

            echo implode(PHP_EOL, $html);
        }

    }

    protected function renderViewFolderHTML($data) {
        global $translate;

        if ($data) {

            $html[] = '<div class="row-fluid">';

            $html[] = '<div class="span6">';
            $html[] = '<h4>' . $translate->_('Required artifacts I have completed') . '</h4>';
            $html[] = '<div class="progress progress-' . $this->getStatusClass($data["fa_required_complete_percent"]) . ' progress-striping">';
            $html[] = '<div class="bar" style="width: ' . $data["fa_required_complete_percent_formatted"] . '">';
            $html[] = $data['fa_required_complete'] . ' <small>' . $translate->_('complete') . '</small>' . ' ' . $translate->_('out of') .  ' ' . $data['fa_required_total'] . ' <small>' . $translate->_('required') . '</small>';
            $html[] = '</div>'; // .bar
            $html[] = '</div>'; // .progress
            $html[] = '</div>'; // ./span

            $html[] = '<div class="span6">';
            $html[] = '<h4>' . $translate->_('Completed artifacts approved by my advisor') . '</h4>';
            $html[] = '<div class="progress progress-' . $this->getStatusClass($data["fa_required_approved_percent"]) . ' progress-striping">';
            $html[] = '<div class="bar" style="width: ' . $data["fa_required_approved_percent_formatted"] . '">';
            $html[] = $data['fa_required_approved'] . ' <small>' . $translate->_('approved') . '</small>' . ' ' . $translate->_('out of') .  ' ' . $data['fa_required_reviewed'] . ' <small>' . $translate->_('reviewed') . '</small>';
            $html[] = '</div>'; // .bar
            $html[] = '</div>'; // .progress
            $html[] = '</div>'; // ./span

            $html[] = '</div>'; // ./row

            /*
            $html[] = '<h4>' . $translate->_('Completed artifacts reviewed by my advisor') . '</h4>';
            $html[] = '<div class="progress progress-info progress-striping">';
            $html[] = '<div class="bar" style="width: ' . $data["fa_required_reviewed_percent_formatted"] . '">';
            $html[] = $data['fa_required_reviewed'] . ' / ' . $data['fa_required_complete'];
            $html[] = '</div>'; // .bar
            $html[] = '</div>'; // .progress
            */

            if (!empty($data['data_fa_required_incomplete'])) {
                $html[] = '<h1 class="text-center text-error">';
                //$html[] = '<i class="fa fa-bell-o"></i> ';
                $html[] = $translate->_('Artifacts that require entries');
                $html[] = '</h1>';
                foreach ($data['data_fa_required_incomplete'] as $artifact) {
                    $html[] = '<div class="card">';
                    $html[] = '<div class="card-block artifact-group">';
                    $html[] = '<h2 class="card-title">';
                    $html[] = $artifact['title'];
                    $html[] = '</h2>';
                    $html[] = '<p>';
                    $html[] = $artifact['description'];
                    $html[] = '</p>';
                    $html[] = '<a href="#" data-id="' . $artifact['pfartifact_id'] . '"data-toggle="modal" data-target="#portfolio-modal" class="btn btn-default btn-outline-primary artifact">';
                    $html[] = '<i class="fa fa-plus"></i> ' . $translate->_('Add Entry');
                    $html[] = '</a>';
                    $html[] = '</div>';
                    $html[] = '</div>';
                }
            }


            echo implode(PHP_EOL, $html);
        } else {
            //echo '<div class="alert alert-warning">No artifacts in this folder.</div>';
        }
    }

    protected function getStatusClass($percent, $context=null) {
        $class = "";
        // ToDo: The idea with the context param is that different contexts may have different cuttoff percentages
        switch($context) {
            default:
                if ($percent >= $this->cutoff_success) {
                    $class = "success";
                } elseif ($percent >= $this->cutoff_warning) {
                    $class = "warning";
                } else {
                    $class="danger";
                }
                break;
        }

        return $class;
    }

    protected function getPortfolioPulse($portfolio_id = null) {
        $portfolio_id = is_null($portfolio_id) ? $this->portfolio_id : $portfolio_id;
        if ($portfolio_id) {
            $portfolio = Models_Eportfolio::fetchRow($this->portfolio_id);
            if ($portfolio) {
                $folders = $portfolio->getFolders();
                if ($folders) {
                    $pf_data = []; // portfolio folders
                    $pfa_required = false; // does this portfolio have required artifacts in any folders?
                    foreach ($folders as $folder) {
                        $pf_pulse = $this->getFolderPulse($folder->getID());
                        $pf_data[] = $pf_pulse;
                        if (!empty($pf_pulse["data_fa_required_incomplete"])) {
                            $pfa_required = true;
                        }
                        // ToDo: accumulate folder-level data points?
                    }
                    $data = [
                        "portfolio_id" => $portfolio_id,
                        "pf_data" => $pf_data,
                        "pfa_required" => $pfa_required
                    ];

                    return $data;
                }
            }
        }
    }

    protected function getFolderPulse($pfolder_id = null) {
        $pfolder_id = is_null($pfolder_id) ? $this->pfolder_id : $pfolder_id;
        if ($pfolder_id) {
            $folder = Models_Eportfolio_Folder::fetchRow($pfolder_id);
            $artifacts = Models_Eportfolio_Folder_Artifact::fetchAll($pfolder_id, $this->proxy_id);
            if ($artifacts) {

                $data_fa = [];                          // all artifacts
                $data_fa_incomplete = [];               // artifacts with no entries
                $data_fa_required_incomplete = [];      // required artifacts with no entries

                $fa_total = 0;                          // total number of folder artifacts
                $fa_required_total = 0;                 // total number of required folder artifacts (proxy_id = 0)
                $fa_complete = 0;                       // total number of artifacts w/ at least one entry
                $fa_required_complete = 0;              // total number of required artifacts w/ at least one entry (proxy_id = 0)
                $fa_incomplete = 0;                     // total number of artifacts w/ no entries
                $fa_required_incomplete = 0;            // total number of required artifacts w/ no entries (proxy_id = 0)
                $fa_flagged = 0;                        // total number of artifacts w/ at least one flag
                $fa_required_flagged = 0;               // total number of required artifacts w/ at least one flag (proxy_id = 0)
                $fa_reviewed = 0;                       // total number of artifacts w/ at least one reviewed entry
                $fa_required_reviewed = 0;              // total number of required artifacts w/ at least one reviewed entry (proxy_id = 0)

                $fa_flagged_reviewed = 0;
                $fa_required_flagged_reviewed = 0;

                $data_ae = [];                          // all entries

                $ae_complete = 0;                       // total number of entries
                $ae_required_complete = 0;              // total number of required entries (artifact proxy_id = 0)
                $ae_flagged = 0;                        // total number of entries flagged
                $ae_required_flagged = 0;               // total number of required entries flagged (artifact proxy_id = 0)
                $ae_reviewed = 0;                       // total number of entries reviewed
                $ae_required_reviewed = 0;              // total number of required entries reviewed (artifact proxy_id = 0)

                //$ae_flagged_reviewed = 0;
                //$ae_required_flagged_reviewed = 0;

                foreach ($artifacts as $artifact) {
                    $fa_has_flag = false;
                    $fa_is_reviewed = false;

                    $fa_required_has_flag = false;
                    $fa_required_is_reviewed = false;

                    $data_fa[] = $artifact->toArray();
                    $fa_total++;
                    if (0 == $artifact->getProxyID()) {
                        $fa_required_total++;
                    }

                    if (!$artifact->getHasEntry()) {
                        $data_fa_incomplete[] = $artifact->toArray();
                        $fa_incomplete++;
                        if (0 == $artifact->getProxyID()) {
                            $data_fa_required_incomplete[] = $artifact->toArray();
                            $fa_required_incomplete++;
                        }
                    }

                    $entries = $artifact->getEntries($this->proxy_id);
                    if ($entries) {
                        foreach ($entries as $entry) {
                            $data_ae[] = $entry->toArray();
                            $ae_complete++;
                            if (0 == $artifact->getProxyID()) {
                                $ae_required_complete++;
                            }
                            if ($entry->getFlag()) {
                                $ae_flagged++;
                                $fa_has_flag = true;
                                if (0 == $artifact->getProxyID()) {
                                    $ae_required_flagged++;
                                }
                            }
                            if (0 < $entry->getReviewedDate()) {
                                $ae_reviewed++;
                                $fa_is_reviewed = true;
                                if (0 == $artifact->getProxyID()) {
                                    $ae_required_reviewed++;
                                }
                            }
                        }
                    }

                    if ($fa_has_flag) {
                        $fa_flagged++;
                        if (0 == $artifact->getProxyID()) {
                            $fa_required_flagged++;
                            $fa_required_has_flag = true;
                        }
                    }
                    if ($fa_is_reviewed) {
                        $fa_reviewed++;
                        if (0 == $artifact->getProxyID()) {
                            $fa_required_reviewed++;
                            $fa_required_is_reviewed = true;
                        }
                    }
                    if ($fa_has_flag && $fa_is_reviewed) {
                        $fa_flagged_reviewed++;
                    }
                    if ($fa_required_has_flag && $fa_required_is_reviewed) {
                        $fa_required_flagged_reviewed++;
                    }
                }

                // just take the inverse rather than accumulate these
                $fa_complete = $fa_total - $fa_incomplete;
                $fa_required_complete = $fa_required_total - $fa_required_incomplete;

                //$fa_approved = $fa_total - $fa_flagged_reviewed;
                //$fa_required_approved = $fa_required_total - $fa_required_flagged_reviewed;
                $fa_approved = $fa_reviewed - $fa_flagged_reviewed;
                $fa_required_approved = $fa_required_reviewed - $fa_required_flagged_reviewed;


                // fa

                $fa_complete_percent = $this->getPercent($fa_complete, $fa_total);
                $fa_complete_percent_formatted = $this->getPercentFormatted($fa_complete_percent);
                $fa_required_complete_percent = $this->getPercent($fa_required_complete, $fa_required_total);
                $fa_required_complete_percent_formatted = $this->getPercentFormatted($fa_required_complete_percent);

                $fa_incomplete_percent = $this->getPercent($fa_incomplete, $fa_total);
                $fa_incomplete_percent_formatted = $this->getPercentFormatted($fa_incomplete_percent);
                $fa_required_incomplete_percent = $this->getPercent($fa_required_incomplete, $fa_required_total);
                $fa_required_incomplete_percent_formatted = $this->getPercentFormatted($fa_required_incomplete_percent);

                $fa_flagged_percent = $this->getPercent($fa_flagged, $fa_complete);
                $fa_flagged_percent_formatted = $this->getPercentFormatted($fa_flagged_percent);
                $fa_required_flagged_percent = $this->getPercent($fa_required_flagged, $fa_required_complete);
                $fa_required_flagged_percent_formatted = $this->getPercentFormatted($fa_required_flagged_percent);

                $fa_reviewed_percent = $this->getPercent($fa_reviewed, $fa_complete);
                $fa_reviewed_percent_formatted = $this->getPercentFormatted($fa_reviewed_percent);
                $fa_required_reviewed_percent = $this->getPercent($fa_required_reviewed, $fa_required_complete);
                $fa_required_reviewed_percent_formatted = $this->getPercentFormatted($fa_required_reviewed_percent);

                $fa_flagged_reviewed_percent = $this->getPercent($fa_flagged_reviewed, $fa_reviewed);
                $fa_flagged_reviewed_percent_formatted = $this->getPercentFormatted($fa_flagged_reviewed_percent);
                $fa_required_flagged_reviewed_percent = $this->getPercent($fa_required_flagged_reviewed, $fa_required_reviewed);
                $fa_required_flagged_reviewed_percent_formatted = $this->getPercentFormatted($fa_required_flagged_reviewed_percent);

                $fa_approved_percent = $this->getPercent($fa_approved, $fa_reviewed);
                $fa_approved_percent_formatted = $this->getPercentFormatted($fa_approved_percent);
                $fa_required_approved_percent = $this->getPercent($fa_required_approved, $fa_required_reviewed);
                $fa_required_approved_percent_formatted = $this->getPercentFormatted($fa_required_approved_percent);

                // ae

                $ae_flagged_percent = $this->getPercent($ae_flagged, $ae_complete);
                $ae_flagged_percent_formatted = $this->getPercentFormatted($ae_flagged_percent);
                $ae_required_flagged_percent = $this->getPercent($ae_required_flagged, $ae_required_complete);
                $ae_required_flagged_percent_formatted = $this->getPercentFormatted($ae_required_flagged_percent);

                $ae_reviewed_percent = $this->getPercent($ae_reviewed, $ae_complete);
                $ae_reviewed_percent_formatted = $this->getPercentFormatted($ae_reviewed_percent);
                $ae_required_reviewed_percent = $this->getPercent($ae_required_reviewed, $ae_required_complete);
                $ae_required_reviewed_percent_formatted = $this->getPercentFormatted($ae_required_reviewed_percent);



                $data = [
                    // folder artifacts
                    //"data_fa" => $data_fa,
                    "data_fa_incomplete" => $data_fa_incomplete,
                    "data_fa_required_incomplete" => $data_fa_required_incomplete,

                    "fa_total" => $fa_total,
                    "fa_required_total" => $fa_required_total,

                    "fa_complete" => $fa_complete,
                    "fa_required_complete" => $fa_required_complete,
                    "fa_complete_percent" => $fa_complete_percent,
                    "fa_complete_percent_formatted" => $fa_complete_percent_formatted,
                    "fa_required_complete_percent" => $fa_required_complete_percent,
                    "fa_required_complete_percent_formatted" => $fa_required_complete_percent_formatted,

                    "fa_incomplete" => $fa_incomplete,
                    "fa_required_incomplete" => $fa_required_incomplete,
                    "fa_incomplete_percent" => $fa_incomplete_percent,
                    "fa_incomplete_percent_formatted" => $fa_incomplete_percent_formatted,
                    "fa_required_incomplete_percent" => $fa_required_incomplete_percent,
                    "fa_required_incomplete_percent_formatted" => $fa_required_incomplete_percent_formatted,

                    "fa_flagged" => $fa_flagged,
                    "fa_required_flagged" => $fa_required_flagged,
                    "fa_flagged_percent" => $fa_flagged_percent,
                    "fa_flagged_percent_formatted" => $fa_flagged_percent_formatted,
                    "fa_required_flagged_percent" => $fa_required_flagged_percent,
                    "fa_required_flagged_percent_formatted" => $fa_required_flagged_percent_formatted,

                    "fa_reviewed" => $fa_reviewed,
                    "fa_required_reviewed" => $fa_required_reviewed,
                    "fa_reviewed_percent" => $fa_reviewed_percent,
                    "fa_reviewed_percent_formatted" => $fa_reviewed_percent_formatted,
                    "fa_required_reviewed_percent" => $fa_required_reviewed_percent,
                    "fa_required_reviewed_percent_formatted" => $fa_required_reviewed_percent_formatted,

                    "fa_flagged_reviewed" => $fa_flagged_reviewed,
                    "fa_required_flagged_reviewed" => $fa_required_flagged_reviewed,
                    "fa_flagged_reviewed_percent" => $fa_flagged_reviewed_percent,
                    "fa_flagged_reviewed_percent_formatted" => $fa_flagged_reviewed_percent_formatted,
                    "fa_required_flagged_reviewed_percent" => $fa_required_flagged_reviewed_percent,
                    "fa_required_flagged_reviewed_percent_formatted" => $fa_required_flagged_reviewed_percent_formatted,

                    "fa_approved" => $fa_approved,
                    "fa_required_approved" => $fa_required_approved,
                    "fa_approved_percent" => $fa_approved_percent,
                    "fa_approved_percent_formatted" => $fa_approved_percent_formatted,
                    "fa_required_approved_percent" => $fa_required_approved_percent,
                    "fa_required_approved_percent_formatted" => $fa_required_approved_percent_formatted,


                    // artifact entries
                    //"data_ae" => $data_ae,

                    "ae_complete" => $ae_complete,
                    "ae_required_complete" => $ae_required_complete,

                    "ae_flagged" => $ae_flagged,
                    "ae_required_flagged" => $ae_required_flagged,
                    "ae_flagged_percent" => $ae_flagged_percent,
                    "ae_flagged_percent_formatted" => $ae_flagged_percent_formatted,
                    "ae_required_flagged_percent" => $ae_required_flagged_percent,
                    "ae_required_flagged_percent_formatted" => $ae_required_flagged_percent_formatted,

                    "ae_reviewed" => $ae_reviewed,
                    "ae_required_reviewed" => $ae_required_reviewed,
                    "ae_reviewed_percent" => $ae_reviewed_percent,
                    "ae_reviewed_percent_formatted" => $ae_reviewed_percent_formatted,
                    "ae_required_reviewed_percent" => $ae_required_reviewed_percent,
                    "ae_required_reviewed_percent_formatted" => $ae_required_reviewed_percent_formatted,

                    // folder data
                    "data_folder" => $folder->toArray()
                ];

                return $data;
            }


        }
    }

    protected function getPercent($dividend, $divisor) {
        return ($divisor > 0) ? round( (($dividend / $divisor) * 100), 0) : 0;
    }

    protected function getPercentFormatted($percent) {
        return number_format($percent, 0) . "%";
    }

}
