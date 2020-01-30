<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * This is the template for the default English language file for Entrada.
 *
 * @author Organisation: University of Calgary
 * @author Unit: Faculty of Veterinary Medicine
 * @author Developer: Szemir Khangyi <skhangyi@ucalgary.ca>
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2008 University of Calgary. All Rights Reserved.
 *
*/

global $AGENT_CONTACTS;

return array (
	/*
	 * Navigation
	 */
	"navigation_tabs" => array (
		"public" => array ( "olab" => array("title" => "Maps") )
	),

	/*
	 * Global terminology used across different Entrada modules.
	 */
    "Organisation" => "Organisation",
    "Organisations" => "Organisations",
    "My Organisations" => "My Organisations",
    "enrolment" => "enrolment",
    "Enrolment" => "Enrolment",
    "Give Feedback!" => "Give Feedback!",
    "Quick Polls" => "Quick Polls",
	"Message Center" => "Message Center",
    "global_button_save" => "Save",
    "global_button_cancel" => "Cancel",
    "global_button_proceed" => "Proceed",
    "global_button_post" => "Post",
    "global_button_update" => "Update",
    "global_button_reply" => "Reply",
    "Centre" => "Centre",
    "Colour" => "Colour",
    "Colours" => "Colours",  
    "login" => "Login",
    "logout" => "Logout",
    "selected_courses" => "Selected Courses",
	"available_courses" => "Available Courses",
	"all_courses" => "All Courses",
	"no_courses" => "No Courses",
    "Course" => "Course",
	"SSO Login" => "SSO Login",

	/*
	 * Feedback
	 */
	"global_feedback_widget" => array(
		"global" => array(
			"system"		=> array(
				"link-text" => APPLICATION_NAME." Feedback",
				"link-desc" => "Please share any feedback you may have about this page.",
				"form"		=> array(
					"title" => "Feedback about ".APPLICATION_NAME,
					"description" => "This form is provided so you can efficiently provide our developers with important feedback regarding this application. Whether you are reporting a bug, feature request or just general feedback, all messages are important to us and appreciated.<br /><br />
									<span class=\"content-small\">Please note: If you are submitting a bug or problem, please try to be specific as to the issue. If possible also let us know how to recreate the problem.</span>",
					"anon"	=> false,
					"recipients" => array(
						$AGENT_CONTACTS["administrator"]["email"] => $AGENT_CONTACTS["administrator"]["name"]
					)
				)
			)
		)
	),

    /*
     * Events Module
     */
	"events_filter_controls" => array (
		"teacher" => array (
			"label" => "Teacher Filters"
		),
		"student" => array (
			"label" => "Student Filters"
		),
		"group" => array (
			"label" => "Cohort Filters"
		),
		"course" => array (
			"label" => "Course Filters"
		),
		"term" => array (
			"label" => "Term Filters"
		),
		"eventtype" => array (
			"label" => "Learning Event Type Filters"
		),
		"cp" => array (
			"label" => "Clinical Presentation Filters",
			"global_lu_objectives_name" => "MCC Presentations"
		),
		"co" => array (
			"label" => "Curriculum Objective Filters",
			"global_lu_objectives_name" => "Curriculum Objectives"
		),
		"topic" => array (
			"label" => "Hot Topic Filters"
		),
		"department" => array (
			"label" => "Department Filters"
		),
	),

    /*
     * Course and event colours
     */
    "event_color_palette" => array("#0055B7", "#00A7E1", "#40B4E5", "#6EC4E8", "#97D4E9"),
    "course_color_palette" => array("#0055B7", "#00A7E1", "#40B4E5", "#6EC4E8", "#97D4E9"),

	/*
	 * Dashboard Module
	 */

    "public_dashboard_feeds" => array (
        //"global" => array (
        //    array ("title" => "Entrada Consortium", "url" => "http://www.entrada-project.org/feed/", "removable" => false),
        //    array ("title" => "PBS NewsHour - Health", "url" => "http://www.pbs.org/newshour/topic/health/feed", "removable" => true),
        //    array ("title" => "Zend Developer Zone", "url" => "http://feeds.feedburner.com/PHPDevZone", "removable" => true),
        //    array ("title" => "CBC | Health News", "url" => "http://www.cbc.ca/cmlink/rss-health", "removable" => true)
        //),
        //"medtech" => array (
        //    // array ("title" => "Admin Feed Example", "url" => "http://www.yourschool.ca/admin.rss", "removable" => false)
        //),
        //"student" => array (
        //    // array ("title" => "Student Feed Example", "url" => "http://www.yourschool.ca/student.rss", "removable" => false)
        //),
        //"alumni" => array (
        //    // array ("title" => "Student Feed Example", "url" => "http://www.yourschool.ca/student.rss", "removable" => false)
        //),
        //"faculty" => array (
        //    // array ("title" => "Faculty Feed Example", "url" => "http://www.yourschool.ca/faculty.rss", "removable" => false)
        //),
        //"resident" => array (
        //    // array ("title" => "Resident Feed Example", "url" => "http://www.yourschool.ca/resident.rss", "removable" => false)
        //),
        //"staff" => array (
        //    // array ("title" => "Staff Feed Example", "url" => "http://www.yourschool.ca/staff.rss", "removable" => false)
        //)
	),
    "public_dashboard_links" => array (
		"global" => array (
			array ("title" => "Entrada Consortium", "url" => "https://entrada.org", "target" => "_blank"),
			array ("title" => "School Library", "url" => ENTRADA_URL . "/library", "target" => "_blank"),
			array ("title" => "MedSkl.com", "url" => "https://medskl.com", "target" => "_blank"),
			array ("title" => "Wired Health News", "url" => "https://www.wired.com/tag/health", "target" => "_blank"),
		),
		"medtech" => array (
			// array ("title" => "Additional Admin Link", "url" => "http://admin.yourschool.ca")
		),
		"student" => array (
			// array ("title" => "Additional Student Link", "url" => "http://student.yourschool.ca")
		),
		"alumni" => array (
			// array ("title" => "Additional Alumni Link", "url" => "http://alumni.yourschool.ca")
		),
		"faculty" => array (
			// array ("title" => "Additional Faculty Link", "url" => "http://faculty.yourschool.ca")
		),
		"resident" => array (
			// array ("title" => "Additional Resident Link", "url" => "http://resident.yourschool.ca")
		),
		"staff" => array (
			// array ("title" => "Additional Staff Link", "url" => "http://staff.yourschool.ca")
		)
	),
    "public_dashboard_title_medtech" => "MEdTech Dashboard",
    "public_dashboard_title_student" => "Student Dashboard",
    "public_dashboard_title_alumni" => "Alumni Dashboard",
    "public_dashboard_title_faculty" => "Faculty Dashboard",
    "public_dashboard_title_resident" => "Resident Dashboard",
    "public_dashboard_title_staff" => "Staff Dashboard",
    "public_dashboard_block_weather" => "Weather Forecast",
    "public_dashboard_block_community" => "My Communities",

	/*
	 * Communities Module
	 */
    "breadcrumb_communities_title"=> "Entrada Communities",
    "public_communities_heading_line" => "Need a <strong>collaborative space</strong> for your <strong>group</strong> to online?",
    "public_communities_tag_line" => "The <strong>Entrada Community Platform</strong> gives your group a <strong>space to connect</strong> online. You can create websites, study groups, share documents, upload photos, maintain mailing lists, announcements, and more!",
    "public_communities_title" => "Entrada Communities",
    "public_communities_create" => "Create a Community",
    "public_communities_count" => "<strong>Powering</strong> %s communities",
    "Community Permissions" => "Community Permissions",
    "community_history_add_announcement" => "A new announcement (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been added.",
    "community_history_edit_announcement" => "Announcement (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been updated.",
    "community_history_add_forum" => "A new discussion forum (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been added.",
    "community_history_add_post" => "A new discussion post (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-post&id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been added.",
    "community_history_edit_forum" => "Discussion forum (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been updated.",
    "community_history_edit_post" => "Discussion post (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-post&id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been updated.",
    "community_history_edit_reply" => "Discussion post #%RECORD_ID% of (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-post&id=%PARENT_ID%#post-%RECORD_ID%\">%RECORD_TITLE%</a>) has been updated.",
    "community_history_add_reply" => "Discussion post (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-post&id=%PARENT_ID%#post-%RECORD_ID%\">%RECORD_TITLE%</a>) was replied to.",
    "community_history_add_event" => "A new event (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been added.",
    "community_history_edit_event" => "Event (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been updated.",
	"community_history_add_event" => "A new event (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been added.",
    "community_history_edit_event" => "Event (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been updated.",
	"community_history_add_learning_event" => "A new learning event (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been added.",
    "community_history_edit_learning_event" => "Learning Event (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been updated.",
    "community_history_add_photo_comment" => "New comment on (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-photo&id=%PARENT_ID%\">%RECORD_TITLE%</a>) photo.",
    "community_history_add_gallery" => "A new photo gallery (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been added.",
    "community_history_add_photo" => "A new photo (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-photo&id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been added.",
    "community_history_edit_photo_comment" => "Comment on (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-photo&id=%PARENT_ID%\">%RECORD_TITLE%</a>) updated.",
    "community_history_edit_gallery" => "Photo gallery (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-photo&id=%RECORD_ID%\">%RECORD_TITLE%</a>) updated.",
    "community_history_edit_photo" => "Photo (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-photo&id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been updated.",
    "community_history_add_page" => "A new page (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%\">%RECORD_TITLE%</a>) has been created.",
    "community_history_edit_page" => "Page (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%\">%RECORD_TITLE%</a>) has been updated.",
    "community_history_edit_home_page" => "<a href=\"%SITE_COMMUNITY_URL%\">Home page</a> has been updated.",
    "community_history_add_poll" => "A new poll (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been added.",
    "community_history_edit_poll" => "Poll (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been updated.",
    "community_history_add_file_comment" => "New comment on (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-file&id=%PARENT_ID%\">%RECORD_TITLE%</a>) file.",
    "community_history_add_file" => "A new file (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-file&id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been uploaded.",
    "community_history_add_share" => "A new shared folder (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been added.",
    "community_history_add_file_revision" => "A new revision of (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-file&id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been uploaded.",
    "community_history_edit_file_comment" => "Comment on (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-file&id=%PARENT_ID%\">%RECORD_TITLE%</a>) updated.",
    "community_history_edit_file" => "File (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-file&id=%RECORD_ID%\">%RECORD_TITLE%</a>) had been updated.",
    "community_history_edit_share" => "Shared folder (<a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>) has been updated.",
    "community_history_create_moderated_community" => "Community (<a href=\"%SITE_COMMUNITY_URL%\">%RECORD_TITLE%</a>) has been created, but is waiting for administrator approval.",
    "community_history_create_active_community" => "Community (<a href=\"%SITE_COMMUNITY_URL%\">%RECORD_TITLE%</a>) has been created, and is now active.",
    "community_history_add_member" => "A new member (<a href=\"%SYS_PROFILE_URL%?id=%PROXY_ID%\">%RECORD_TITLE%</a>) has joined this community.",
    "community_history_add_members" => "%RECORD_ID% new member(s) added to the community.",
    "community_history_edit_community" => "The community profile was updated by <a href=\"%SYS_PROFILE_URL%?id=%RECORD_ID%\">%RECORD_TITLE%</a>.",
    "community_history_rename_community" => "Community is now known as <a href=\"%SITE_COMMUNITY_URL%\">%RECORD_TITLE%</a>",
    "community_history_activate_module" => "The <a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%\">%RECORD_TITLE%</a> module was activated for this community.",
    "community_history_move_file" => "The <a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-file&id=%RECORD_ID%\">%RECORD_TITLE%</a> file was moved to a different <a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-folder&id=%PARENT_ID%\">folder</a>.",
	"community_history_move_photo" => "The <a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-photo&id=%RECORD_ID%\">%RECORD_TITLE%</a> photo was moved to a different <a href=\"%SITE_COMMUNITY_URL%:%PAGE_URL%?action=view-gallery&id=%PARENT_ID%\">gallery</a>.",

    "Join Community" => "Join Community",
    "Join this community to access more features." => "Join this community to access more features.",
    "Admin Center" => "Admin Center",
    "Manage Community" => "Manage Community",
    "Manage Members" => "Manage Members",
    "Manage Pages" => "Manage Pages",
    "This Community" => "This Community",
    "My membership" => "My membership",
    "View all members" => "View all members",
    "Quit this community" => "Quit this community",
    "Log In" => "Log In",
    "Additional Pages" => "Additional Pages",
    "Permission Masks" => "Permission Masks",
    "Community Login" => "Community Login",
    "Course Navigation" => "Course Navigation",

	/*
	 * MSPR Module
	 */
	"mspr_no_entity" => "No Entity ID provided.",
	"mspr_invalid_entity" => "Item not found or invalid identifier provided",
	"mspr_no_action" => "No action requested.",
	"mspr_invalid_action" => "Invalid action requested for this item",
	"mspr_no_section" => "No MSPR section specified",
	"mspr_invalid_section" => "Invalid MSPR section specified",
	"mspr_no_comment" => "A comment is required and none was provided",
	"mspr_no_reject_reason" => "A reason for the rejection is required and none was provided",
	"mspr_invalid_user_info" => "Invalid user information provided",
	"mspr_no_details" => "Details are required and none were provided",
	"mspr_insufficient_info" => "Insufficient information provided.",
	"mspr_email_failed" => "Failed to send rejection email.",
	"mspr_observership_preceptor_required" => "A faculty preceptor must be selected or a non-faculty preceptor name entered.",
	"mspr_observership_invalid_dates" => "A valid start date is required.",
	"mspr_too_many_critical_enquiry" => "Cannot have more than one Critical Enquiry on MSPR. Please edit the existing project or remove it before adding a new one.",
	"mspr_too_many_community_based_project" => "Cannot have more than one Community-Based Project on MSPR. Please edit the existing project or remove it before adding a new one.",

	/*
     * Courses Module
     */
	"course" => "Course",
	"courses" => "Courses",
    "Course Director" => "Course Director",
    "Course Directors" => "Course Directors",
    "Curriculum Coordinator" => "Curriculum Coordinator",
    "Curriculum Coordinators" => "Curriculum Coordinators",
	"Faculty" => "Faculty",
    "Program Coordinator" => "Program Coordinator",
    "Program Coordinators" => "Program Coordinators",
    "Evaluation Rep" => "Evaluation Rep",
    "Student Rep" => "Student Rep",
    "Add A New Course" => "Add A New Course",
    "Delete Courses" => "Delete Courses",
    "Search Courses..." => "Search Courses...",
    "Loading Courses..." => "Loading Courses...",
    "No Courses Found" => "No Courses Found",
    "No Courses Selected to delete" => "No Courses Selected to delete",
    "Please confirm you would like to delete the selected Courses(s)?" => "Please confirm you would like to delete the selected Courses(s)?",
    "Load More Courses" => "Load More Courses",

	"evaluation_filtered_words" => "Dr. Doctor; Firstname Lastname",

	/*
	 * Course Group Module
	 *
	 */
	"Course Groups" => "Course Groups",
	"Add Group" => "Add Group",
	"Group Details" => "Group Details",
	"Group Name Prefix" => "Group Name Prefix",
	"Group Type" => "Group Type",
	"Create" => "Create",
	"empty groups" => "empty groups",
	"Automatically populate groups" => "Automatically populate groups",
	"Selected learners within" => "Selected learners within",
	"Populate based on" => "Populate based on",
	"Number of Groups" => "Number of Groups",
	"Group Size" => "Group Size",
	"No method supplied" => "No method supplied",
	"No Groups Found." => "No Groups Found.",
	"Invalid GET method." => "Invalid GET method.",
	"Assign Period" => "Assign Period",
	"Delete Groups" => "Delete Groups",
	"Add New Groups" => "Add New Groups",
	"Download as CSV" => "Download as CSV",
	"Search the Course Groups" => "Search the Course Groups",
	"No groups to display" => "No groups to display",
	"No Group Selected to delete." => "No Group Selected to delete.",
	"Please confirm that you would like to proceed with the selected Group(s)?" => "Please confirm that you would like to proceed with the selected Group(s)?",
	"Assign Curriculum Period" => "Assign Curriculum Period",
	"Please select only Group(s) without curriculum period assigned." => "Please select only Group(s) without curriculum period assigned.",
	"Please confirm that you would like to assign the curriculum period to the selected Group(s)?" => "Please confirm that you would like to assign the curriculum period to the selected Group(s)?",
	"Print" => "Print",
	"Male" => "Male",
	"Female" => "Female",
	"View Members" => "View Members",
	"Delete Members" => "Delete Members",
	"Name" => "Name",
	"Group & Role" => "Group & Role",
	"No members to display" => "No members to display",
	"Add Members" => "Add Members",

	/*
	 * Curriculum Explorer
	 */
	"curriculum_explorer" => array(
		"badge-success" => "0.3",
		"badge-warning" => "0.1",
		"badge-important" => "0.05"
	),

	/*
	 * Copyright Notice
	 */
    "copyright_title" => "Acceptable Use Agreement",
    "copyright_accept_label" => "I will comply with this copyright policy.",
	"copyright" => array(
		"copyright-version" => "", // Latest copyright version date time stamp (YYYY-MM-DD HH:MM:SS). You can also leave this empty to disable the acceptable use feature.
		"copyright-firstlogin" => "<strong>Use of Copyright Materials In ".APPLICATION_NAME."</strong>
			<p>Copyright protects the form in which literary, artistic, musical and dramatic works are expressed. In COUNTRY, copyright exists once a work is expressed in fixed form; no special registration needs to take place. Copyright usually resides with the creator of the work. Copyright exists in most work for 50 years after the death of the creator.</p>
			<p>The University of UNIVERSITY encourages access to works while ensuring that the rights of creators are respected in accordance with the Copyright Act, (see...)</p>
			<p>It is the responsibility of each individual to ensure compliance with copyright regulations.</p>
			<p>To proceed, you accept to comply with the copyright policy.</p>",
		"copyright-uploads" => "<strong>Use of Copyright Materials In ".APPLICATION_NAME."</strong>
			<p>Copyright protects the form in which literary, artistic, musical and dramatic works are expressed. In COUNTRY, copyright exists once a work is expressed in fixed form; no special registration needs to take place. Copyright usually resides with the creator of the work. Copyright exists in most work for 50 years after the death of the creator.</p>
			<p>The University of UNIVERSITY encourages access to works while ensuring that the rights of creators are respected in accordance with the Copyright Act, (see...)</p>
			<p>It is the responsibility of each individual to ensure compliance with copyright regulations.</p>
			<p>To proceed, you accept to comply with the copyright policy.</p>",
	),

    /*
     * Gradebook Module
     */
    "assignment_notice" => "<p>A new assignment [<a href=\"%assignment_submission_url%\">%assignment_title%</a>] has been released in %course_code%: %course_name%.</p>
        <p>The details provided for this assignment are as follows:</p>
        <p>Due Date: %due_date%</p>
        <p>Title: %assignment_title%</p>
        <p>Description:<br />%assignment_description%</p>",

	"Cancel" => "Cancel",
	"Add" => "Add",
	"Assign" => "Assign",
	"Submit" => "Submit",
	"Save" => "Save",
	"Back" => "Back",
	"Delete" => "Delete",
	"Done" => "Done",
	"Close" => "Close",

    /**
     * My Entrada Module
     */

    "My_Entrada" => array(
        "title" => "My Entrada",
        "links" => array(
            "exams" => "My Exams",
            "gradebook" => "My Gradebook",
            "assignments" => "My Assignments",
            "evaluations" => "My Evaluations"
        ),
    ),

    /**
     * Exams Module
     */
    "exams" => array(
        "title" => "Exams",
        "title_singular" => "Exam",
        "breadcrumb" => array(
            "title" => "Exams"
        ),
        "exams" => array(
            "title" => "Exams",
            "breadcrumb" => array(
                "title" => "Exams"
            ),
            "buttons" => array(
                "add_exam"          => "Add Exam",
                "delete_exam"       => "Delete Exam",
                "move_exam"         => "Move Exam",
                "copy_exam"         => "Copy Exam",
                "toggle"            => "Toggle Details",
                "btn_cancel"        => "Cancel",
                "btn_close"         => "Close",
                "btn_reorder"       => "Reorder",
                "delete_questions"  => "Delete Questions",
                "group_questions"   => "Group Questions"
            ),
            "placeholders" => array(
                "exam_bank_search" => "Begin Typing to Search the Exams..."
            ),
            "add-exam" => array(
                "title" => "Create New Exam",
                "breadcrumb" => array(
                    "title" => "Add Exam"
                ),
            ),
            "edit-exam" => array(
                "title" => "Editing Exam:",
                "labels" => array(
                    "number" => "#",
                    "id" => "ID",
                    "description" => "Description",
                    "update" => "Update",
                    "code" => "Code",
                    "type" => "Type",
                    "exam_bank_toggle_title" => "Exam Bank Viability",
                    "preview" => "Exam Preview"
                ),
                "breadcrumb" => array(
                    "title" => "Edit Exam"
                ),
                "exam_not_found"    => "Sorry, there was a problem loading the exam using that ID.",
                "no_exam_elements"  => "There are currently no questions attached to this exam."
            ),
            "add-permission" => array(
                "title" => "Add Permission",
                "breadcrumb" => array(
                    "title" => "Add Permission"
                ),
                "labels" => array(
                    "label_contact_type"    => "Contact Type",
                    "label_contact_name"    => "Contact Name"
                ),
                "contact_types" => array(
                    "proxy_id"          => "Individual",
                    "organisation_id"   => "Organisation",
                    "course_id"         => "Course"
                )
            ),
            "exam" => array(
                "label_exam_type"               => "Exam Type",
                "label_exam_title"              => "Exam Title",
                "label_exam_description"        => "Exam Description",
                "label_exam_permissions"        => "Exam Permissions",
                "title_exam_questions"          => "Exam Questions",
                "title_exam_info"               => "Exam Information",
                "title_modal_delete_element"    => "Delete Element",
                "title_modal_group_questions"   => "Group Questions",
                "label_add_to_group"            => "Add Question to Group",
                "title_modal_linked_questions"  => "Linked Question Groups",
                "select_question_group"         => "Select Question Group",
                "btn_add_single_question"       => "Add Individual Question(s)",
                "btn_add_free_text"             => "Add Free Text",
                "btn_add_page_break"            => "Add Page Break",
                "btn_add_question"              => "Add Question",
                "btn_add_group"                 => "Add Group",
                "btn_add_data_src"              => "Add Data Source",
                "btn_add_exam_el"               => "Add Text",
                "btn_copy_exam"                 => "Copy Exam",
                "text_loading_questions"        => "Loading Exam Questions...",
                "text_no_questions"             => "No Questions to display",
                "text_load_more_q"              => "Load More Questions",
                "text_no_attached_questions"    => "There are currently no questions attached to this exam.",
                "text_modal_delete_element"     => "Would you like to delete this exam element?",
                "text_update_success"           => "Successfully updated the exam.",
                "text_error_general"            => "An error occurred while attempting to update the exam.",
                "text_error_title"              => "Sorry, an exam title is required.",
                "text_error_exam_in_progress"   => "You are editing an Exam that learners have already started. If you delete the records for those students, then you can edit this exam, otherwise you will need to use the Copy Exam button and make a new post.",
                "failed_to_create_element"      => "Sorry, we were unable to add this element.",
                "failed_to_create_author"       => "Sorry, we were unable to add this author.",
                "settings" => array(
                    "title_exam_settings"       => "Exam Settings",
                    "label_display"             => "Display",
                    "label_random"              => "Randomization",
                    "text_display_all"          => "All questions on one page ",
                    "text_display_one"          => "One question per page ",
                    "text_display_page_breaks"  => "Questions according to Page Breaks ",
                    "text_random_on"            => "On",
                    "text_random_off"           => "Off",
                    "text_exam_data"            => "Exam Data",
                    "text_questions"            => "Questions",
                    "text_points"               => "Points",
                    "text_curriculum_tags"      => "Curriculum Tags",
                    "text_keyword"              => "Keyword",
                    "text_count"                => "Count",
                    "text_exam_pdfs"            => "Exam PDFs",
                    "text_loading_exam_pdfs"    => "Loading Exam PDFs...",
                    "text_add_pdf"              => "Add a PDF",
                    "text_delete_exam_pdfs"     => "Delete Exam PDFs",
                    "text_delete_PDF"           => "Delete PDF",
                    "text_add_exam_pdf"         => "Add Exam PDF",
                    "text_upload_pdf"           => "Drop the selected PDF anywhere to upload."
                )
            ),
            "add-element" => array(
                "title" => "Add Exam Element",
                "breadcrumb" => array(
                    "title" => "Add Element"
                ),
                "failed_to_create"          => "Sorry, we were unable to add this element to the exam.",
                "failed_to_create_exam"     => "Sorry, we were unable to create this exam.",
                "failed_to_create_element"  => "Sorry, we were unable to add this element.",
                "failed_to_create_author"   => "Sorry, we were unable to add this author.",
                "already_attached"          => "Sorry, the element you are attempting to add is already attached to the exam.",
                "no_available_questions"    => "There are no questions available to attach to this exam.",
                "add_element_notice"        => "Please check off the questions you wish to add to your exam and click the Add Elements button below."
            ),
            "index" => array(
                "delete_success"                    => "Exams have been successfully deleted. You will now be taken back to the Exams index.",
                "delete_error"                      => "There was a problem deleting the exams you selected. An administrator has been informed, please try again later.",
                "title_heading"                     => "Exam Title",
                "Exam_id"                           => "Exam ID",
                "created_heading"                   => "Date Created",
                "updated_heading"                   => "Updated",
                "questions_heading"                 => "Questions",
                "edit_heading"                      => "Edit",
                "posts_heading"                     => "Posts",
                "page_break"                        => "Page Break",
                "add_page_break"                    => "Add Page Break",
                "no_elements_attached"              => "There are currently no elements attached to this exam.",
                "no_exams_found"                    => "You currently have no exams to display. To Add a new exam click the Add Exam button above.",
                "text_modal_no_exams_selected"      => "No Exams selected to delete.",
                "text_modal_delete_exams"           => "Please confirm you would like to delete the selected Exam(s)?",
                "title_modal_delete_exams"          => "Delete Exams",
                "can_not_delete"                    => "You can not delete this exam.",
                "can_not_copy"                      => "You can not copy this exam.",
                "can_not_move"                      => "You can not move this exam.",
                "text_modal_no_exams_selected_m"    => "No Exams selected to move.",
                "text_modal_move_exams"             => "Please confirm you would like to move the selected Exam(s)?",
                "title_modal_move_exams"            => "Move Exams",
                "text_modal_no_exams_selected_c"    => "No Exams selected to copy.",
                "text_modal_copy_exams"             => "Please confirm you would like to copy the selected Exam(s)?",
                "text_copy_01"                      => "You will now be redirected to the copied exam; this will happen <strong>automatically</strong> in 5 seconds or ",
                "text_copy_02"                      => "click here",
                "text_copy_03"                      => " to continue.",
                "title_modal_copy_exams"            => "Copy Exams",
                "title_modal_delete_questions"      => "Remove Questions",
                "text_modal_no_questions_selected"  => "No questions selected to remove.",
                "text_modal_delete_questions"       => "Are you sure you would like to remove the selected question(s)?",
                "title_updated_questions_available" => "Updated questions are available. Press &#34;Update All&#34; to update them all to the latest versions.",
                "title_updated_questions_available2" => "Updated questions are available. Click ",
                "title_updated_questions_available3" => "here",
                "title_updated_questions_available4" => " to update them.",
                "button_update_all"                 => "Update All",
                "edit_menu" => array(
                    "view_posts"     => "Posts",
                    "edit_exam"      => "Edit Exam",
                    "preview_post"   => "Preview Exam",
                    "adjust_scoring" => "Adjust Scoring",
                    "reports"        => "Reports",
                    "print_view"     => "Printer Friendly View"
                ),
                "post-info" => array(
                    "title_post" => "Exam Posted in Courses"
                )
            ),
            "posting" => array(
                "steps" => array(
                    "1" => "Exam",
                    "2" => "Settings",
                    "3" => "Security",
                    "4" => "Exceptions",
                    "5" => "Feedback",
                    "6" => "Review",
                ),
                "post_not_found"        => "The Post ID provided is not valid",
                "event_not_found"       => "The Event ID provided is not valid",
                "exam_not_found"        => "The Exam ID provided is not valid",
                "attach_exam"           => "Post an Exam",
                "select_exam"           => "Select Exam",
                "browse_exam"           => "Browse Exams",
                "browse_audience"       => "Browse Audience",
                "exam_type"             => "Exam Type",
                "exam_title"            => "Exam Title",
                "exam_description"      => "Description",
                "exam_info"             => "Exam Information",
                "exam_instructions"     => "Instructions",
                "exam_exceptions"       => "Exam Exceptions",
                "exam_start_date"       => "Exam Start Date",
                "exam_end_date"         => "Exam End Date",
                "exam_submission_date"  => "Submission Deadline",
                "timeframe"             => "Time Frame",
                "use_time_limit"        => "Time Limit",
                "use_self_timer_title"  => "Self Timer",
                "use_self_timer_text"   => "Allows the learner to set a Self Timer",
                "time_hours"            => "Hours",
                "time_mins"             => "Minutes",
                "auto_submit"           => "Auto Submit",
                "auto_submit_text"      => "When checked and using time limit, exam will auto-submit when the limit is reached",
                "release_start_date"    => "Release Start Date",
                "release_end_date"      => "Release End Date",
                "use_re_attempt_threshold"      => "Re-Attempt Threshold",
                "label_re_attempt_threshold"    => "%",
                "re_attempt_threshold_attempts" => "Attempts",
                "threshold_note"        => "Note: using this feature will set the regular Max Attempts to 1.",
                "mandatory"             => "Required",
                "mandatory_text"        => "Require this exam to be completed by all audience members",
                "max_attempts"          => "Number of attempts allowed",
                "backtrack"             => "Backtrack",
                "backtrack_text"        => "Allows the learner to navigate back to questions already viewed",
                "secure_mode"           => "Exam Security Mode",
                "secure_not_required"   => "This is a formative assessment. No security options are required.",
                "secure_required"       => "Exam security options are required (i.e. password security, lockdown browser, etc.)",
                "secure_exam_config"    => "Safe Exam Browser",
                "seb_file"              => "Safe Exam Browser (SEB) File",
                "secure_keys"           => "Secure Key(s)",
                "exam_url"              => "Exam Url",
                "rpnow_reviewed_exam"   => "Should Software Secure review the exam?",
                "rpnow_reviewer_notes"  => "Exam specific notes for the reviewers",
                "exam_sponsor"          => "Exam Sponsor",
                "secure_warning_1"      => "You must save the exam post first",
                "secure_warning_2"      => "In order to add the secure exam files, you must complete the remaining steps first.",
                "fac_feedback_title"    => "Mark for Faculty Review",
                "fac_feedback_text"     => "Allows the learner to mark ScratchPad for faculty review",
                "calculator_title"      => "Calculator",
                "allow_calculator_text" => "Allows the learner to use the Calculator",
                "max_text"              => "Enter &quot;0&quot; for unlimited attempts",
                "gradebook"             => "Add to Gradebook",
                "gradebook_text"        => "Create a Gradebook assessment for this exam",
                "release_score"         => "Release Score",
                "release_feedback"      => "Release Feedback",
                "release_incorrect_responses" => "Release Incorrect Feedback",
                "score_text"            => "Release learners score after they have submitted",
                "feedback_text"         => "Release learners feedback after they have submitted",
                "feedback_level"        => "Feedback Level",
                "feedback_level_all"    => "Release all questions",
                "feedback_level_incorrect" => "Release incorrect questions",
                "incorrect_responses_text"  => "Release learners feedback after they have submitted",
                "exam_post_review"      => "Please review your Exam Post details below",
                "exception_list"        => "Exceptions List",
                "no_users_exam"         => "No learners found matching the search criteria",
                "learner_name"          => "Learner Name",
                "excluded"              => "Excluded",
                "max_attempts"          => "Max Attempts",
                "exception_start_date"  => "Exam Start Date",
                "exception_end_date"    => "Exam End Date",
                "exception_submission_date" => "Submission Deadline",
                "exception_time_factor"     => "Extra Time",
                "exc_time_factor_perc"      => "Extra Time Percentage",
                "exc_time_factor_more"      => " % More",
                "hide_exam"                 => "Hide Exam From Learners",
                "use_resume_password"       => "Resume Password",
                "use_resume_password_text"  => "Require users to input a password to resume their exam. Must be 5-20 characters long (case sensitive).",
                "resume_password"           => "Generate",
                "grade_book" => array(
                    "label"      => "Grade Book",
                    "attach"     => "Attach Grade Book to Post",
                    "no_results" => "No Grade Books found to attach."
                )
            ),
            "posts" => array(
                "title_plural"              => "Exam Posts",
                "title_singular"            => "Exam Post",
                "title_heading"             => "Title",
                "course_code"               => "Course Code",
                "course_name"               => "Course Name",
                "target"                    => "Target",
                "downloads"                 => "Started",
                "submissions"               => "Finished",
                "max_attempts"              => "Max",
                "start_date"                => "Start",
                "end_date"                  => "End",
                "sub_date"                  => "Submission",
                "release_score"             => "Release Score",
                "score_review"              => "Score Review",
                "release_start_date"        => "Release Start",
                "release_end_date"          => "Release End",
                "release_feedback"          => "Release Feedback",
                "feedback_review"           => "Feedback Review",
                "created_date"              => "Created",
                "created_by"                => "Created By",
                "updated_date"              => "Updated",
                "updated_by"                => "Updated By",
                "edit"                      => "Edit",
                "actions"                   => "Actions",
                "add_post"                  => "Add Post",
                "edit_post"                 => "Edit Post",
                "text_no_available_posts"   => "No Exams have been posted.",
                "edit_menu" => array(
                    "view"         => "View Activity",
                    "grade"        => "Grade Responses",
                    "edit"         => "Edit Post",
                    "edit_graders" => "Edit Graders",
                    "repost"       => "Re-Post",
                    "delete"       => "Delete Post"
                )
            ),
            "activity" => array(
                "title"                     => "Exam Activity",
                "post_not_found"            => "No posts found to view activity",
                "no_progress_records_found" => "No progress records found.",
                "progress" => array(
                    "student"                       => "Student",
                    "number"                        => "ID",
                    "progress_value"                => "Status",
                    "submission_date"               => "Submission <br /> Date",
                    "late"                          => "Mins Late",
                    "exam_points"                   => "Points",
                    "exam_value"                    => "Max Points",
                    "exam_score"                    => "Score",
                    "created"                       => "Created Date",
                    "createdBy"                     => "Created By",
                    "started"                       => "Started Date",
                    "update"                        => "Update Date",
                    "updatedBy"                     => "Update By",
                    "edit"                          => "Edit",
                    "title_reopen"                  => "Re-Open Progress Record",
                    "reopen_instructions"           => "To Re-Open this record, click &#34;Re-Open Progress Record&#34;",
                    "title_delete"                  => "Delete Progress Record",
                    "delete_instructions"           => "To Delete this record, click &#34;Delete Progress Record&#34;",
                    "no_progress_records_found"     => "No Progress Records Found",
                    "buttons" => array(
                        "btn_cancel" => "Cancel",
                        "btn_reopen" => "Re-open Progress Record",
                        "btn_delete_progress" => "Delete Progress Record"
                    ),
                    "menu" => array(
                        "view"      => "View Progress Details",
                        "reopen"    => "Re-Open Progress Record",
                        "delete"    => "Delete Progress Record"
                    )
                )
            ),
            "analysis" => array(
                "title" => "Item Analysis",
                "no_exam_elements_found" => "No multiple choice exam elements were found for this post.",
                "no_submissions_found" => "No submissions were found for the selected posts.",
                "table_headings" => array(
                    "order" => "Order",
                    "question_text" => "Question Text",
                    "num_scores" => "Exam Takers",
                    "mean" => "Mean",
                    "median" => "Median",
                    "min/max" => "Min/Max",
                    "stdev" => "Stdev",
                    "kr20" => "KR20",
                    "difficulty_index" => "Diff. Index",
                    "upper_27" => "Upper 27%",
                    "lower_27" => "Lower 27%",
                    "disc_index" => "Disc. Index",
                    "point_biserial" => "Point Biserial",
                    "correct_answer" => "Correct",
                    "frequency" => "Frequency"
                )
            ),
            "print" => array(
                "font_size" => "Font Size",
                "print" => "Print",
                "print_view"     => "Printer Friendly View",
                "print_options" => "Print Options",
                "question_number" => "Question #:",
                "options" => array(
                    "exam_id" => "Exam ID",
                    "exam_created_date" => "Exam Created Date",
                    "num_questions" => "# of Questions",
                    "total_exam_points" => "Total Exam Points",
                    "rationale" => "Rationale",
                    "entrada_id" => "Entrada ID",
                    "examsoft_id" => "Examsoft ID",
                    "description" => "Description",
                    "weight" => "Weight",
                    "correct" => "Correct Text",
                    "question_folder" => "Question Folder",
                    "curriculum_tags" => "Curriculum Tags"
                ),
                "errors" => array(
                    "01" => "Invalid exam ID provided."
                )
            ),
            "progress" => array(
                "title" => "Progress Details",
                "table_headers" => array(
                    "element_order"     => "Order",
                    "question_id"       => "ID",
                    "question_text"     => "Text",
                    "question_code"     => "Code",
                    "question_type"     => "Type",
                    "grade_comments"    => "Grade Comments",
                    "student_comments"  => "ScratchPad",
                    "response"          => "Response",
                    "letter"            => "Letter",
                    "correct_letter"    => "Correct Letter",
                    "points"            => "Points",
                    "scored"            => "Scored",
                    "regrade"           => "Regrade",
                    "graded_by"         => "Grader",
                    "graded_date"       => "Graded",
                    "created"           => "Created",
                    "creator"           => "Creator",
                    "started"           => "Started",
                    "updated"           => "Updated",
                    "updater"           => "Updater"
                )
            ),
            "graders" => array(
                "title" => "Edit Graders for %s"
            ),
            "feedback" => array(
                "title"     => "Exam Score and Feedback",
                "labels"    => array(
                    "scores"    => "Exam Scores",
                    "feedback"  => "Exam Feedback"
                ),
                "text" => array(
                    "score" => "Score",
                    "points" => "points",
                    "not_graded" => "Your exam&#39;s free response questions are not yet graded."
                ),
                "errors"    => array(
                    "invalid_post_id"       => "Invalid Post ID",
                    "invalid_progress_id"   => "Invalid Progress ID",
                    "exam_not_submitted"    => "Exam not submitted",
                    "other_users_record"    => "You have attempted to access a record of another learner.",
                    "no_scores_feedback"    => "No exam scores or feedback have not been released."
                )
            ),
            "reports" => array(
                "category" => array(
                    "title" => "Curriculum Tags",
                    "admin" => array(
                        "title"                 => "Relelase Learner Curriculum Tags Report",
                        "posts"                 => "Posts",
                        "link_01"               => "No curriculum tag report is setup for this post, click ",
                        "link_02"               => "here",
                        "link_03"               => " to add one.",
                        "release_start_date"    => "Release Start Date",
                        "release_end_date"      => "Release End Date",
                        "audience"              => "Learners",
                        "audience_list"         => "Audience List",
                        "browse_audience"       => "Browse Audience",
                        "browse_sets"           => "Browse Curriculum Tag Sets",
                        "curriculum"            => "Curriculum Tag Sets",
                        "curriculum_tag_name"   => "Curriculum Tag Name",
                        "tag"                   => "Curriculum Tag Set",
                        "no_tags"               => "No Curriculum Tags",
                        "learner_name"          => "Learner Name",
                        "no_learners"           => "No Learners have submitted.",
                        "edit_category"         => "Edit Settings"
                    ),
                    "faculty" => array(
                        "title"                 => "Curriculum Tags Report",
                        "posts"                 => "Posts",
                        "browse_audience"       => "Browse Audience",
                        "browse_sets"           => "Browse Curriculum Tag Sets",
                        "curriculum"            => "Curriculum Tag Sets",
                        "curriculum_tag_name"   => "Curriculum Tag Name",
                        "tag"                   => "Curriculum Tag Set",
                        "no_tags"               => "No Curriculum Tags",
                        "learner_name"          => "Learner Name",
                        "no_learners"           => "No Learners have submitted.",
                        "edit_category"         => "Edit Settings"
                    ),
                    "errors" => array(
                        "invalid_post_id"       => "Invalid Post ID",
                        "invalid_progress_id"   => "Invalid Progress ID",
                        "exam_not_submitted"    => "Exam not submitted",
                        "other_users_record"    => "You have attempted to access a record of another learner.",
                        "no_scores_feedback"    => "No exam scores or feedback have not been released."
                    ),
                ),
            ),
            "incorrect" => array(
                "title"     => "Incorrect Responses"
            ),
            "preview" => array(
                "title" => "Exam Preview",
                "title_modal_preview_exams" => "Add Exam Preview",

            ),
            "my_exams" => array(
                "title" => "My Exams",
                "message_not_submitted"    => "There are %s exam(s) that have not been submitted.",
                "message_submitted"    => "There are %s exam(s) that have been submitted.",
                "text_submitted" => "Submitted",
                "text_not_submitted" => "Not Submitted",
                "text_submitted_exams" => "Submitted Exams",
                "text_not_submitted_exams" => "Not Submitted Exams",
                "text_exam_activity"    => "Exam Activity",
                "text_loading_exam_activity"    => "Loading exam activity.",
                "text_no_exam_activity"    => "No exam activity found.",
                "table_headers" => array(
                    "name"                      => "Name",
                    "resume"                    => "Resume",
                    "new"                       => "New Attempt",
                    "start_date"                => "Start",
                    "end_date"                  => "End",
                    "mandatory"                 => "Required",
                    "submission_deadline"       => "Submission Deadline",
                    "score_review"              => "Release Score",
                    "feedback_review"           => "Release Feedback",
                    "score_start_date"          => "Release Start",
                    "score_end_date"            => "Release End",
                    "course_code"               => "Course Code",
                    "course_name"               => "Course Name",
                ),
                "tool_tips"     => array(
                    "name"                      => "This link will take you to the exam post.",
                    "resume"                    => "If you can resume an attempt or not.",
                    "new"                       => "If you can start a new attempt or not.",
                    "start_date"                => "The exam start date.",
                    "end_date"                  => "The exam end date.",
                    "score_review"              => "If score review is available at this time.",
                    "feedback_review"           => "If feedback review is available at this time.",
                    "score_start_date"          => "The release start date.",
                    "score_end_date"            => "The release end date.",
                    "course_code"               => "The course code.",
                    "course_name"               => "The course name.",
                    "mandatory"                 => "If you have to complete the exam or not.",
                    "submission_deadline"       => "If this is set, you will not be able to submit after this deadline."
                ),
                "buttons" => array(
                    "btn_cancel"    => "Cancel",
                    "btn_close"     => "Close",
                    "btn_submit"    => "Submit Exam",
                    "btn_save"      => "Save",
                    "btn_saved"     => "Saved",
                ),
            ),
        ),
        "questions" => array (
            "title" => "Questions",
            "breadcrumb" => array(
                "title" => "Questions"
            ),
            "buttons" => array(
                "add_question"                      => "Add A New Question",
                "question_list_view_toggle_title"   => "Toggle Question List View",
                "question_detail_view_toggle_title" => "Toggle Question Detail View",
                "delete_questions"                  => "Delete Questions",
                "move_questions"                    => "Move Questions",
                "group_questions"                   => "Group Questions",
                "tag_questions"                     => "Tag Questions",
                "add_answer"                        => "Add Answer",
                "add_stem"                          => "Add Item Stem"
            ),
            "placeholders" => array(
                "question_bank_search" => "Begin Typing to Search the Questions..."
            ),
            "add-question" => array(
                "title" => "Create New Question",
                "breadcrumb" => array(
                    "title" => "Add Question"
                ),
				"failed_to_create" => "Sorry, we were unable to add this question to the exam.",
				"already_attached" => "Sorry, the question you are attempting to add is already attached to the exam.",
				"group_already_posted" => "Sorry, you can not add questions this group, it is attached to an exam that has already been posted.",
            ),
            "edit-question" => array(
                "title" => "Editing Question:",
                "breadcrumb" => array(
                    "title" => "Edit Question"
                ),
                "success_msg_01" => "The question has successfully updated. You will be redirected to the question bank index, please ",
                "success_msg_02" => "click here",
                "success_msg_03" => " if you do not wish to wait.",
                "success_msg_04" => "The question has successfully updated. You will be redirected to edit-exam, please ",
            ),
            "add-permission" => array(
                "title" => "Add Question Permission",
                "breadcrumb" => array(
                    "title" => "Add Question Permission"
                ),
                "labels" => array(
                    "label_contact_type"    => "Contact Type",
                    "label_contact_name"    => "Contact Name"
                ),
                "contact_types" => array(
                    "proxy_id"          => "Individual",
                    "organisation_id"   => "Organisation",
                    "course_id"         => "Course"
                )
            ),
            "exam" => array(
                "label_question_type"           => "Question Type",
                "label_question_text"           => "Question Text",
                "label_question_description"    => "Question Description",
                "label_question_code"           => "Question Code",
                "label_question_item_stem_text" => "Item Stem",
                "label_grading_scheme"          => "Grading Scheme",
                "label_rationale"               => "Rationale for this Question",
                "label_question_correct_text"   => "Correct Text",
                "label_answers"                 => "Number of Answers",
                "label_question_permissions"    => "Question Permissions",
                "btn_add_question"              => "Add New Question",
                "btn_attach_question"           => "Attach Selected",
                "btn_add_attach_question"       => "Add &amp; Attach New Question",
                "text_error_exam_in_progress"   => "You are editing an Exam that learners have already started. If you delete the records for those students, then you can edit this exam, otherwise if you make any changes to this exam, a new exam will be automatically be created. If you want learners to use that exam, you will need to create a new post."
            ),
            "index" => array(
                "title" => "Questions",
                "breadcrumb" => array(
                    "title" => "Questions"
                ),
                "headers" => array(
                    "updated"       => "Updated",
                    "question_id"   => "ID",
                    "description"   => "Description"
                ),
                "failed_to_create"                      => "Sorry, we were unable to add this question to the exam.",
                "group_already_posted"                  => "Sorry, you can not add questions this group, it is attached to an exam that has already been posted.",
                "already_attached"                      => "Sorry, the question you are attempting to add is already attached to the exam.",
                "cannot_attach_group_question_to_exam"  => "You cannot attach Group Questions directly to Forms.",
                "no_available_questions"                => "There are no questions available to attach to this exam.",
                "add_question_notice"                   => "Please check off the questions you wish to add to your exam and click the Add Elements button below.",
                "no_questions_found"                    => "You currently have no questions to display. To Add a new Question click the Add a New Question button above.",
                "title_modal_delete_questions"          => "Delete Questions",
                "text_modal_no_questions_selected"      => "No Questions Selected to delete.",
                "text_modal_delete_questions"           => "Are you sure you would like to delete the selected Questions(s)?",
                "title_modal_move_questions"            => "Move Questions",
                "text_modal_no_questions_move"          => "No Questions Selected to move.",
                "text_modal_move_questions"             => "Are you sure you would like to move the selected Questions(s)?",
                "text_modal_move_destination"           => "Please choose a destination folder.",
                "title_modal_group_questions"           => "Group Questions",
                "title_modal_linked_questions"          => "Linked Question Groups",
                "title_modal_preview_questions"         => "Preview Question",
                "browse_question_group"                 => "Browse Question Groups",
                "select_question_group"                 => "Select Question Group",
                "text_question_bank_toggle_title"       => "Question Bank View Details",
                "text_question_bank_select_title"       => "Question Bank Select All",
                "title_modal_delete_folders"            => "Delete Folders",
                "text_modal_no_folder_selected"         => "No Folders selected to delete.",
                "text_modal_delete_folders"             => "Please confirm you would like to delete the selected Folder(s)?"
            ),
            "add-questions" => array(
                "title" => "Add Question",
                "breadcrumb" => array(
                    "title" => "Add Question"
                ),
                "failed_to_create" => "Sorry, we were unable to add this question to the exam.",
                "already_attached" => "Sorry, the question you are attempting to add is already attached to the exam."
            ),
            "answers" => array(
                "label_answer_text"                 => "Answer Text",
                "label_answer_category"             => "Answer Category",
                "label_answer_performance_flag"     => "Flag",
                "label_answer_correct_flag"         => "Correct",
                "label_row"                         => "Answer",
                "label_row_fnb"                     => "Fill in the blank",
                "label_row_match"                   => "Match Answer Choice",
                "text_modal_delete_answers"         => "Delete Answers",
                "text_modal_no_answers_selected"    => "No answer selected",
                "title_modal_delete_answers"        => "Delete Answers",
                "text_modal_question_versions_used_already" => "This version of the question has already been used and no modifications to the answers are allowed.",
                "buttons" => array(
                    "add_answer"              => "Add Answer"
                ),
                "fnb" => array(
                    "title_modal_add_correct" => "Add Correct fill in the blank responses",
                    "correct_answers"     => "Correct Answers",
                    "add_correct"         => "Add Correct",
                    "add_correct_close" => "Add Correct and Close",
                    "add_more"            => "Add More Correct",
                    "add-correct-text"  => "Add Correct Text for Blank # "

                )
            ),
            "match" => array(
                "label_row" => "Match Item Stem",
            ),
            "add-folder" => array(
                "title" => "Add Folder",
                "breadcrumb" => array(
                    "title" => "Add Folder"
                ),
                "failed_to_create" => "Sorry, we were unable to add this folder."
            ),
            "edit-folder" => array(
                "title" => "Edit Folder",
                "breadcrumb" => array(
                    "title" => "Edit Folder"
                ),
                "failed_to_create" => "Sorry, we were unable to edit this folder."
            ),
            "folder" => array(
                "label_folder_parent_id"      => "Parent Folder",
                "label_folder_title"          => "Folder Text",
                "label_folder_description"    => "Folder Description",
                "label_folder_order"          => "Folder Order",
                "label_folder_image_id"       => "Folder Image Color",
                "label_folder_author"         => "Folder Authors",
                "buttons" => array(
                    "add_folder"              => "Add New Folder",
                    "edit_folder"             => "Edit Folder",
                    "delete_folder"           => "Delete Folder",
                    "delete_folders"          => "Delete Folders",
                    "question_bank_toggle_title" => "Question Bank Viability"
                )
            ),
            "import" => array(
                "text_import_success_01"    => "Successfully imported",
                "text_import_success_02"    => "exam question(s).",
                "text_import_success_03"    => "Successfully imported",
                "text_redirect_01"          => "You will now be redirected to the selected folder; this will happen <strong>automatically</strong> in 5 seconds or ",
                "text_redirect_02"          => "click here",
                "text_redirect_03"          => " to continue.",
                "text_no_questions"         => "No questions found.",
                "text_please_review"        => "Please review the questions below before confirming the import.",
                "text_default_folder"       => "Default folder (optional)",
                "text_select_folder"        => "Select a folder",
                "label_question_text"       => "Questions text",
                "title_import"              => "Import Exam Questions",
                "btn_import_q"              => "Import Questions",
                "btn_confirm"               => "Confirm Question Import"
            ),
        ),
        "groups" => array(
            "title" => "Grouped Questions",
            "breadcrumb" => array(
                "title" => "Grouped Questions"
            ),
            "buttons" => array(
                "add_group" => "Add Group",
                "group_list_view_toggle_title" => "Toggle Group List View",
                "group_detail_view_toggle_title" => "Toggle Group Detail View"
            ),
            "placeholders" => array (
                "group_bank_search" => "Begin Typing to Search the Groups..."
            ),
            "add-group" => array(
                "title" => "Create New Group",
                "breadcrumb" => array(
                    "title" => "Add Group"
                ),
            ),
            "edit-group" => array(
                "breadcrumb" => array(
                    "title" => "Edit Group",
                ),
                "title" => "Editing Group:",
                "title_q" => "Edit Grouped Question",
                "title_remove_from_group" => "Remove Question(s)",
                "label_title" => "Title",
                "label_description" => "Description",
                "label_permission" => "Permissions",
                "text_back_to_exam" => "Back to Exam",
                "text_remove_from_group" => "Are you sure you want to remove the following question(s) from the group?",
                "text_add_and_attach" => "Add & Attach Question",
                "text_attached_questions" => "Attached Questions",
                "text_add_questions" => "Add Questions",
				"text_attach_questions" => "Attach Questions",
                "text_delete_questions" => "Delete Questions",
                "text_instructions" => "Note: to rearrange order, drag the questions, they will automatically save. To update other settings click save.",
                "error" => array(
                    "01a" => "Your account does not have the permissions required to edit this group. ",
                    "01b" => "If you believe you are receiving this message in error please contact ",
                    "01c" => " for assistance.",
                    "02" => "No Grouped Question found with the identifier supplied.",
                    "03" => "Your account does not have the permissions required to use this feature of this module."
                ),
                "js" => array(
                    "redirect" => " Redirecting to your cloned Group."
                )
            ),
            "add-permission" => array(
                "title" => "Add Permission",
                "breadcrumb" => array(
                    "title" => "Add Permission"
                ),
                "labels" => array(
                    "label_contact_type"    => "Contact Type",
                    "label_contact_name"    => "Contact Name"
                )
            ),
            "add-element" => array(
                "title" => "Add Elements to Group",
                "breadcrumb" => array(
                    "title" => "Add Elements to Group"
                )
            ),
            "group" => array(
                "label_group_type"              => "Group Type",
                "label_group_title"             => "Group Title",
                "label_scale_title"             => "Title",
                "label_group_description"       => "Group Description",
                "label_group_permissions"       => "Group Permissions",
                "title_group_questions"         => "Group Questions",
                "title_modal_delete_question"   => "Delete Question",
                "title_modal_delete_group"      => "Delete Question Group",
                "btn_add_question"              => "Add Question",
                "btn_add_group"                 => "Add Group",
                "btn_add_group_question"        => "Add Grouped Question",
                "btn_delete_group"              => "Delete Group",
                "btn_add_data_src"              => "Add Data Source",
                "btn_add_group_el"              => "Add Group Question",
                "text_no_attached_questions"    => "There are currently no questions attached to this group.",
                "text_modal_delete_question"    => "Would you like to delete this group element?",
                "no_available_questions"        => "There are no questions to display.",
                "delete_group_question_modal"   => "Are you sure you want to delete this Question Group?"
            ),
            "index" => array(
                "title"                         => "Grouped Questions",
                "text_modal_delete_groups"      => "Are you sure you would like to delete the selected Group(s)?",
                "title_modal_delete_groups"     => "Delete Groups",
                "title_modal_add_group"         => "Add Question Group",
                "text_modal_no_groups_selected" => "No Groups Selected to delete.",
                "no_groups_found"               => "You currently have no groups to display. To Add a new group click the Add Group button above.",
                "btn_attach_group"              => "Attach Group",
                "btn_create_and_attach_question" => "Create and Attach",
                "no_exam_found"                 => "Sorry, there was a problem finding the exam you are trying to edit.",
                "already_attached"              => "The selected group could not be attached because one or more questions has already been added to the exam",
            ),
            "api" => array(
                "error" => array(
                    "01" => "There was an error while attempting to delete the question (Ver ID: ",
                    "01b" => ") from the group.",
                    "02" => "You do not have permission to remove that question (Ver ID: ",
                    "02b" => ") from the group."
                ),
                "success" => array(
                    "01" => "Successfully removed the question(s) from the group.",
                )

            )
        ),
        "posts" => array(
            "text_no_available_posts"       => "No Exams have been posted.",
            "text_no_expiration"            => "No Expiration",
            "text_no_end_date"              => ", no end date",
            "text_no_sub_date"              => "No submission deadline",
            "text_available_until"          => "This exam was only available until",
            "text_please_contact"           => "Please contact a coordinator for assistance if required.",
            "text_starting"                 => "You will be able to attempt this exam starting",
            "text_not_in_audience"          => "You are not in the audience for this exam.",
            "text_not_viewable_students"    => "This exam is not viewable by students.",
            "text_not_in_audience_user"     => "User not in the audience for exam.",
            "text_exam_information"         => "Exam Information",
            "text_opp"                      => "One per page.",
            "text_aop"                      => "All on one page.",
            "text_vpp"                      => "Various amounts on each page.",
            "text_submitted"                => "Submitted",
            "text_started"                  => "In Progress",
            "btn_feedback"                  => "Feedback",
            "btn_category"                  => "Category",
            "btn_resume"                    => "Resume",
            "btn_incorrect"                 => "Incorrect Responses",
            "feedback_not_aval"             => "Feedback not available",
            "label_exam_activity"           => "Exam Activity",
            "label_attached_exams"          => "Attached Exams",
            "label_list_of_attached_exams"  => "List of Attached Exams",
            "label_exam_title"              => "Exam Title",
            "label_exam_expires"            => "Exam Expires",
            "label_no_exams_learning_event" => "There are no exams currently posted to this learning event.",
            "table_headers" => array(
                "attempts"                  => "Attempts Allowed",
                "instructions"              => "Exam Instructions",
                "available"                 => "Exam available",
                "start_date"                => "Start",
                "end_date"                  => "End",
                "time_limit"                => "Time Limit",
                "auto_submit"               => "Auto Submit",
                "mandatory"                 => "Required",
                "questions_pp"              => "Questions Per Page",
                "total_questions"           => "Total Questions",
                "progress_value"            => "Status",
                "submission_date"           => "Submission Date",
                "submission_deadline"       => "Submission Deadline",
                "exam_points"               => "Points",
                "exam_value"                => "Max Points",
                "exam_score"                => "Score",
            ),
            "tool_tips" => array(
                "attempts"                  => "How many times you are allowed to take the exam.",
                "available"                 => "The date range the exam is available.",
                "time_limit"                => "The time you have to take your exam.",
                "auto_submit"               => "If this is on then the exam will auto submit once the timer runs out.",
                "mandatory"                 => "If you have to complete the exam or not.",
                "total_questions"           => "The number of questions in the exam.",
                "submission_deadline"       => "If this is set, you will not be able to submit after this deadline."
            )
        ),
        "attempt" => array(
            "not_answered"          => " Questions not answered, please answer questions: ",
            "all_answered"          => "All questions answered.",
            "unknown_error"         => "Unknown error while checking question responses.",
            "title_exam_submission" => "Exam Submission Confirmation",
            "title_exam_time_limit" => "Exam Time Limit Warning",
            "title_exam_self_timer" => "Exam Self Timer",
            "title_scratch_pad"     => "ScratchPad",
            "text" => array(
                "submit_confirmation"   => "Click Submit to submit your exam.",
                "return_to_exam"        => "Press Cancel to return to the exam.",
                "return_to_exam_close"  => "Press Close to return to the exam.",
                "no_id_error"           => "In order to attempt a exam, you must provide a valid exam posting identifier.",
                "no_post_id_error"      => "Failed to provide a post id when attempting to take an exam.",
                "id_invalid"            => "The ID provided is invalid.",
                "user_invalid"          => "Your account information does not match the creator of this exam.",
                "submit_success"        => "Your exam has been submitted successfully.",
                "submit_auto_forward"   => "You will be auto forwarded to exam score and feedback in 5 seconds.",
                "submit_already"        => "Your exam has already been submitted.",
                "submit_already2"       => "To start a new attempt or review ",
                "submit_fail"           => "Your exam has not been submitted successfully.",
                "backtrack_error_01"    => "You have not completed all the questions on a previous page, please answer all the questions on found on this ",
                "page"                  => "page",
                "secure_mode_required"  => "You are required to use Safe Exam Browser to access this exam. Please launch Safe Exam Browser and try again.",
                "secure_mode_required_rpnow"  => "You are required to use RPNow Exam Browser to access this exam. Please launch RPNow Exam Browser and try again.",
                "access_time_sub_past"  => "The submission deadline for this exam has already past.",
                "access_time_end_past"  => "The start period for this exam has already past.",
                "access_time_future"    => "The start date for this exam already has not been reached yet.",
				"access_time_unknown"   => "You are not able to take this exam at this time.",
                "no_access_audience"    => "You are not in the audience for this exam.",
                "label_navigation"      => "Navigation",
                "label_calculator"      => "Calculator",
                "label_self-timer"      => "Self Timer",
                "label_time_left"       => "Time Left",
                "answer_check"          => "Please wait while checking for missing answers.",
                "time_limit_error_01"   => "Your exam time limit is reduced as the late submission deadline occurs before the time limit ends, please submit your exam before the submission deadline.",
                "auto_submit_msgs_0"    => "You have less than 5 minutes before your exam auto submits.",
                "auto_submit_msgs_1"    => "You have less than 5 minutes to submit your exam.",
                "auto_submit_msgs_2"    => "You have less than 1 minutes before your exam auto submits.",
                "auto_submit_msgs_3"    => "You have less than 1 minutes to submit your exam.",
                "bad_resume_password"   => "The resume password was invalid. Please try again",
                "no_resume_password"    => "Please enter a resume password to continue your ongoing exam",
                "review_link_01"        => "Click",
                "review_link_02"        => "here",
                "review_link_03"        => "to review them.",
                "no_scores_feedback"    => "No exam scores or feedback has not been released.",
                "open_link_title"       => "Open link",
                "open_link"             => "To open this link in a new window, click the link:",
                "self_timer_text"       => "Add a self timer here.",
                "self_timer_title"      => "Self Timer",
                "time_hours"            => "Hours",
                "time_mins"             => "Minutes"
            ),
            "buttons" => array(
                "btn_cancel"    => "Cancel",
                "btn_close"     => "Close",
                "btn_submit"    => "Submit Exam",
                "btn_save"      => "Save",
                "btn_saved"     => "Saved",
            ),
            "feedback" => array(
                "title"     => "Exam Score and Feedback",
            ),
        ),
        "element" => array(
            "text_no_available_element" => "No Elements available to views"
        )

    ),
    
     /**
     * Assessments Module
     */
    "assessments" => array(
        "title" => "Assessment & Evaluation",
        "breadcrumb" => array(
            "title" => "Assessment & Evaluation"
        ),
        "forms" => array(
            "title" => "Forms",
            "breadcrumb" => array(
                "title" => "Forms"
            ),
            "buttons" => array(
                "add_form" => "Add Form",
                "delete_form" => "Delete Form"
            ),
            "placeholders" => array(
                "form_bank_search" => "Begin Typing to Search the Forms..."
            ),
            "add-form" => array(
                "title" => "Create New Form",
                "breadcrumb" => array(
                    "title" => "Add Form"
                ),
            ),
            "edit-form" => array(
                "title" => "Editing Form:",
                "breadcrumb" => array(
                    "title" => "Edit Form"
                ),
                "form_not_found" => "Sorry, there was a problem loading the form using that ID.",
                "no_form_elements" => "There are currently no items attached to this form."
            ),
            "add-permission" => array(
                "title" => "Add Permission",
                "breadcrumb" => array(
                    "title" => "Add Permission"
                ),
                "labels" => array(
                    "label_contact_type"    => "Contact Type",
                    "label_contact_name"    => "Contact Name"
                ),
                "contact_types" => array(
                    "proxy_id"          => "Individual",
                    "organisation_id"   => "Organisation",
                    "course_id"         => "Course"
                )
            ),
            "form" => array(
                "label_form_type"           => "Form Type",
                "label_form_title"          => "Form Title",
                "label_form_description"    => "Form Description",
                "label_form_permissions"    => "Form Permissions",
                "title_form_items"          => "Form Items",
                "title_form_info"           => "Form Information",
                "title_modal_delete_element" => "Delete Element",
                "btn_add_single_item"       => "Add Individual Item(s)",
                "btn_add_free_text"         => "Add Free Text",
                "btn_add_item"              => "Add Item",
                "btn_add_rubric"            => "Add Grouped Item",
                "btn_add_data_src"          => "Add Data Source",
                "btn_add_form_el"           => "Add Text",
                "text_no_attached_items"    => "There are currently no items attached to this form.",
                "text_modal_delete_element" => "Would you like to delete this form element?",
                "text_modal_no_form_items_selected" => "No Forms Items Selected to delete.",
                "text_modal_delete_form_items" => "Please confirm you would like to delete the selected <span></span> Form Item(s).",
                "text_modal_delete_form_items_success" => "You have successfully deleted the selected <span></span> Form Item(s).",
                "text_modal_delete_form_items_error" => "Unfortunately, an error was encountered while attempting to remove the selected <span></span> Form Item(s).",
            ),
            "add-element" => array(
                "title" => "Add Form Element",
                "breadcrumb" => array(
                    "title" => "Add Element"
                ),
                "failed_to_create" => "Sorry, we were unable to add this element to the form.",
                "already_attached" => "Sorry, the element you are attempting to add is already attached to the form.",
                "no_available_items" => "There are no items available to attach to this form.",
                "add_element_notice" => "Please check off the items you wish to add to your form and click the Add Elements button below."
            ),
            "index" => array(
                "delete_success" => "Forms have been successfully deleted. You will now be taken back to the Forms index.",
                "delete_error" => "There was a problem deleting the forms you selected. An administrator has been informed, please try again later.",
                "title_heading" => "Form Title",
                "created_heading" => "Date Created",
                "items_heading" => "Items",
                "no_forms_found" => "You currently have no forms to display. To Add a new form click the Add Form button above.",
                "text_modal_no_forms_selected" => "No Forms Selected to delete.",
                "text_modal_delete_forms" => "Please confirm you would like to delete the selected Form(s).",
                "no_forms_found" => "You currently have no forms to display. To Add a new form click the Add Form button above.",
                "title_modal_delete_forms" => "Delete Forms"
            )
        ),
        "items" => array (
            "title" => "Items",
            "breadcrumb" => array(
                "title" => "Items"
            ),
            "buttons" => array(
                "add_item" => "Add A New Item",
                "item_list_view_toggle_title" => "Toggle Item List View",
                "item_detail_view_toggle_title" => "Toggle Item Detail View",
                "delete_items" => "Delete Items"
            ),
            "placeholders" => array(
                "item_bank_search" => "Begin Typing to Search the Items..."
            ),
            "add-item" => array(
                "title" => "Create New Item",
                "breadcrumb" => array(
                    "title" => "Add Item"
                ),
            ),
            "edit-item" => array(
                "title" => "Editing Item:",
                "item_not_found" => "Unfortunately, there was a problem loading the item using that ID.",
                "breadcrumb" => array(
                    "title" => "Edit Item"
                ),
            ),
            "add-permission" => array(
                "title" => "Add Item Permission",
                "breadcrumb" => array(
                    "title" => "Add Item Permission"
                ),
                "labels" => array(
                    "label_contact_type"    => "Contact Type",
                    "label_contact_name"    => "Contact Name"
                ),
                "contact_types" => array(
                    "proxy_id"          => "Individual",
                    "organisation_id"   => "Organisation",
                    "course_id"         => "Course"
                )
            ),
            "form" => array(
                "label_item_type"           => "Item Type",
                "label_item_text"           => "Item Text",
                "label_item_code"           => "Item Code",
                "label_allow_comments"      => "Allow comments for this Item",
                "label_optional_comments"	=> "Comments are optional",
                "label_mandatory_comments"	=> "Require comments for any response",
                "label_flagged_comments"	=> "Require comments for flagged responses",
                "label_responses"           => "Number of Responses",
                "label_item_permissions"    => "Item Permissions",
                "label_item_objectives"     => "Curriculum Tags",
                "btn_add_item"              => "Add New Item",
                "btn_attach_item"           => "Attach Selected",
                "btn_add_attach_item"       => "Add &amp; Attach New Item"
            ),
            "index" => array(
                "title" => "Items",
                "breadcrumb" => array(
                    "title" => "Items"
                ),
                "failed_to_create" => "Sorry, we were unable to add this item to the form.",
                "already_attached" => "Sorry, the item you are attempting to add is already attached to the form.",
                "cannot_attach_rubric_item_to_form" => "You cannot attach Grouped Item Items directly to Forms.",
                "no_available_items" => "There are no items available to attach to this form.",
                "add_item_notice" => "Please check off the items you wish to add to your form and click the Add Elements button below.",
                "no_items_found" => "You currently have no items to display. To Add a new Item select Add and Attach New Item from the dropdown button above.",
                "no_items_selected" => "No Items selected to attach",
                "title_modal_delete_items" => "Delete Items",
                "text_modal_no_items_selected" => "No Items Selected to delete.",
                "text_modal_delete_items" => "Please confirm you would like to delete the selected Items(s)?",
            ),
            "add-items" => array(
                "title" => "Add Item",
                "breadcrumb" => array(
                    "title" => "Add Item"
                ),
                "failed_to_create" => "Sorry, we were unable to add this item to the form.",
                "already_attached" => "Sorry, the item you are attempting to add is already attached to the form."
            ),
            "responses" => array(
                "label_response_text" => "Response Text",
                "label_response_category" => "Response Category",
                "label_response_performance_flag" => "Flag",
                "label_row" => "Response"
            )
        ),
        "rubrics" => array(
            "title" => "Grouped Items",
            "breadcrumb" => array(
                "title" => "Grouped Items"
            ),
            "buttons" => array(
                "add_rubric" => "Add Grouped Item",
                "rubric_list_view_toggle_title" => "Toggle Grouped Item List View",
                "rubric_detail_view_toggle_title" => "Toggle Grouped Item Detail View"
            ),
            "placeholders" => array (
                "rubric_bank_search" => "Begin Typing to Search the Grouped Items...",
            ),
            "add-rubric" => array(
                "title" => "Create New Grouped Item",
                "breadcrumb" => array(
                    "title" => "Add Grouped Item"
                ),
            ),
            "edit-rubric" => array(
                "title" => "Editing Grouped Item:",
                "breadcrumb" => array(
                    "title" => "Edit Grouped Item"
                ),
            ),
            "add-permission" => array(
                "title" => "Add Permission",
                "breadcrumb" => array(
                    "title" => "Add Permission"
                ),
                "labels" => array(
                    "label_contact_type"    => "Contact Type",
                    "label_contact_name"    => "Contact Name"
                )
            ),
            "add-element" => array(
                "title" => "Add Elements to Grouped Item",
                "breadcrumb" => array(
                    "title" => "Add Elements to Grouped Item"
                )
            ),
            "rubric" => array(
                "label_rubric_type"           => "Grouped Item Type",
                "label_rubric_title"          => "Grouped Item Title",
                "label_scale_title"         => "Title",
                "label_rubric_description"    => "Grouped Item Description",
                "label_rubric_permissions"    => "Grouped Item Permissions",
                "title_rubric_items"          => "Grouped Item Items",
                "title_modal_delete_item" => "Delete Element",
                "btn_add_item"              => "Add Item",
                "btn_add_rubric"            => "Add Grouped Item",
                "btn_add_scale"             => "Add Grouped Item",
                "btn_delete_scale"          => "Delete Grouped Item",
                "btn_add_data_src"          => "Add Data Source",
                "btn_add_rubric_el"           => "Add Grouped Item Item",
                "text_no_attached_items"    => "There are currently no items attached to this rubric.",
                "text_modal_delete_item" => "Would you like to delete this Grouped Item element?",
                "no_available_items" => "There are no items to display.",
                "delete_rubric_item_modal"  => "Are you sure you want to delete this Grouped Item Item?"
            ),
            "index" => array(
                "text_modal_delete_rubrics" => "Please confirm you would like to delete the selected Grouped Item(s)?",
                "title_modal_delete_rubrics" => "Delete Grouped Items",
                "text_modal_no_rubrics_selected" => "No Grouped Items Selected to delete.",
                "no_rubrics_found" => "You currently have no Grouped Items to display. To Add a new Grouped Item click the Add Grouped Item button above.",
                "btn_attach_rubric" => "Attach Grouped Item",
                "btn_create_and_attach_item" => "Create and Attach"
            )
        ),
        "distributions" => array(
            "title" => "Distributions",
            "breadcrumb" => array(
                "title" => "Distributions"
            ),
        ),
        "schedule" => array(
            "title" => "Schedules",
            "breadcrumb" => array(
                "title" => "Schedules"
            ),
            "edit-schedule" => array(
                "title" => "Edit Schedule",
                "breadcrumb" => array(
                    "title" => "Edit"
                ),
                "schedule_information" => "Schedule Information",
                "children_organisation_title" => "Academic Years",
                "children_academic_year_title" => "Streams",
                "children_stream_title" => "Blocks",
                "no_children" => "There are currently no child schedules.",
                "errors" => array(
                    "title" => "The title can not be empty.",
                    "start_date" => "Start date must come before end date.",
                    "schedule_type" => "Invalid schedule type.",
                    "organisation_id" => "An organisation ID is required."
                )
            ),
            "add-schedule" => array(
                "title" => "Add Schedule",
                "breadcrumb" => array(
                    "title" => "Add"
                )
            )
        )
    ),

	/**
	 *  profile Module
	 */
	"profile" => array(
		"title" => "My Profile",
		"breadcrumb" => array(
			"title" => "My Profile"
		),
		"buttons" => array(
			"delete_anotification" => "Delete Active Notifications"
		),
		"placeholders" => array(
			"anotification_bank_search" => "Search the Active Notifications..."
		),
		"index" => array(
			"title_modal_delete_anotification" => "Delete Active Notifications",
			"text_modal_no_anotifications_selected" => "No Active Notifications Selected to delete.",
			"text_modal_delete_anotifications" => "Please confirm you would like to delete the selected Active Notification(s)?",
			"no_anotifications_found" => "You currently have no active notifications to display.",
		)
	),

	"rotationschedule" => array(
        "title" => "Rotation Schedule",
        "breadcrumb" => array(
            "title" => "Rotation Schedule"
        ),
        "import" => array(
            "title" => "Rotation Schedule",
            "breadcrumb" => array(
                "title" => "Rotation Schedule"
            )
        ),
        "edit-draft" => array(
            "title" => "Edit Draft",
            "add-slot" => "Add Slot"
        ),
        "edit" => array(
            "title" => "Edit Schedule",
            "add-slot" => "Add Slot"
        ),
        "drafts" => array(
            "title" => "My Drafts"
        )
    ),
    "default" => array(
        "btn_submit"    => "Submit",
        "btn_save"      => "Save",
        "btn_add"       => "Add",
        "btn_move"      => "Move",
        "btn_back"      => "Back",
        "btn_cancel"    => "Cancel",
        "btn_copy"      => "Copy",
        "btn_update"    => "Update",
        "btn_edit"      => "Edit",
        "btn_delete"    => "Delete",
        "btn_remove"    => "Remove",
        "btn_done"      => "Done",
        "btn_close"     => "Close",
        "btn_preview"           => "Preview",
        "btn_add_elements"      => "Add Elements",
        "btn_next_page"         => "Next Page",
        "btn_previous_page"     => "Previous Page",
        "invalid_req_method"    => "Invalid request method.",
        "invalid_get_method"    => "Invalid GET method.",
        "invalid_post_method"   => "Invalid POST method.",
        "contact_types" => array(
            "proxy_id"          => "Individual",
            "organisation_id"   => "Organisation",
            "course_id"         => "Course"
        ),

		"date_created"   		=> "Date Created",
		"btn_my_drafts" 		=> "My Drafts",
		"btn_publish"   		=> "Publish Draft",
		"btn_unpublish"   		=> "Withdraw Rotation Schedule",
		"btn_import"    		=> "Import",
		"btn_new"       		=> "New",
		"deactivate"    		=> "Deactivate",
		"activate"    			=> "Activate"
	),
    "secure" => array(
        "exams" => array(
            "title" => "Exams",
            "title_singular" => "Exam",
            "breadcrumb" => array(
                "title" => "Exams"
            ),
            "learning_event_exams" => "Learning Event Exams",
            "community_exams" => "Community Exams",
            "index" => array(
                "optional"              => "Optional",
                "required"              => "Required",
                "list_of_secure" => "List of Secure Exams",
                "community_title" => "Community Title",
                "exam_title" => "Exam Title",
                "exam_expires" => "Exam Expires",
                "start_exam" => "Start Exam",
                "exam_loading" => "Exam Loading...",
                "please_wait" => "Please wait while your exam loads. Your exam will start automatically once this is complete."
            ),
            "attempt" => array(
                "not_answered"          => " Questions not answered, please answer questions: ",
                "all_answered"          => "All questions answered.",
                "unknown_error"         => "Unknown error while checking question responses.",
                "title_exam_submission" => "Exam Submission Confirmation",
                "title_exam_time_limit" => "Exam Time Limit Warning",
                "text" => array(
                    "submit_confirmation"   => "Click Submit to submit your exam.",
                    "return_to_exam"        => "Press Cancel to return to the exam.",
                    "return_to_exam_close"  => "Press Close to return to the exam.",
                    "no_id_error"           => "In order to attempt a exam, you must provide a valid exam posting identifier.",
                    "no_post_id_error"      => "Failed to provide a post id when attempting to take an exam.",
                    "id_invalid"            => "The ID provided is invalid.",
                    "submit_success"        => "Your exam has been submitted successfully.",
                    "submit_fail"           => "Your exam has not been submitted successfully.",
                    "backtrack_error_01"    => "You have not completed all the questions on a previous page, please answer all the questions on found on this ",
                    "page"                  => "page",
                    "secure_mode_required"  => "You are required to use Safe Exam Browser to access this exam. Please launch Safe Exam Browser and try again.",
                    "access_time_sub_past"  => "The submission date for this exam has already past.",
                    "access_time_end_past"  => "The start period for this exam has already past.",
                    "access_time_future"    => "The start date for this exam already has not been reached yet.",
                    "no_access_audience"    => "You are not in the audience for this exam.",
                    "label_navigation"      => "Navigation",
                    "label_time_left"       => "Time Left",
                    "answer_check"          => "Please wait while checking for missing answers.",
                    "time_limit_error_01"   => "Your exam time limit is reduced as the late submission deadline occurs before the time limit ends, please submit your exam before the submission deadline.",
                    "bad_resume_password"   => "The resume password was invalid. Please try again",
                    "no_resume_password"    => "Please enter a resume password to continue your ongoing exam",
                    "no_exam_id_error"      => "The exam you are attempting to load could not be found in the system. Please contact a system administrator if you believe this is an error."
                ),
                "buttons" => array(
                    "btn_cancel"    => "Cancel",
                    "btn_close"     => "Close",
                    "btn_submit"    => "Submit Exam",
                    "btn_save"      => "Save"
                ),
                "feedback" => array(
                    "title"     => "Exam Score and Feedback",
                ),
            ),
            "errors" => array(
                "no_exams" => "There are no available secure exams for your learning events.",
                "no_exams_community" => "There are no available secure exams for your communities.",
                "loading" => "An error occurred while loading this exam. Please try again. If the problem persists, please contact an administrator.",
                "entity_missing_1" => "Event Resource Entity Missing for Exam Post: ",
                "entity_missing_2" => "Please contact the system administrator.",
            )
        )
	),



    "settings" => array(
        /**
         * Building/Room management Text
         */
        "building" => array(
            "building" => array(
                "add_building"      => "Add Building",
                "update_building"   => "Update Building",
                "edit_building"   => "Edit Building",
                "building_information"  => "Building Information",
                "building_rooms"  => "Building Rooms",
                "label_name"        => "Building Name:",
                "label_code"        => "Building Code:",
                "label_address1"    => "Address Line 1",
                "label_address2"    => "Address Line 2",
                "label_city"        => "City",
                "label_country"     => "Country",
                "label_country2"    => "Country information not currently available",
                "label_country3"    => "-- Select Country --",
                "label_state"       => "Province / State",
                "label_state_country" => "Please select a Country from above first.",
                "label_postal_code" => "Postal Code",
                "label_example_postal_code" => "90095",
                "label_example"     => "Example:",
                "error_msg" => array(
                    "name"      => "The Building Name is a required field.",
                    "code"      => "The Building Code is a required field.",
                    "address_1" => "The Address Line 1 is a required field.",
                    "city"      => "The City is a required field.",
                    "postal"    => "The Postal Code is a required field.",
                    "country"   => "The selected country does not exist in our countries database. Please select a valid country.",
                    "country2"  => "You must select a country.",
                    "state1"    => "The province / state you have selected does not appear to exist in our database. Please selected a valid province / state.",
                    "state2"    => "Please select a country and then a province/state.",
                    "state3"    => "Province or state format error.",
                    "state4"    => "You must select a province or state.",
                    "insert"    => "There was a problem inserting this Building into the system. The system administrator was informed of this error; please try again later."
                ),
                "success_msg" => array(
                    "success_add_1" => "You have successfully added ",
                    "success_add_2" => " to the system.",
                    "success_add_3" => "You will now be redirected to the Buildings index; this will happen",
                    "success_add_4" => " automatically ",
                    "success_add_5" => "in 5 seconds or ",
                    "success_add_6" => "click here",
                    "success_add_7" => " to continue.",
                    "success_update_1" => "You have successfully updated ",
                    "success_update_2" => " to the system.",
                    "success_update_3" => "You will now be redirected to the Buildings index; this will happen",
                    "success_update_4" => " automatically ",
                    "success_update_5" => "in 5 seconds or ",
                    "success_update_6" => "click here",
                    "success_update_7" => " to continue.",
                ),
                "delete" => array(
                    "title" => "Delete a Building",
                    "label_list" => "List of Buildings",
                    "label_building" => "Building",
					"label_delete"  => "Confirm Delete",
                    "notice_msg" => array(
                        "review" => "Please review the following Buildings to ensure that you wish to delete them."
                    ),
                    "success_msg" => array(
                        "success_add_1" => "You have successfully deleted ",
                        "success_add_2" => " Buildings from the system.",
                        "success_add_3" => "You will now be redirected to the Buildings index; this will happen",
                        "success_add_4" => " automatically ",
                        "success_add_5" => "in 5 seconds or ",
                        "success_add_6" => "click here",
                        "success_add_7" => " to continue.",
                    ),
                    "error_msg" => array(
                        "error_add_1" => "No Buildings were selected to be deleted. You will now be redirected to the Buildings index; this will happen",
                        "error_add_2" => " automatically ",
                        "error_add_3" => "in 5 seconds or ",
                        "error_add_4" => "click here",
                        "error_add_5" => " to continue.",
                        "update"      => "There was a problem updating this Building into the system. The system administrator was informed of this error; please try again later."
                    )
                ),
                "manage" => array(
                    "manage_buildings"  => "Manage Buildings",
                    "manage_rooms"  => "Manage Rooms",
                    "manage_locations"  => "Manage Locations",
                    "label_list" => "List of Buildings",
                    "label_building" => "Building",
                    "label_building_code" => "Code",
                    "label_building_name" => "Name",
                    "add_building" => "Add New Building",
                    "delete_selected" => "Delete Selected",
                    "no_buildings" => "There are currently no buildings assigned to this "
                )
            ),
            "room" => array(
                "add_room"          => "Add Room",
                "edit_room"         => "Edit Room",
                "room_information"         => "Room Information",
                "update_room"       => "Update Room",
                "label_building"    => "Building",
                "select_building"   => "-- Select Building --",
                "info_not_available" => "Building information not currently available.",
                "label_name"        => "Room Name",
                "label_number"      => "Room Number",
                "label_description" => "Room Description",
                "label_max_occupancy" => "Room Max Occupancy",
                "error_msg" => array(
                    "name"      => "The Building is a required field.",
                    "room_name" => "The Room Number is a required field.",
                    "insert1"   => "There was a problem inserting this Room into the system. The system administrator was informed of this error; please try again later.",
                    "insert2"   => "There was a problem inserting this Room into the system. The system administrator was informed of this error; please try again later."
                ),
                "success_msg" => array(
                    "success_add_1" => "You have successfully added Room ",
                    "success_add_2" => " to the system.",
                    "success_add_3" => "You will now be redirected to the Buildings index; this will happen",
                    "success_add_4" => " automatically ",
                    "success_add_5" => "in 5 seconds or ",
                    "success_add_6" => "click here",
                    "success_add_7" => " to continue.",
                    "success_update_1" => "You have successfully added Room ",
                    "success_update_2" => " to the system.",
                    "success_update_3" => "You will now be redirected to the Buildings index; this will happen",
                    "success_update_4" => " automatically ",
                    "success_update_5" => "in 5 seconds or ",
                    "success_update_6" => "click here",
                    "success_update_7" => " to continue.",
                ),
                "delete" => array(
                    "title"         => "Delete a Room",
                    "label_list"    => "List of Rooms",
                    "label_room"    => "Room",
                    "label_delete"  => "Confirm Delete",
                    "notice_msg" => array(
                        "review" => "Please review the following Rooms to ensure that you wish to delete them."
                    ),
                    "success_msg" => array(
                        "success_add_1" => "You have successfully removed ",
                        "success_add_2" => " Room from the system.",
                        "success_add_3" => "You will now be redirected to the Room index; this will happen",
                        "success_add_4" => " automatically ",
                        "success_add_5" => "in 5 seconds or ",
                        "success_add_6" => "click here",
                        "success_add_7" => " to continue.",
                    ),
                    "error_msg" => array(
                        "error_add_1" => "No Rooms were selected to be deleted. You will now be redirected to the Buildings index; this will happen",
                        "error_add_2" => " automatically ",
                        "error_add_3" => "in 5 seconds or ",
                        "error_add_4" => "click here",
                        "error_add_5" => " to continue.",
                    )
                ),
                "manage" => array(
                    "manage_rooms"      => "Manage Rooms",
                    "label_list"        => "List of Rooms",
                    "label_room"        => "Room",
                    "label_room_number" => "Number",
                    "label_room_name"   => "Name",
                    "label_room_max_occupancy" => "Max Occupancy",
                    "add_room" => "Add New Room",
                    "delete_selected"   => "Delete Selected",
                    "no_rooms"          => "There are currently no rooms assigned to this "
                )
            ),
            "migrate" => array(
                "match_01"              => "Successfully matched",
                "match_02"              => "to its room_id.",
                "manually_match"        => "The following event locations were unable to be matched with rooms. Please match them manually.",
                "label_event_location"  => "Match Event Locations",
                "adding_page"           => "Adding Page",
                "match"                 => "Match ",
                "select_room"           => "-- Select a room --",
                "locations_matched"     => "All event locations have been matched with room_ids!",
                "no_room_names"         => "No room names found.",
                "no_unmatched"          => "No unmatched event locations found."
            )
        )
    ),


    /**
     * Community Text
     *
     */
    "community" => array(
        "discussion" => array(
            "error_open"	=> "Error updating Discussion Boards Open.",
            "error_request" => "Invalid request method."
        ),
    ),

    /**
     *  Admin - Settings - Grading Scale
     */
    "Grading Scale" => "Grading Scale",
    "Add New Grading Scale" => "Add New Grading Scale",
    "Edit Grading Scale" => "Edit Grading Scale",
    "Delete Grading Scale" => "Delete Grading Scale",
    "Add Range" => "Add Range",
    "Twitter" => "Twitter",
    "Twitter Handle" => "Twitter Handle",
    "Twitter Hastags" => "Twitter Hastags",

    "Manage Organisations" => "Manage Organisations",
    "Add New Organisation" => "Add New Organisation",
    "Delete Selected" => "Delete Selected",

    "Add Organisation" => "Add Organisation",
    "Organisation Name" => "Organisation Name",
    "Description" => "Description",
    "Country" => "Country",
    "Province / State" => "Province / State",
    "Please select a <b>Country</b> from above first." => "Please select a <b>Country</b> from above first.",
    "City" => "City",
    "Postal Code" => "Postal Code",
    "Address 1" => "Address 1",
    "Address 2" => "Address 2",
    "Telephone" => "Telephone",
    "Fax" => "Fax",
    "E-Mail Address" => "E-Mail Address",
    "Website" => "Website",
    "Interface Template" => "Interface Template",
    "AAMC Institution ID" => "AAMC Institution ID",
    "AAMC Institution Name" => "AAMC Institution Name",
    "AAMC Program ID" => "AAMC Program ID",
    "AAMC Program Name" => "AAMC Program Name",

    "Organisation Details" => "Organisation Details",
    "Delete Organisations" => "Delete Organisations"

);
