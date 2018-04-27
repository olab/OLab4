<!doctype html>
<html class="no-js" lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta charset="<?php echo DEFAULT_CHARSET; ?>" />
        <title>%TITLE%</title>

        <meta name="description" content="%DESCRIPTION%" />
        <meta name="keywords" content="%KEYWORDS%" />

        <meta name="robots" content="index, follow" />

        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/bootstrap.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="all" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/font-awesome/css/font-awesome.min.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="all" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="all" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/print.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="print" />
        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="all" />
        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/style.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="all" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/javascript/calendar/css/xc2_default.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="all" />
        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/javascript/entradajs/styles/normalize.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/javascript/entradajs/styles/entrada-shim.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/javascript/entradajs/styles/default.css" rel="stylesheet" type="text/css" />        <script>
            if (self !== top) {
                top.location = self.location;
            }
        </script>
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/jquery/jquery-ui.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" />
        <script>
            %JAVASCRIPT_TRANSLATIONS%
        </script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/jquery/jquery.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/jquery/jquery-ui.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script>jQuery.noConflict();</script>
        <script>
            var ENTRADA_URL = '<?php echo ENTRADA_URL; ?>'; 
            var ENTRADA_RELATIVE = '<?php echo ENTRADA_RELATIVE; ?>'; 
            var TEMPLATE_URL = '<?php echo $ENTRADA_TEMPLATE->url(); ?>'; 
            var TEMPLATE_RELATIVE = '<?php echo $ENTRADA_TEMPLATE->relative(); ?>';
            var JWT = '<?php echo $ENTRADA_USER ? $ENTRADA_USER->getToken() : ''; ?>';
            var API_URL = '<?php echo ENTRADA_URL . "/api/v2"; ?>';
        </script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/vue/vue.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/vue/vuex.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/axios/axios.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/entradajs/EntradaJS/entradajs.js"></script>
        <script>
            // EntradaJS Bootstrap
            jQuery(function() {
                try {
                    let bootstrap = new Bootstrap('<?php echo ENTRADA_RELATIVE; ?>/javascript/entradajs');
                    bootstrap.boot('#app-root');
                } catch(ex) {
                    console.warn('Warning: This browser is currently not supported by EJS.');
                }
            });
            window.filter = Array.prototype.filter;
        </script>
        %JQUERY%
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/scriptaculous/prototype.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/scriptaculous/scriptaculous.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/livepipe/livepipe.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/livepipe/window.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/livepipe/selectmultiplemod.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/common.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/selectmenu.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/calendar/config/xc2_default.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/calendar/script/xc2_inpage.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/js/libs/bootstrap.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/js/libs/modernizr-2.5.3.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/js/libs/jquery.matchHeight.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/bookmark.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/communities_sidebar.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/disclaimers.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        %HEAD%
    </head>
    <body>
        <header id="main-header">
            <div class="banner">
                <div class="container">
                    <div class="row-fluid">
                        <div class="span4">
                            <a class="brand" href="<?php echo ENTRADA_URL; ?>"><img src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/logo.png" width="211" height="33" alt="<?php echo APPLICATION_NAME; ?>" title="<?php echo APPLICATION_NAME; ?>"/></a>
                        </div>
                        <?php
                        if ((isset($_SESSION["isAuthorized"])) && ($_SESSION["isAuthorized"])) {

                            $assessment_tasks_count = 0;

                            $entrada_actor = array(
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            );
                            $subject = array(
                                "subject_id" => $entrada_actor["actor_proxy_id"],
                                "subject_type" => "proxy_id",
                                "subject_scope" => "internal"
                            );

                            $assessment_tasks = new Entrada_Assessments_Tasks($entrada_actor);
                            /**
                             * Query for the non-complete tasks
                             */
                            $pending_tasks_lists = array("assessor-pending");
                            $pending_tasks = $assessment_tasks->fetchAssessmentTaskList($pending_tasks_lists, $entrada_actor["actor_proxy_id"]);
                            $assessment_tasks_count = $assessment_tasks->fetchAssessmentTaskListCount($pending_tasks_lists, $entrada_actor["actor_proxy_id"], "proxy_id", "internal", true);
                            ?>
                            <div class="span8 pull-right">
                                <div class="welcome-area">
                                    <div class="welcome-block">
                                        <a href="<?php echo ENTRADA_RELATIVE; ?>/profile">
                                            <div class="userAvatar">
                                                <?php echo "<img src=\"".webservice_url("photo", array($ENTRADA_USER->getID(), (isset($uploaded_file_active) && $uploaded_file_active ? "upload" : (!file_exists(STORAGE_USER_PHOTOS."/".$ENTRADA_USER->getID()."-official") && file_exists(STORAGE_USER_PHOTOS."/".$ENTRADA_USER->getID()."-upload") ? "upload" : "official"))))."\" alt=\"".html_encode($_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"])."\" class=\"img-polaroid\" />"; ?>
                                                <span class="fa fa-user header-icon"></span>
                                            </div>
                                            <?php echo $ENTRADA_USER->getFirstname() . " " . $ENTRADA_USER->getLastname(); ?>
                                        </a>
                                        <a href="<?php echo ENTRADA_RELATIVE; ?>/assessments">
                                            <span class="fa fa-list-ul header-icon"></span>
                                            <?php
                                            echo $translate->_("Assessment &amp; Evaluation");
                                            if ($assessment_tasks_count > 0) {
                                                echo "<span class=\"space-left badge badge-success\">" . $assessment_tasks_count . "</span>";
                                            } ?>
                                        </a>
                                        <a href="<?php echo ENTRADA_RELATIVE; ?>/?action=logout" class="log-out"><span class="fa fa-power-off"></span> Logout</a>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>

            <?php
            if ((isset($_SESSION["isAuthorized"])) && ($_SESSION["isAuthorized"])) {
                ?>
                <div class="navbar">
                    <div class="navbar-inner">
                        <div class="container no-printing">
                            <?php echo navigator_tabs(); ?>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </header>
        <div class="container" id="page">
            <div class="row-fluid">
                <?php
                if ((isset($_SESSION["isAuthorized"])) && ($_SESSION["isAuthorized"])) {
                    ?>
                    <div class="span3 no-printing" id="sidebar">%SIDEBAR%</div>
                    <div class="span9" id="content">
                    <?php
                } else {
                    ?>
                    <div class="span12" id="content">
                    <?php
                }
                ?>
                <div class="clearfix inner-content">
                    <div class="clearfix">%BREADCRUMB%</div>
