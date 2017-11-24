<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />

	<title>{$page_title}</title>

	<meta name="robots" content="noindex, nofollow" />

	<link href="{$template_relative}/css/stylesheet.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="site-container">
	<div id="site-header">
		<div>
			<img src="{$template_relative}/images/community-icon.gif" width="101" height="130" alt="" style="vertical-align: bottom; margin-right: 10px" />
			<span class="community-title">{$page_title}</span>
		</div>
	</div>
	<div id="site-body">
		<table id="content-table" style="width: 100%; table-layout: fixed" cellspacing="0" cellpadding="0" border="0">
		<colgroup>
			<col style="width: 22%" />
			<col style="width: 58%" />
			<col style="width: 20%" />
		</colgroup>
		<tbody>
			<tr>
				<td class="column">&nbsp;</td>
				<td class="column">
					<div>
						{$page_content}
					</div>
				</td>
				<td class="column">&nbsp;</td>
			</tr>
		</tbody>
		</table>
	</div>
	<div id="site-footer">
		<div style="padding: 10px 5px 15px 22%; text-align: left" class="content-copyright">
			{$copyright_string}
		</div>
	</div>
</div>

{if $development_mode && $google_analytics_code }
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