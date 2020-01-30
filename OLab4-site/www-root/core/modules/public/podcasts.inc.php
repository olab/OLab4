<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * $Id: podcasts.inc.php 1171 2010-05-01 14:39:27Z ad29 $
 */

if(!defined("PARENT_INCLUDED")) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('podcast', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $settings = new Entrada_Settings();
    if ($settings->read("podcast_display_sidebar")) {
        $sidebar_html = "<div style=\"text-align: center\">\n";
        $sidebar_html .= "	<a href=\"" . str_replace(array("https://", "http://"), "itpc://", ENTRADA_URL) . "/podcasts/feed\"><img src=\"" . ENTRADA_URL . "/images/itunes_podcast_icon.png\" width=\"70\" height=\"70\" alt=\"MEdTech Podcasts\" title=\"Subscribe to our Podcast feed.\" border=\"0\"></a><br />\n";
        $sidebar_html .= "	<a href=\"" . str_replace(array("https://", "http://"), "itpc://", ENTRADA_URL) . "/podcasts/feed\" style=\"display: block; margin-top: 10px; font-size: 14px\">Subscribe Here</a>";
        $sidebar_html .= "</div>\n";
        new_sidebar_item("Podcasts in iTunes", $sidebar_html, "podcast-bar", "open", "1.1");
    }
	?>
    <div style="text-align: left; border-bottom: 1px #d9dee2 solid">
        <a href="<?php echo str_replace(array("https://", "http://"), "itpc://", ENTRADA_URL); ?>/podcasts/feed"><img src="<?php echo ENTRADA_URL; ?>/images/podcast-header-image.png" width="750" height="258" alt="Podcasts in iTunes" title="" border="0" /></a>
    </div>

    <h1>Podcasts Now Available</h1>
    <p>Click the image above to launch iTunes, then enter your <?php echo APPLICATION_NAME; ?> username and password when iTunes prompts you.</p>

    <div class="row-fluid">
        <div class="span6">
            <h2><?php echo APPLICATION_NAME; ?> Podcasts</h2>
            <p class="lead">We provide many of the presented learning events in this system as digitally recorded podcasts available for download through iTunes or as MP3 files in the learning events' resources section.</p>

            <p>Podcast files can be accessed as a manual download from within the Learning Event page, or as an automatic download whenever iTunes is launched (click the button below to configure your iTunes).</p>

            <p class="text-error"><strong>Please note</strong> these podcasts are only accessible by learners and teachers after logging in. It is forbidden to share podcast mp3 files or their derivatives without prior consent from the teachers involved.</p>
        </div>
        <div class="span6">
            <h2>What is Podcasting?</h2>
            <p>Podcasting is a form of audio broadcasting that allows individuals to subscribe to a group of audio files over the Internet and listen to them on their personal computer or portable media player. Although audio files by themselves have been available on the Internet for some time, the ability to subscribe to a feed and have new audio files automatically downloaded for you has made podcasting an extremely powerful and popular medium.</p>

            <p>The term podcasting is closely associated with Apple's iPod, however, it is important to note than an iPod is not required to listen to a podcast. All that is required to listen to a podcast is a podcasting client (such as <a href="http://www.itunes.com">iTunes</a>) and an Internet connection.</p>

            <button class="btn btn-large btn-primary pull-right" onclick="window.location='<?php echo str_replace(array("https://", "http://"), "itpc://", ENTRADA_URL); ?>/podcasts/feed'"><span class="fa fa-play-circle"></span> Launch iTunes</button>
        </div>
    <?php
}