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
<header>
	<div class="container-fluid">
		<div class="row-fluid">
			<div class="span12">
				<div class="table">
					<div class="table-cell">
						<h1>404 Community Not Found</h1>
					</div>
				</div>
			</div>
		</div>
	</div>
</header>

<div class="breadcrumb-wrap">
	<div class="container-fluid">
		<div class="row-fluid">
			&nbsp;
		</div>
	</div>
</div>

<div class="content-wrap">
	<div class="container-fluid">
		<div class="row-fluid">
			<div id="main" class="span8" role="main">
				<div class="row-fluid clearfix">
					<section class="span12">
						<section>
							{$page_content}
						</section>
					</section>
				</div>
			</div>
		</div>
	</div>
</div>

<footer>
	<div class="container-fluid">
		<div class="row-fluid">
			<div class="span12">
				<p>{$copyright_string}</p>
			</div>
		</div>
	</div>
</footer>
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
</body>
</html>