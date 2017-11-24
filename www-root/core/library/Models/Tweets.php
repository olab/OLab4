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
 * A model to handle the different Twitter feeds for the handles and hashtags configured for each organisations, courses and communities.
 *
 * @author Organisation: 
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 . All Rights Reserved.
 */

class Models_Tweets extends Models_Base {
    protected   $id,
                $last_update,
                $tweets_handle,
                $tweets_hashtag,
                $tweets,
                $twitter_consumer_key,
                $twitter_consumer_secret,
                $twitter_sort_order,
                $twitter_language,
                $twitter_update_interval,
                $bearer_token;

    protected static $table_name = "tweets";
    protected static $primary_key = "id";
    protected static $default_sort_column = "tweets_handle";

    public function __construct($arr = NULL) {
        parent::__construct($arr);

        /**
         * Check for authentication settings, and if not set then exit
         */
        $this->twitter_consumer_key        = Entrada_Settings::fetchValueByShortname("twitter_consumer_key");
        $this->twitter_consumer_secret     = Entrada_Settings::fetchValueByShortname("twitter_consumer_secret");
        $this->twitter_sort_order          = Entrada_Settings::fetchValueByShortname("twitter_sort_order") ? Entrada_Settings::fetchValueByShortname("twitter_sort_order") : "popular";
        $this->twitter_language            = Entrada_Settings::fetchValueByShortname("twitter_language") ? Entrada_Settings::fetchValueByShortname("twitter_language") : "en";
        $this->twitter_update_interval     = ((int) Entrada_Settings::fetchValueByShortname("twitter_update_interval")) ? ((int) Entrada_Settings::fetchValueByShortname("twitter_update_interval")) : 5;

        $this->bearer_token = $this->GetBearerToken();
    }

    public function getID() {
        return $this->id;
    }

    public function getTweetsHandle() {
        return $this->tweets_handle;
    }

    public function getTweetsHashtag() {
        return $this->tweets_hashtag;
    }

    public function getTweets() {
        return $this->tweets;
    }

    public function setTweetsHandle($handle) {
        $this->tweets_handle = $handle;
    }

    public function setTweetsHashtag($hashtag) {
        $this->tweets_hashtag = $hashtag;
    }

    public function setTweets($tweets) {
        $this->tweets = $tweets;
    }

    public function setLastUpdate($last_update) {
        $this->last_update = $last_update;
    }

    public static function fetchRowByID($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "value" => $id, "method" => "=")
        ));
    }

    public static function fetchRowByHashtag($hashtag) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "tweets_hashtag", "value" => $hashtag, "method" => "=")
        ));
    }

    public static function fetchRowByHandle($handle) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "tweets_handle", "value" => $handle, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "id", "value" => 0, "method" => ">=")));
    }

    /**
     * Get the Bearer Token, this is an implementation of steps 1&2
     * from https://dev.twitter.com/docs/auth/application-only-auth
     *
     * @return bool
     */
    private function GetBearerToken() {
        // step 1.1 - url encode the consumer_key and consumer_secret in accordance with RFC 1738
        $encoded_consumer_key = urlencode($this->twitter_consumer_key);
        $encoded_consumer_secret = urlencode($this->twitter_consumer_secret);

        // step 1.2 - concatinate encoded consumer, a colon character and the encoded consumer secret
        $bearer_token = $encoded_consumer_key.':'.$encoded_consumer_secret;

        // step 1.3 - base64-encode bearer token
        $base64_encoded_bearer_token = base64_encode($bearer_token);

        /**
         * step 2: Fetch the token using cURL
         */
        $url = "https://api.twitter.com/oauth2/token"; // url to send data to for authentication
        $headers = array(
            "POST /oauth2/token HTTP/1.1",
            "Host: api.twitter.com",
            "User-Agent: Entrada Twitter Application-only OAuth App v.1",
            "Authorization: Basic ".$base64_encoded_bearer_token,
            "Content-Type: application/x-www-form-urlencoded;charset=UTF-8"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_HEADER, 1); // send custom headers

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $retrievedhtml = curl_exec ($ch); // execute the curl
        curl_close($ch); // close the curl

        $output = explode("\n", $retrievedhtml);
        $bearer_token = '';
        foreach($output as $line) {
            if ($line === false) {
                return false;
            } else {
                $bearer_token = $line;
            }
        }

        $bearer_token = json_decode($bearer_token);
        return $bearer_token->{'access_token'};
    }

    /**
     * Invalidates the Bearer Token should the bearer token become compromised or need
     * to be invalidated for any reason, call this method/function.
     *
     * @param $bearer_token
     * @return mixed
     */
    private function InvalidateBearerToken($bearer_token) {
        $encoded_consumer_key = urlencode($this->twitter_consumer_key);
        $encoded_consumer_secret = urlencode($this->twitter_consumer_secret);
        $consumer_token = $encoded_consumer_key.':'.$encoded_consumer_secret;
        $base64_encoded_consumer_token = base64_encode($consumer_token);

        // step 2
        $url = "https://api.twitter.com/oauth2/invalidate_token"; // url to send data to for authentication
        $headers = array(
            "POST /oauth2/invalidate_token HTTP/1.1",
            "Host: api.twitter.com",
            "User-Agent: Entrada Twitter Application-only OAuth App v.1",
            "Authorization: Basic ".$base64_encoded_consumer_token,
            "Accept: */*",
            "Content-Type: application/x-www-form-urlencoded"
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "access_token=".$this->bearer_token);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $retrievedhtml = curl_exec ($ch);
        curl_close($ch);

        return $retrievedhtml;
    }

    /**
     * Search
     * Basic Search of the Search API
     * Based on https://dev.twitter.com/docs/api/1.1/get/search/tweets
     */
    private function searchTweets($query){
        $url = "https://api.twitter.com/1.1/search/tweets.json"; // base url
        $q = urlencode(trim($query));

        $formed_url ='?q='.$q;

        /**
         *  Result type - mixed(default), recent, popular
         */
        if ($this->twitter_sort_order!='mixed') {
            $formed_url = $formed_url.'&result_type='.$this->twitter_sort_order;
        }

        /**
         * Get the maximum 100 tweets. Allows to handle the "More Tweets" function
         */
        $formed_url = $formed_url.'&count=100';

        /**
         * Language
         */
        if( $this->twitter_language != "" ) {
            $formed_url .= "&lang=".$this->twitter_language;
        }

        $formed_url = $formed_url.'&include_entities=true'; // makes sure the entities are included, note @mentions are not included see documentation

        $headers = array(
            "GET /1.1/search/tweets.json".$formed_url." HTTP/1.1",
            "Host: api.twitter.com",
            "User-Agent: Entrada Twitter Application-only OAuth App v.1",
            "Authorization: Bearer ".$this->bearer_token
        );

        $ch = curl_init();  // setup a curl
        curl_setopt($ch, CURLOPT_URL,$url.$formed_url);  // set url to send to
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // set custom headers
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return output
        $retrievedhtml = curl_exec ($ch); // execute the curl
        curl_close($ch); // close the curl

        return $retrievedhtml;
    }

    /**
     * Search
     * Basic Search of the Search API
     * Based on https://dev.twitter.com/docs/api/1.1/get/search/tweets
     */
    private function getUserTimeline($handle, $count='15'){
        $url = "https://api.twitter.com/1.1/statuses/user_timeline.json"; // base url
        $handle = urlencode(trim($handle));

        $formed_url ='?screen_name='.$handle;

        /**
         * Results per page - defaulted to 15
         * */
        if ($count!='15') {
            $formed_url = $formed_url.'&count='.$count;
        }

        $headers = array(
            "GET /1.1/statuses/user_timeline.json".$formed_url." HTTP/1.1",
            "Host: api.twitter.com",
            "User-Agent: Entrada Twitter Application-only OAuth App v.1",
            "Authorization: Bearer ".$this->bearer_token
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url.$formed_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $retrievedhtml = curl_exec ($ch);
        curl_close($ch);

        return $retrievedhtml;
    }

    /**
     * Retrieve the
     * @return mixed
     */
    private function getRateLimitStatus() {
        $url = "https://api.twitter.com/1.1/application/rate_limit_status.json";

        $headers = array(
            "GET /1.1/application/rate_limit_status.json HTTP/1.1",
            "Host: api.twitter.com",
            "User-Agent: Entrada Twitter Application-only OAuth App v.1",
            "Authorization: Bearer ".$this->bearer_token
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $retrievedhtml = curl_exec ($ch);
        curl_close($ch);

        return $retrievedhtml;
    }

    /**
     * Return the latest twitter time line for a handle
     *
     * @param $handle
     * @return bool/mixed
     */
    public function fetchHandleLatestTwitterFeed($handle) {
        if ($handle == "") {
            return false;
        }

        $twitter_feed = $this->fetchRowByHandle($handle);
        if (!$twitter_feed) {
            $twitter_feed = new self();
            $twitter_feed->setTweetsHandle($handle);
        }

        if ((!isset($twitter_feed->last_update)) || ((time() - strtotime($twitter_feed->last_update)) > ($this->twitter_update_interval * 60))) {
            /**
             * Retrieve the feeds
             */
            $response = json_decode($this->getUserTimeline(ltrim($handle, "@")));

            if (is_array($response) && count($response)) {
                $twitter_feed->setTweets(base64_encode(serialize($response)));

                if ($twitter_feed->getID()) {
                    $twitter_feed->setLastUpdate("");
                    $twitter_feed->update();
               } else {
                    $twitter_feed->insert();
                 }
            }
        }

        return $twitter_feed->tweets;
    }


    /**
     * Fetch latest tweets for a hashtag
     *
     * @param $hashtag
     * @return bool
     */
    public function fetchHashtagsLatestTwitterFeed($hashtag) {
        if ($hashtag == "") {
            return false;
        }

        $twitter_feed = $this->fetchRowByHashtag($hashtag);
        if (!$twitter_feed) {
            $twitter_feed = new self();
            $twitter_feed->setTweetsHashtag($hashtag);
        }

        /**
         * Check if the feed has been updated within the interval set in the settings
         * and fetch the latest version if not.
         */
        if ((!isset($twitter_feed->last_update)) || ((time() - strtotime($twitter_feed->last_update)) > ($this->twitter_update_interval * 60))) {
            /**
             * Retrieve the feeds
             */
            $response  = json_decode($this->searchTweets($hashtag));
            if (isset($response->statuses)) {
                $twitter_feed->setTweets(base64_encode(serialize($response)));

                if ($twitter_feed->getID()) {
                    $twitter_feed->setLastUpdate("");
                    $twitter_feed->update();
                } else {
                    $twitter_feed->insert();
                }
            }
        }

        return $twitter_feed->tweets;
    }
}