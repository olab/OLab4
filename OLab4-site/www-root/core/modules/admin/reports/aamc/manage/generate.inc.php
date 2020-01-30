<?php
/**
 * AAMC Curriculum Inventory Reporting
 *
 * @author Organisation: Queen's University
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 * This file is not open source, and may not be distributed with the Entrada project.
 *
*/

use LSS\Array2XML;

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_AAMC_CI"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("report", "read", false)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    /**
     * Fetch the hostname for this organisation based on the URL provided for the organisation.
     */
    $org_hostname = parse_url($ACTIVE_ORG->getURL(), PHP_URL_HOST);

    if (!$org_hostname) {
        $org_hostname = "unknown.edu";
    }

    if (isset($REPORT["report_params"]) && $REPORT["report_params"]) {
        $learners = json_decode($REPORT["report_params"], true);
    } else {
        $learners = array();
    }

    if (!empty($learners)) {
        /**
         * Define the 4 major content arrays of the report.
         */
        $events = array ();
        $expectations = array ();
        $academic_levels = array ();
        $sequence = array ();

        try {
            $aamc = new Models_Reports_Aamc($ACTIVE_ORG->getID());
            $aamc->setHostname($org_hostname);
            $aamc->setReport($REPORT_ID);
            $aamc->setLearners($learners);

            $events             = $aamc->fetchEvents();
            $expectations       = $aamc->fetchExpectations();
            $academic_levels    = $aamc->fetchAcademicLevels();
            $sequence           = $aamc->fetchSequence();
        } catch (Exception $e) {
            add_error("Unable to load the AAMC module, please consult a system administrator.");
        }

        /**
         * Clears all open buffers so we can return a plain response for the Javascript.
         */
        ob_clear_open_buffers();

        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename="'.time().'-aamc-ci-export.xml"');

        $description = clean_input($REPORT["report_description"], array("striptags", "decode", "trim"));

        $inventory = array();
        $inventory = array (
            "@attributes" => array (
                "xsi:schemaLocation" => "http://ns.medbiq.org/curriculuminventory/v1/ curriculuminventory.xsd",
                "xmlns" => "http://ns.medbiq.org/curriculuminventory/v1/",
                "xmlns:lom" => "http://ltsc.ieee.org/xsd/LOM",
                "xmlns:a" => "http://ns.medbiq.org/address/v1/",
                "xmlns:cf" => "http://ns.medbiq.org/competencyframework/v1/",
                "xmlns:co" => "http://ns.medbiq.org/competencyobject/v1/",
                "xmlns:hx" => "http://ns.medbiq.org/lom/extend/v1/",
                "xmlns:m" => "http://ns.medbiq.org/member/v1/",
                "xmlns:xsi" => "http://www.w3.org/2001/XMLSchema-instance",
            ),
            "ReportID" => array (
                "@attributes" => array (
                    "domain" => "idd:".$org_hostname.":cireport"
                ),
                "@value" => $REPORT_ID.time(),
            ),
            "Institution" => array (
                "m:InstitutionName" => $ACTIVE_ORG->getAAMCInstitutionName(),
                "m:InstitutionID" => array (
                    "@attributes" => array (
                        "domain" => "idd:".$org_hostname.":institution"
                    ),
                    "@value" => $ACTIVE_ORG->getAAMCInstitutionId()
                ),
                "m:Address" => array (
                    "a:StreetAddressLine" => $ACTIVE_ORG->getAddress1(),
                    "a:City" => $ACTIVE_ORG->getCity(),
                    "a:StateOrProvince" => $ACTIVE_ORG->getProvince(),
                    "a:PostalCode" => $ACTIVE_ORG->getPostCode(),
                    "a:Country" => array (
                        "a:CountryName" => $ACTIVE_ORG->getCountry(),
                    ),
                ),
            ),
            "Program" => array (
                "ProgramName" => $ACTIVE_ORG->getAAMCProgramName(),
                "ProgramID" => array (
                    "@attributes" => array (
                         "domain" => "idd:".$org_hostname.":program",
                    ),
                    "@value" => $ACTIVE_ORG->getAAMCProgramId(),
                )
            ),
            "Title" => $REPORT["report_title"],
            "ReportDate" => date("Y-m-d", time()),
            "ReportingStartDate" => $REPORT["report_start"],
            "ReportingEndDate" => $REPORT["report_finish"],
            "Language" => $REPORT["report_langauge"],
            "Description" => ($description ? $description : "No description provided."),
            "SupportingLink" => $REPORT["report_supporting_link"],
            "Events" => $events,
            "Expectations" => $expectations,
            "AcademicLevels" => $academic_levels,
            "Sequence" => $sequence,
        );


        $xml = Array2XML::createXML('CurriculumInventory', $inventory);
        echo $xml->saveXML();
        exit;
    } else{
        add_error("There are either no academic levels selected for this report, or there are no students selected to represent one ore more academic levels.");

        echo display_error();
    }
}