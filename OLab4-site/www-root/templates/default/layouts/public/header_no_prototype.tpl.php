<!doctype html>
<html class="no-js" lang="en">
<head>
        <meta charset="<?php echo DEFAULT_CHARSET; ?>" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta http-equiv="X-Frame-Options" content="SAMEORIGIN" />
        <title>%TITLE%</title>

        <meta name="description" content="%DESCRIPTION%" />
        <meta name="keywords" content="%KEYWORDS%" />

        <meta name="robots" content="index, follow" />

        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/bootstrap.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/print.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="print" />
        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/style.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/font-awesome/css/font-awesome.min.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" media="all" />

        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
        <link rel="apple-touch-icon" href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/touch-icon-iphone.png"/>
        <link rel="apple-touch-icon" href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/touch-icon-ipad.png" sizes="76x76"/>
        <link rel="apple-touch-icon" href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/touch-icon-iphone-retina.png" sizes="120x120"/>
        <link rel="apple-touch-icon" href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/touch-icon-ipad-retina.png" sizes="152x152"/>

        <script>
            if (self !== top) {
                top.location = self.location;
            }
        </script>
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/jquery/jquery-ui.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" />

        <script type="text/javascript">
            %JAVASCRIPT_TRANSLATIONS%
        </script>
        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/jquery/jquery.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/jquery/jquery-ui.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript">jQuery.noConflict();</script>
        <script type="text/javascript">var ENTRADA_URL = '<?php echo ENTRADA_URL; ?>'; var ENTRADA_RELATIVE = '<?php echo ENTRADA_RELATIVE; ?>'; var TEMPLATE_URL = '<?php echo $ENTRADA_TEMPLATE->url(); ?>'; var TEMPLATE_RELATIVE = '<?php echo $ENTRADA_TEMPLATE->relative(); ?>';</script>
        %JQUERY%

        <script type="text/javascript" src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/js/libs/bootstrap.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/js/libs/modernizr-2.5.3.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        %HEAD%
    </head>
    <body>
        <header id="main-header">
            <div class="banner">
                <div class="container">
                    <div class="row-fluid">
                        <div class="span5">
                            <h1><a href="<?php echo ENTRADA_URL; ?>"><img src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/logo.png" alt="<?php echo APPLICATION_NAME; ?>" title="<?php echo APPLICATION_NAME; ?>"/></a></h1>
                        </div>
                        <?php
                        if ((isset($_SESSION["isAuthorized"])) && ($_SESSION["isAuthorized"])) {
                            ?>
                            <div class="span5">
                                <div class="welcome-area">
                                    <div class="userAvatar">
                                        <?php
                                        $offical_file_active	= false;
                                        $uploaded_file_active	= false;

                                        if ((@file_exists(STORAGE_USER_PHOTOS."/".$ENTRADA_USER->getID()."-official")) && ($ENTRADA_ACL->amIAllowed(new PhotoResource($ENTRADA_USER->getID(), (int) $user_record["privacy_level"], "official"), "read"))) {
                                            $offical_file_active	= true;
                                        }

                                        $query = "SELECT * FROM `".AUTH_DATABASE."`.`user_photos` WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
                                        $uploaded_photo = $db->GetRow($query);

                                        if ((@file_exists(STORAGE_USER_PHOTOS."/".$ENTRADA_USER->getID()."-upload")) && ($uploaded_photo['photo_active']) && ($ENTRADA_ACL->amIAllowed(new PhotoResource($ENTRADA_USER->getID(), (int) $user_record["privacy_level"], "upload"), "read"))) {
                                            $uploaded_file_active = true;
                                        }

                                        $photo_type = "";

                                        if ($uploaded_file_active) {
                                            $photo_type = "upload";
                                        } elseif ($offical_file_active) {
                                            $photo_type = "official";
                                        }
                        
                                        ?>
                                        <a href="#"><img src="<?php echo webservice_url("photo", array($PROXY_ID, $photo_type))."/"; ?>" class="img-polaroid profile_photo_welcome_area" alt=" <?php echo html_encode($_SESSION["details"]["firstname"] . " " . $_SESSION["details"]["lastname"]);?>" /></a>
                                    </div>
                                    <div class="welcome-block">
                                        Welcome <span class="userName"><?php echo $ENTRADA_USER->getFirstname() . " " . $ENTRADA_USER->getLastname(); ?></span>
                                        <br />
                                        <a href="<?php echo ENTRADA_RELATIVE; ?>/profile">My Profile</a> |
                                        <a href="<?php echo ENTRADA_RELATIVE; ?>/evaluations">My Evaluations</a>
                                        <?php
                                        /**
                                         * Cache any outstanding evaluations.
                                         */
                                        if (!isset($ENTRADA_CACHE) || !$ENTRADA_CACHE->test("evaluations_outstanding_"  . AUTH_APP_ID . "_" . $ENTRADA_USER->getID())) {
                                            $evaluations_outstanding = Classes_Evaluation::getOutstandingEvaluations($ENTRADA_USER->getID(), $ENTRADA_USER->getActiveOrganisation(), true);

                                            if (isset($ENTRADA_CACHE)) {
                                                $ENTRADA_CACHE->save($evaluations_outstanding, "evaluations_outstanding_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
                                            }
                                        } else {
                                            $evaluations_outstanding = $ENTRADA_CACHE->load("evaluations_outstanding_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
                                        }

                                        if ($evaluations_outstanding) {
                                            echo "<span class=\"badge badge-success\"><small>".$evaluations_outstanding."</small></span>";
                                        }
                                        ?> |
                                        <a href="<?php echo ENTRADA_RELATIVE."/community".($ENTRADA_USER->getActiveGroup() === "student" ? "/gryphonguides" : "/gryphonguidesforfacu"); ?>:gryphon_guides">Help</a>
                                    </div>
                                </div>
                            </div>
                            <div class="span2">
                                <a href="<?php echo ENTRADA_RELATIVE; ?>/?action=logout" class="log-out">Logout <i class="fa fa-sign-out"></i></a>
                            </div>
                            <div class="clearfix"></div>
                            <?php
                        } else {
                        ?>
                        <div class="span7 pull-right">
                            <a href="<?php echo LOGIN_SECONDARY_URL; ?>" target="_blank"><img class="pull-right" src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/logo-secondary.png" alt="DGSOM" title="DGSOM"/></a>
                        </div>
                        <div class="clearfix"></div>
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
                <div class="span12" id="content">
                    <div class="clearfix inner-content">
                        <div class="clearfix">%BREADCRUMB%</div>