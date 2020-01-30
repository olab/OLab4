                </div>
            </div>
        </div>
    </div>
	<footer id="main-footer">
		<div class="no-printing container">
			<span class="copyright">%LASTUPDATED%<?php echo COPYRIGHT_STRING; ?></span> <a href="<?php echo ENTRADA_URL; ?>/privacy_policy" class="copyright">Privacy Policy</a>.
			<?php
			$time_end = getmicrotime();
			if (SHOW_LOAD_STATS) {
				echo "<br /><span class=\"copyright\">Rendered and loaded page in ".round(($time_end - $time_start), 4)." seconds.</span>\n";
			}
			?>
		</div>
	</footer>
    <?php
    if (((!defined("DEVELOPMENT_MODE")) || (!(bool) DEVELOPMENT_MODE)) && (defined("GOOGLE_ANALYTICS_CODE")) && (GOOGLE_ANALYTICS_CODE != "")) {
        ?>
        <script type="text/javascript">
        var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
        document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
        </script>
        <script type="text/javascript">
        var pageTracker = _gat._getTracker("<?php echo GOOGLE_ANALYTICS_CODE; ?>");
        pageTracker._initData();
        pageTracker._trackPageview();
        </script>
        <?php
    }
    ?>
    <?php
    if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) :
        $maxlifetime = ini_get("session.gc_maxlifetime");
        $disable_timeout = false;
        if ($disable_timeout_setting = $ENTRADA_SETTINGS->fetchByShortname("disable_timeout_monitor", $ENTRADA_USER->getActiveOrganisation())) {
            $disable_timeout = (int)$disable_timeout_setting->getValue() ? true : false;
        }
        if (!$disable_timeout): ?>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/jquery/jquery.session.timeout.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script type = "text/javascript" >
            jQuery(document).ready(function($) {
                $.timeoutMonitor({
                    sessionTime: <?php echo ($maxlifetime -1) * 1000; ?>,
                    warnTime: 180000,    // 3 minutes before it expires
                    title: '<?php echo $translate->_("Your session will expire."); ?>',
                    message: '<?php echo $translate->_("Your session will expire in %%timeleft%%. Any information entered will be lost.<br /><br />Do you want to extend your session?");?>',
                    keepAliveURL: '<?php echo ENTRADA_RELATIVE; ?>/index.php',
                    logoutURL: '<?php echo ENTRADA_RELATIVE; ?>/?action=logout'
                });
            });
        </script >
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>