                </div>
            </div>
        </div>
    </div>
	<footer id="main-footer">
		<div class="no-printing container">
			<span class="copyright">%LASTUPDATED%<?php echo COPYRIGHT_STRING; ?></span>
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
</body>
</html>