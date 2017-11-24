<!doctype html>
	<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
	<!--[if lt IE 7 ]> <html class="no-js ie6" lang="en"> <![endif]-->
	<!--[if IE 7 ]>    <html class="no-js ie7" lang="en"> <![endif]-->
	<!--[if IE 8 ]>    <html class="no-js ie8" lang="en"> <![endif]-->
	<!--[if (gte IE 9)|!(IE)]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
	<head>
		<meta charset="{$site_default_charset}">

		<title>{$page_title}</title>
		<meta name="description" content="{$page_description}" />
		<meta name="keywords" content="{$page_keywords}" />

		<meta name="robots" content="index, follow" />

        <link rel="stylesheet" href="{$protocol}://fonts.googleapis.com/css?family=Roboto:400,400italic,700,700italic,300,300italic">
		<link rel="stylesheet" href="{$sys_website_relative}/css/font-awesome/css/font-awesome.min.css">

	    <link rel="stylesheet" href="{$sys_website_relative}/css/jquery/jquery-ui.css">
        <link rel="stylesheet" href="{$template_relative}/css/bootstrap.min.css">
        <link rel="stylesheet" href="{$template_relative}/css/stylesheet.css">

		<script src="{$sys_website_relative}/javascript/jquery/jquery.min.js"></script>
		<script src="{$sys_website_relative}/javascript/jquery/jquery-ui.min.js"></script>

	    <script>var COMMUNITY_ID = "{$community_id}";</script>
		<script>jQuery.noConflict();</script>

        <script src="{$template_relative}/js/bootstrap.min.js"></script>
        <script src="{$template_relative}/js/modernizr-1.7.min.js"></script>
        <script src="{$template_relative}/js/common.js"></script>

		{$page_head}
	</head>
	<body>
		<div class="container">
		    <header class="span12 page-header">
				<div class="page-header-title span10">
					<h3 class="module-name" >{$site_community_title}</h3>
				</div>
		    </header>

		    {include file="navigation_primary.tpl" site_primary_navigation=$site_primary_navigation}

			<nav class="breadcrumb span12">
			    {$site_breadcrumb_trail}
				<div id="community-nav-collapse">
	                <a id="community-nav-collapse-toggle" href="#"><span class="menu-icon" id="community-nav-menu-icon" title="Administrative Navigation"></span></a>
	            </div>
			</nav>
		    <div id="main" role="main">
				<div class="row clearfix">
					{if $show_tertiary_sideblock}
						<aside class="span3 pull-left">
							{include file="sidebar-blocks/tertiary_block.tpl"}
						</aside>
						<section class="span6 sideblock-content pull-left">
							<section>
								{$page_content}
							</section>
                            {if $is_sequential_nav}
                                {include file="sequential_nav.tpl"}
                            {/if}
						</section>
					{else}
						<section class="span9 content pull-left">
							<section>
								{$page_content}
							</section>
                            {if $is_sequential_nav}
                                {include file="sequential_nav.tpl"}
                            {/if}
						</section>
					{/if}
					<aside id="right-community-nav" class="span3 right-community-nav-expanded pull-right">
						{if $is_logged_in && $user_is_admin}
							{include file="sidebar-blocks/admin_block.tpl"}
						{/if}
						{include file="sidebar-blocks/entrada_block.tpl"}
						{if $twitter}
							<section>
								<div class="panel-header">
									<h1>{translate}Twitter{/translate}</h1>
								</div>
								<div class="panel-content">
									{$twitter}
								</div>
							</section>
						{/if}
						{if $is_logged_in && $user_is_member}
							{include file="sidebar-blocks/community_block.tpl"}
						{/if}
		                {if $allow_membership}
		                    {include file="sidebar-blocks/community_join_block.tpl"}
		                {/if}
					</aside>
				</div>
		    </div>
		    <footer class="span12">
				<p>{$copyright_string}</p>
		    </footer>
	  	</div>
        {if !$development_mode && $google_analytics_code}
            <script type="text/javascript">
                var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
                document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
            </script>
            <script type="text/javascript">
                var pageTracker = _gat._getTracker("{$google_analytics_code}");
                pageTracker._initData();
                pageTracker._trackPageview();
            </script>
        {/if}
		{if $isAuthorized}
			<script src = "{$sys_website_url}/javascript/jquery/jquery.session.timeout.js?release={$application_version}" ></script >
			<script type = "text/javascript" >
				jQuery(document) . ready(function ($) {
					$.timeoutMonitor({
						sessionTime: {$maxlifetime},
						warnTime: 60000,    // 60 seconds before it expires
						title: '{$session_expire_title}',
						message: '{$session_expire_message}'
					});
				});
			</script >
		{/if}
	</body>
</html>