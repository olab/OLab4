<!doctype html>
<html class="no-js" lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta charset="<?php echo DEFAULT_CHARSET; ?>" />

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
        
        <link href="<?php echo ENTRADA_RELATIVE; ?>/w3c/p3p.xml" rel="P3Pv1" type="text/xml" />

        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/jquery/jquery-ui.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" type="text/css" />

        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/jquery/jquery.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/jquery/jquery-ui.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript">jQuery.noConflict();</script>
        <script type="text/javascript">var ENTRADA_URL = '<?php echo ENTRADA_URL; ?>'; var ENTRADA_RELATIVE = '<?php echo ENTRADA_RELATIVE; ?>'; var TEMPLATE_URL = '<?php echo $ENTRADA_TEMPLATE->url(); ?>'; var TEMPLATE_RELATIVE = '<?php echo $ENTRADA_TEMPLATE->relative(); ?>';</script>
        %JQUERY%

        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/scriptaculous/prototype.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/scriptaculous/scriptaculous.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/livepipe/livepipe.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/livepipe/window.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/livepipe/selectmultiplemod.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/common.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo ENTRADA_RELATIVE; ?>/javascript/selectmenu.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>

        <script type="text/javascript" src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/js/libs/bootstrap.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type="text/javascript" src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/js/libs/modernizr-2.5.3.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        %HEAD%
    </head>
    <body id="<?php echo ($PATH_SEPARATED[1] === "secure") ? "secure" : $MODULE; ?>">
        <header id="main-header">
            <div class="banner">
                <div class="container">
                    <div class="row-fluid">
                        <div class="span5">
                            <h1><img src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/logo_sm.png" alt="<?php echo APPLICATION_NAME; ?>" title="<?php echo APPLICATION_NAME; ?>"/></h1>
                        </div>
                        <div class="span2 offset5">
                            <ul class="nav nav-pills">
                                <?php if (!in_array($_GET["section"], array("attempt", "feedback", "confirmation"))){ ?><li><a href="<?php echo ENTRADA_RELATIVE; ?>/secure" class="home" title="Secure Home"><i class="icon-home icon-white"></i></a></li><?php } ?>
                                <li class="last"><a href="<?php echo ENTRADA_RELATIVE; ?>/secure/secure-logout" class="log-out" title="Secure Logout">Logout <i class="fa fa-sign-out"></i></a></li>
                            </ul>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                </div>
            </div>
        </header>
        <div class="container" id="page">
            <div class="row-fluid">
                <div class="span12" id="content">
                    <div class="clearfix inner-content">
                        <div class="clearfix"></div>