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
 * Primary controller file for the public Regional Education module.
 * /regionaled
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */


/**
 * Entrada Twitter
 *
 * This class is used to display the latest tweets. The widget can be added using
 * the method ::render(num_of_tweets, feed_type, item_id); where num_of_tweets is
 * the number of tweets to show, feed_type can be either 'course' or 'community' and
 * item_id is the ID of the course/community
 *
 * @author Organization: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Your Organization Here. All Rights Reserved.
 */
class Entrada_Twitter {
    protected $hashtags;
    protected $handles;

    public function __construct($arr = NULL) {
        $this->hashtags = array();
        $this->handles  = array();
    }

    /**
     * Add the hashtags and/or handle for each course associated with the current user
     */
    private function getUserCoursesTwitterDetails() {
        /**
         * Get the current curriculum period
         */
        $cperiod = new Models_Curriculum_Period();
        $current_curriculum_periods = $cperiod->fetchAllCurrent();

        if ($current_curriculum_periods) {
            $cperiods = array();
            foreach ($current_curriculum_periods as $curriculum_period) {
                $cperiods[] = $curriculum_period->getID();
            }
        }

        /**
         * Get the list of courses for the current period along with their twitter details.
         */
        $audience = new Models_Course_Audience();
        $results = $audience->fetchCoursesByCurriculumPeriod($cperiods);

        /**
         * Go through the result set and update the handles and hashtags variable
         */
        if ($results) {
            foreach ($results as $result) {
                if ($result["handle"] != "") {
                    $this->handles[] = $result["handle"];
                }

                if ($result["hashtags"] != "") {
                    $this->hashtags = array_merge($this->hashtags, explode(" ", $result["hashtags"]));
                }
            }
        }
    }

    /**
     * Add the current user organisation's twitter handle and/or hashtags to the
     * private variables hashtags and handles.
     */
    private function getActiveOrganisationTwitterDetails() {
        global $ENTRADA_USER;

        $organisations = new Models_Organisation();
        $organisation = $organisations->fetchRowByID($ENTRADA_USER->getActiveOrganisation());

        $handle = $organisation->getOrganisationTwitterHandle();
        if ( $handle != "" ) {
            $this->handles[] = $handle;
        }

        $hashtags = $organisation->getOrganisationTwitterHashtags();
        if ( $hashtags != "" ) {
            $this->hashtags[] = $hashtags;
        }
    }

    /**
     * Add the twitter hashtags and/or handle configured for the communities
     * that the current user is a member of.
     */
    private function getUserCommunitiesTwitterDetails() {
        $community = new Models_Community();
        $results = $community->getCurrentUserCommunitiesTwitterDetails();

        /**
         * Go through the result set and update the handles and hashtags variable
         */
        if ($results) {
            foreach ($results as $result) {
                if ($result["handle"] != "") {
                    $this->handles[] = $result["handle"];
                }

                if ($result["hashtags"] != "") {
                    $this->hashtags = array_merge($this->hashtags, explode(" ", $result["hashtags"]));
                }
            }
        }
    }


    /**
     * Find out what twitter handle and/or hastags to be displayed based on the current
     * page either: Course, Community or other.
     *
     * @param $feed_type
     * @param $item_id
     * @return bool
     */
    private function fetchHandlesAndHastags($feed_type, $item_id) {
        if (($feed_type == "course")) {
            if ((int) $item_id) {
                /**
                 * Get handle and hashtags for the current course only
                 */
                $courses = new Models_Course;
                $course = $courses->get($item_id);

                $this->handles = explode(" ", $course->getTwitterHandle());
                $this->hashtags = explode(" ", $course->getTwitterHashTags());
            } else {
                $this->getUserCoursesTwitterDetails();
            }
        } else if (($feed_type == "community")) {
            if ((int) $item_id) {
                /**
                 * Get handle and hashtags for the current community only
                 */
                $communities = new Models_Community();
                $community = $communities->fetchRowByID($item_id);

                $this->handles = explode(" ", $community->getTwitterHandle());
                $this->hashtags = explode(" ", $community->getTwitterHashTags());
            } else {
                $this->getUserCommunitiesTwitterDetails();
            }
        } else {
            /**
             * Get handles and hashtags for all organisations, courses
             * and communities the user is associated to
             */
            $this->getActiveOrganisationTwitterDetails();
            $this->getUserCoursesTwitterDetails();
            $this->getUserCommunitiesTwitterDetails();
        }

        /**
         * Remove Duplicates
         */
        if (is_array($this->handles) && count($this->handles)) {
            $this->handles = array_unique($this->handles);
        }
        if (is_array($this->hashtags) && count($this->hashtags)) {
            $this->hashtags = array_unique($this->hashtags);
        }

        return true;
    }

    /**
     * This method gets the latest tweets from the cache files based on the private
     * variables hashtags and handles set by the fetchHandlesAndHastags method
     *
     * @return array|bool
     */
    private function getTwitterFeed($feed_type="", $item_id=0) {
        if (!$this->fetchHandlesAndHastags($feed_type, $item_id)) {
            return false;
        }

        $tweets = array();

        /**
         * Do we have tags or handle to show ?
         */
        if (!count($this->handles) && !count($this->hashtags)) {
            return false;
        }
        $model_tweets = new Models_Tweets();

        /**
         * Get the tweets for the handles if we have a cache
         */
        if (count($this->handles) && !empty($this->handles[0])) {
            /**
             * Get and parse the cached feed
             */

            foreach ($this->handles as $handle) {
                $handle_tweets = $model_tweets->fetchHandleLatestTwitterFeed($handle);
                $json = unserialize(base64_decode($handle_tweets));
                if (isset($json) && is_array($json)) {
                    foreach ($json as $tweet) {
                        $tweets[] = array(
                            "date"      => $tweet->created_at,
                            "timestamp" => strtotime(($tweet->created_at)),
                            "id"        => $tweet->id,
                            "created_at" => date("g:i A - j M Y", strtotime($tweet->created_at)),
                            "text"      => $tweet->text,
                            "user"      => $tweet->user->screen_name,
                            "user_img" => $tweet->user->profile_image_url,
                            "user_img_ssl" => $tweet->user->profile_image_url_https,
                            "uid"       => $tweet->user->id,
                            "hashtag"   => "",
                            "hashtags"  => $tweet->entities->hashtags,
                            "user_mentions" => $tweet->entities->user_mentions
                        );
                    }
                    unset($json);
                }
                unset($handle_tweets);
            }
        }

        /**
         * Get the tweets for the hashtags if we have a cache
         */
        if (count($this->hashtags) && !empty($this->hashtags[0])) {
            /**
             * Get and parse the cached feed
             */
            foreach ($this->hashtags as $hashtag) {
                $twitter = new Models_Tweets();

                $hashtag_tweets = $twitter->fetchHashtagsLatestTwitterFeed($hashtag);
                $json = unserialize(base64_decode($hashtag_tweets));
                if (isset($json->statuses) && is_array($json->statuses)) {
                    foreach ($json->statuses as $tweet) {
                        $tweets[] = array(
                            "date" => $tweet->created_at,
                            "timestamp" => strtotime(($tweet->created_at)),
                            "id" => $tweet->id,
                            "created_at" => date("g:i A - j M Y", strtotime($tweet->created_at)),
                            "text" => $tweet->text,
                            "user" => $tweet->user->screen_name,
                            "user_img" => $tweet->user->profile_image_url,
                            "user_img_ssl" => $tweet->user->profile_image_url_https,
                            "user_url" => $tweet->user->url,
                            "uid" => $tweet->user->id,
                            "hashtags" => $tweet->entities->hashtags,
                            "hashtag" => $hashtag,
                            "user_mentions" => $tweet->entities->user_mentions
                        );
                    }
                    unset($json);
                }
                unset($hashtag_tweets);
            }
        }


        /**
         * Sort the tweets by date
         */
        usort($tweets, function($a, $b) {
            return $b["timestamp"] - $a["timestamp"];
        });

        return $tweets;
    }

     /**
     * This method return formatted tweets adding appropriate links to
     * all the handles, hash tags and URLs.
     *
     * @param $tweet_text
     * @return mixed|string
     */
    private function formatTweet($tweet_text) {
        /**
         * Any processing of the tweet text can be done here before adding to the widget. Currently, nothing to do.
         */
        return $tweet_text;
    }

    /**
     * This method display the latest tweets return by the getTwitterFeeds method.
     * Adjust the max number of tweets to show using the count variable.
     *
     * @param int $count
     * @param string $feed_type
     * @param int $item_id
     * @return bool|string
     */
    public function render($count=4, $feed_type="", $item_id=0, $api=false, $offset=0) {
        $tweets = $this->getTwitterFeed($feed_type, $item_id);
        if (!$tweets) {
            return false;
        }

        $is_ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? true : false;

        $twitter_update_interval     = ((int) Entrada_Settings::fetchValueByShortname("twitter_update_interval")) ? ((int) Entrada_Settings::fetchValueByShortname("twitter_update_interval")) : 0;
        $output = array();

        /**
         * Output javascript to refresh the feed
         */
        if (!$api) {
            $div_id = uniqid("twitter-content-");

            if ($twitter_update_interval) {
                /**
                 * Add script to refresh the tweets at the same rate than the update interval
                 */
                $api_url = ENTRADA_URL . "/api/twitter.api.php";

                $output[] = "<script type=\"text/javascript\">";
                $output[] = "    jQuery(document).ready(function($){";
                $output[] = "        var count=".$count."; var offset=".$count.";";
                $output[] = "        setInterval(function () {";
                $output[] = "           $.ajax({";
                $output[] = "               type: 'GET',";
                $output[] = "               url: '". $api_url . "',";
                $output[] = "               data: { 'c':count, 't':'".$feed_type."', 'id':".$item_id ." }, ";
                $output[] = "               success: function(data) {";
                $output[] = "                   $('#" . $div_id . "').hide().html(data).fadeIn(1000);";
                $output[] = "               }";
                $output[] = "           });";
                $output[] = "        }, ".($twitter_update_interval * 60 * 1000).");";
                $output[] = "        $(\"#twitter-load-more\").click(function(){";
                $output[] = "           offset = count; count += " . $count;
                $output[] = "           $(\"#".$div_id."\").parent(\".panel-body\").height($(\"#".$div_id."\").parent(\".panel-body\").height() + 1);";
                $output[] = "           $(\"#".$div_id."\").parent(\".panel-body\").css('overflow-y', 'scroll');";
                $output[] = "           $(\"#".$div_id."\").parent(\".panel-body\").css('padding-right', '2px');";
                $output[] = "           $(\"#twitter-loading-spinner\").toggle(); $(\"#div-twitter-load-more\").toggle();";
                $output[] = "           $.ajax({";
                $output[] = "               type: 'GET',";
                $output[] = "               url: '". $api_url . "',";
                $output[] = "               data: { 'c':count, 't':'".$feed_type."', 'id':".$item_id .", 'o':offset }, ";
                $output[] = "               success: function(data) {";
                $output[] = "                   if (data != '') {";
                $output[] = "                       $('#" . $div_id . "').append(data).fadeIn(1000);";
                $output[] = "                       $(\"#div-twitter-load-more\").toggle(); $(\"#twitter-loading-spinner\").toggle();";
                $output[] = "                   } else { ";
                $output[] = "                       $(\"#twitter-no-more-tweets\").toggle();$(\"#twitter-loading-spinner\").toggle();";
                $output[] = "                   }";
                $output[] = "               }";
                $output[] = "           });";
                $output[] = "        });";
                $output[] = "        $(\"#" . $div_id . "\").on(\"click\", \".panel-link\", function() {";
                $output[] = "           window.location = $(this).find(\".tweet-link\").attr(\"href\");";
                $output[] = "           return false;";
                $output[] = "        });";
                $output[] = "    });";
                $output[] = "</script>";
            }

            $output[] = "<div id=\"" . $div_id . "\" class=\"twitter-widget-container\">";
        }

        for ($i=0 ; $i<$count ; $i++) {
            if ($offset > $i) {
                continue;
            }
            if (!isset($tweets[$i])) {
                break;
            }
            $tweet_text = $this->formatTweet($tweets[$i]["text"]);

            $output[] = "<a href=\"https://twitter.com/" . $tweets[$i]["user"] . "/status/" .$tweets[$i]["id"] . "\" class=\"tweet-link list-group-item\">";
            $output[] = "    <div class=\"table\">";
            $output[] = "        <div class=\"table-cell\">";
            $output[] = "            <span class=\"circle circle-img-sm\">";
            $output[] = "                <img src=\"" . ($is_ssl ? $tweets[$i]["user_img_ssl"] : $tweets[$i]["user_img"]) . "\" alt=\"Twitter user profile picture\"> <!-- Twitter profile picture -->";
            $output[] = "            </span>";
            $output[] = "        </div>";
            $output[] = "        <div class=\"table-cell-lg\">";
            $output[] = "            <p class=\"tweet-user\">@" . $tweets[$i]["user"] . "</p>  <!-- Twitter username for tweet -->";
            $output[] = "        </div>";
            $output[] = "    </div>";
            $output[] = "    <div class=\"tweet-content\">";
            $output[] = "        <p>" . $tweet_text . "</p>  <!-- Tweet content including links -->";
            $output[] = "    </div>";
            $output[] = "    <p class=\"subheading tweet-date\">" . $tweets[$i]["created_at"] . "</p>  <!-- Date of tweet -->";
            $output[] = "</a> <!-- Link to the tweet on Twitter -->";
        }

        if (!$api) {
            $output[] = "</div>";
            $output[] = "<div id=\"div-twitter-load-more\">";
            $output[] = "    <a id=\"twitter-load-more\" href=\"javascript://\"> <!-- Load more tweets -->";
            $output[] = "        <div class=\"panel-footer\">";
            $output[] = "           Load More";
            $output[] = "        </div>";
            $output[] = "    </a>";
            $output[] = "</div>";
            $output[] = "<div id=\"twitter-loading-spinner\" style=\"visible: false; display: none;\">";
            $output[] = "    <img src=\"/images/loading.gif\">";
            $output[] = "</div>";
            $output[] = "<div id=\"twitter-no-more-tweets\" style=\"visible: false; display: none;\">";
            $output[] = "    <p>There are no more tweets to load</p>";
            $output[] = "</div>";
        }
        
        return implode("\n", $output);
    }
    
    public static function widgetIsActive() {
        return (Entrada_Settings::fetchValueByShortname("twitter_consumer_key") &&
            Entrada_Settings::fetchValueByShortname("twitter_consumer_secret") &&
            Entrada_Settings::fetchValueByShortname("twitter_sort_order") &&
            Entrada_Settings::fetchValueByShortname("twitter_update_interval"));
    }
}