<?php

/**
 * Open Labyrinth [ http://www.openlabyrinth.ca ]
 *
 * Open Labyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Open Labyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Open Labyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright 2012 Open Labyrinth. All Rights Reserved.
 *
 */

namespace Entrada\Modules\Olab\Classes\h5p;

use Illuminate\Support\Facades\Log;
use \Exception;
use Entrada\Modules\Olab\Classes\CustomAssetManager;
use Entrada\Modules\Olab\Models\H5pContents;
use Entrada\Modules\Olab\Models\H5pContentsUserData;
use Entrada\Modules\Olab\Classes\h5p\H5POpenLabyrinth;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\xAPI\xAPI;
use \H5PFrameworkInterface;
use \H5PCore;

class H5PPlugin
{

    const H5P_URL_ROOT = '/core/library/vendor/h5p/h5p-core';

    /**
     * Plugin version, used for cache-busting of style and script file references.
     * Keeping track of the DB version.
     *
     * @since 1.0.0
     * @var string
     */
    const VERSION = '20190508';

    /**
     * The Unique identifier for this plugin.
     *
     * @since 1.0.0
     * @var string
     */
    protected $plugin_slug = 'h5p';

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var H5PPlugin
     */
    protected static $instance = null;

    /**
     * Instance of H5P WordPress Framework Interface.
     *
     * @since 1.0.0
     * @var H5POpenLabyrinth
     */
    protected static $interface = null;

    /**
     * Instance of H5P Core.
     *
     * @since 1.0.0
     * @var \H5PCore
     */
    protected static $core = null;

    /**
     * JavaScript settings to add for H5Ps.
     *
     * @since 1.0.0
     * @var array
     */
    protected static $settings = null;

    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @since 1.0.0
     */
    private function __construct()
    {

        /*// Load plugin text domain
        add_action('init', array($this, 'load_plugin_textdomain'));

        // Load public-facing style sheet and JavaScript.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles_and_scripts'));

        // Add support for h5p shortcodes.
        add_shortcode('h5p', array($this, 'shortcode'));

        // Adds JavaScript settings to the bottom of the page.
        add_action('wp_footer', array($this, 'add_settings'));

        // Clean up tmp editor files
        add_action('h5p_daily_cleanup', array($this, 'remove_old_tmp_files'));

        // Check for library updates
        add_action('h5p_daily_cleanup', array($this, 'get_library_updates'));

        // Remove old log messages
        add_action('h5p_daily_cleanup', array($this, 'remove_old_log_events'));

        // Always check if the plugin has been updated to a newer version
        add_action('init', array('H5PPlugin', 'check_for_updates'), 1);

        // Add menu options to admin bar.
        add_action('admin_bar_menu', array($this, 'admin_bar'));*/
    }

    /**
     * @param int $id
     * @return string
     */
    public static function renderShortCode($id)
    {
        $plugin = H5PPlugin::get_instance();
        $html = $plugin->shortcode(['id' => $id]);
        $abs_root = OLabUtilities::get_path_info()['siteRelativeUrl'];

        $raw_js = 'H5PIntegration = null; H5PIntegration = ' . 
                  json_encode($plugin->get_settings());
        $js_for_xAPI = vsprintf("H5P.externalDispatcher.on('xAPI', function (event) {
                                    console.log(event.data.statement);
                                    H5P.jQuery.post('%s/saveXAPIStatement', event.data.statement);
                                });", array( self::get_api_url() ) );

        CustomAssetManager::addRawScript('H5PIntegration', $raw_js, true);
        CustomAssetManager::addRawScript('H5PIntegration.xAPI', $js_for_xAPI);

        return $html;
    }

    /**
     * Return the plugin slug.
     *
     * @since 1.0.0
     * @return string Plugin slug variable.
     */
    public function get_plugin_slug()
    {
        return $this->plugin_slug;
    }

    /**
     * Return an instance of this class.
     *
     * @since 1.0.0
     * @return H5PPlugin A single instance of this class.
     */
    public static function get_instance()
    {
        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Determine charset to use for database tables
     *
     * @since 1.2.0
     * @global \wpdb $wpdb
     */
    public static function determine_charset()
    {
        global $wpdb;
        $charset = '';

        if (!empty($wpdb->charset)) {
            $charset = "DEFAULT CHARACTER SET {$wpdb->charset}";

            if (!empty($wpdb->collate)) {
                $charset .= " COLLATE {$wpdb->collate}";
            }
        }

        return $charset;
    }

    /**
     * Register and enqueue public-facing style sheets and JavaScript files.
     *
     * @since 1.0.0
     */
    public function enqueue_styles_and_scripts()
    {
        wp_enqueue_style($this->plugin_slug . '-plugin-styles', plugins_url('h5p/h5p-php-library/styles/h5p.css'),
            array(), self::VERSION);
    }

    /**
     * Add menu options to the WordPress admin bar
     *
     * @since 1.2.2
     */
    public function admin_bar($wp_admin_bar)
    {
        $wp_admin_bar->add_menu(array(
            'parent' => 'new-content',
            'id' => 'new-h5p-content',
            'title' => __('H5P Content', $this->plugin_slug),
            'href' => admin_url('admin.php?page=h5p_new')
        ));
    }

    /**
     * Get the path to the H5P files folder.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_h5p_path()
    {
        $path_info = OLabUtilities::get_path_info();
        return OLabUtilities::concat_path( $path_info['siteBaseDir'], 'h5p' );
    }

    public static function get_api_url() 
    {    
      $path_info = OLabUtilities::get_path_info();
      return OLabUtilities::concat_path( $path_info['apiRelativePath'], 'h5p' );
    }

    /**
     * Get the URL for the H5P files folder.
     *
     * @since 1.0.0
     * @param bool $absolute
     * @return string
     */
    public static function get_h5p_url($absolute = false)
    {
        static $url;

        if (!$url) {

            $upload_dir = OLabUtilities::get_path_info();

            // Absolute urls are used to enqueue assets.
            $url = array('abs' => $upload_dir['siteBaseUrl'] . '/h5p');

            // Check for HTTPS
            if (OLabUtilities::is_ssl() && substr($url['abs'], 0, 5) !== 'https') {
                // Update protocol
                $url['abs'] = 'https' . substr($url['abs'], 4);
            }

            // Relative URLs are used to support both http and https in iframes.
            $url['rel'] = '/' . preg_replace('/^[^:]+:\/\/[^\/]+\//', '', $url['abs']);
        }

        return $absolute ? $url['abs'] : $url['rel'];
    }

    /**
     * Get H5P language code
     *
     * @param bool $english_only
     * @return string
     */
    public function get_language($english_only = true)
    {
        $result = 'en';

        if ($english_only) {
            return $result;
        }

        /** @var Model_Leap_User $user */
        $user = Auth::instance()->get_user();

        if (empty($user)) {
            return $result;
        }

        $language = $user->language;

        if (empty($language)) {
            return $result;
        }

        return strtolower($language->name);
    }

    /**
     * Get the different instances of the core.
     *
     * @since 1.0.0
     * @param string $type
     * @return H5POpenLabyrinth|\H5PCore|\H5PContentValidator|\H5PExport|\H5PStorage|\H5PValidator
     */
    public function get_h5p_instance($type)
    {
        if (self::$interface === null) {
            self::$interface = new H5POpenLabyrinth();
            $language = $this->get_language();
            self::$core = new \H5PCore(self::$interface, 
                                       $this->get_h5p_path(), 
                                       self::get_h5p_url(), 
                                       $language,
                                       OLabUtilities::get_option('h5p_export', true));

            self::$core->aggregateAssets = !(defined('H5P_DISABLE_AGGREGATION') && H5P_DISABLE_AGGREGATION === true);
        }

        switch ($type) {
            case 'validator':
                return new \H5PValidator(self::$interface, self::$core);
            case 'storage':
                return new \H5PStorage(self::$interface, self::$core);
            case 'contentvalidator':
                return new \H5PContentValidator(self::$interface, self::$core);
            case 'export':
                return new \H5PExport(self::$interface, self::$core);
            case 'interface':
                return self::$interface;
            case 'core':
                return self::$core;
        }

        Log::error('unknonwn h5p type: ' . $type );
        return null;
    }

    /**
     * Get content with given id.
     *
     * @since 1.0.0
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function get_content($id)
    {
        if ($id === false || $id === null) {
            return __('Missing H5P identifier.');
        }

        // Try to find content with $id.
        $core = $this->get_h5p_instance('core');
        $content = $core->loadContent($id);

        if (!$content) {
            return sprintf(__('Cannot find H5P content with id: %d.'), $id);
        }

        $content['language'] = $this->get_language();

        return $content;
    }

    /**
     * Translate h5p shortcode to html.
     *
     * @since 1.0.0
     * @param array $atts
     * @return string
     */
    public function shortcode($atts)
    {
        if (isset($atts['slug'])) {

            $row = H5pContents::ByArray($atts);

            if (!isset($row['id'])) {
                Log::debug( sprintf('Cannot find H5P content with slug: %s.', $atts['slug'] ) );
            }

            $atts['id'] = $row['id'];
        }

        $id = isset($atts['id']) ? intval($atts['id']) : null;
        $content = $this->get_content($id);

//        if (is_string($content)) {
            // Return error message if the user has the correct cap
//            return current_user_can('edit_h5p_contents') ? $content : null;
//        }

        // Log view
        //new H5P_Event('content', 'shortcode',
        //    $content['id'],
        //    $content['title'],
        //    $content['library']['name'],
        //    $content['library']['majorVersion'] . '.' . $content['library']['minorVersion']);

        return $this->add_assets($content);
    }

    /**
     * Get settings for given content
     *
     * @since 1.5.0
     * @param array $content
     * @return array
     */
    public function get_content_settings($content)
    {
        //$wpdb = getWPDB();
        $core = $this->get_h5p_instance('core');
        $urlInfo = OLabUtilities::get_path_info();

        // Add global disable settings
        //$content['disable'] |= $core->getGlobalDisable();

        $safe_parameters = $core->filterParameters($content);

        $settings = array(
            'library' => H5PCore::libraryToString($content['library']),
            'jsonContent' => $safe_parameters,
            'fullScreen' => $content['library']['fullscreen'],
            'exportUrl' => OLabUtilities::get_option('h5p_export', true) ? 
                          self::get_h5p_url() . '/exports/' . ($content['slug'] ? 
                          $content['slug'] . '-' : '') . $content['id'] . '.h5p' : 
                          '',
            'embedCode' => '<iframe src="' . OLabUtilities::concat_path( $urlInfo['apiRelativePath'], 
                                                                         '/h5p/embed/' ) . $content['id'] . '" width=":w" height=":h" frameborder="0" allowfullscreen="allowfullscreen"></iframe>',
            'resizeCode' => '<script src="' . OLabUtilities::concat_path( $urlInfo['siteRelativeUrl'], self::H5P_URL_ROOT ) . '/js/h5p-resizer.js" charset="UTF-8"></script>',
            'url' => OLabUtilities::concat_path( $urlInfo['apiBaseUrl'], '/h5p/embed/' ) . $content['id'],
            'title' => $content['title'],
            'disable' => $content['disable'],
            'displayOptions' => array('frame' => null),
            'contentUserData' => array(
                0 => array(
                    'state' => '{}'
                )
            )
        );

        // Get preloaded user data for the current user
        //$current_user = Auth::instance()->get_user();
        $current_user = HostSystemApi::getUserInfo();

        if (OLabUtilities::get_option('h5p_save_content_state', false) && $current_user['id']) {
            
          $results = H5pContentsUserData::PreLoadedData( $current_user['id'], $content['id'] );

            //$results = $wpdb->get_results($wpdb->prepare(
            //                            "SELECT hcud.sub_content_id,
            //                            hcud.data_id,
            //                            hcud.data
            //                            FROM h5p_contents_user_data hcud
            //                            WHERE user_id = %d
            //                            AND content_id = %d
            //                            AND preload = 1",
            //                $current_user['id'], $content['id'] ));

            if ($results) {
                foreach ($results as $result) {
                    $settings['contentUserData'][$result->sub_content_id][$result->data_id] = $result->data;
                }
            }
        }

        Log::debug( "Settings: " . var_export( $settings, true ));

        return $settings;
    }

    /**
     * Include settings and assets for the given content.
     *
     * @since 1.0.0
     * @param array $content
     * @param boolean $no_cache
     * @return string Embed code
     */
    public function add_assets($content, $no_cache = false)
    {
        // Add core assets
        $this->add_core_assets();

        $url_base = OLabUtilities::get_path_info()['siteRelativeUrl'];

        // Detemine embed type
        $embed = H5PCore::determineEmbedType($content['embedType'], $content['library']['embedTypes']);

        // Make sure content isn't added twice
        $cid = 'cid-' . $content['id'];
        if (!isset(self::$settings['contents'][$cid])) {

          self::$settings['contents'][$cid] = $this->get_content_settings($content);
          $core = $this->get_h5p_instance('core');

          // Get assets for this content
          $preloaded_dependencies = $core->loadContentDependencies($content['id'], 'preloaded');
          $files = $core->getDependenciesFiles($preloaded_dependencies);
          $this->alter_assets($files, $preloaded_dependencies, $embed);

          $cache_buster = '?ver=' . H5PPlugin::VERSION;

          if ($embed === 'div') {

            $this->enqueue_assets($files);

          } elseif ($embed === 'iframe') {
            
            $url_base = OLabUtilities::concat_path( $url_base, '/h5p' );

            foreach ($files['scripts'] as $item ) {
              $url = OLabUtilities::concat_path( $url_base, $item->path ) . $cache_buster;
              Log::debug( "Adding iframe script asset: " . $url );
              self::$settings['contents'][$cid]['scripts'][] = $url;
            }

            foreach ($files['styles'] as $item ) {
              $url = OLabUtilities::concat_path( $url_base, $item->path ) . $cache_buster;              	
              Log::debug( "Adding iframe style asset: " . $url );
              self::$settings['contents'][$cid]['styles'][] = $url; 
            }

          }
        }
       
        if ($embed === 'div') {
            $html = '<div class="h5p-content" data-content-id="' . $content['id'] . '"></div>';
        } else {
            $html = '<div class="h5p-iframe-wrapper"><iframe id="h5p-iframe-' . $content['id'] . '" class="h5p-iframe" data-content-id="' . $content['id'] . '" style="height:1px" src="about:blank" frameBorder="0" scrolling="no"></iframe></div>';
        }

        Log::debug( "HTML: " . $html );

        return $html;
    }

    /**
     * Finds the assets for the dependencies and allows other plugins to change
     * them and add their own.
     *
     * @since 1.5.3
     * @param array $dependencies
     * @param array $files scripts & styles
     * @param string $embed type
     */
    public function alter_assets(&$files, &$dependencies, $embed)
    {
        return;

        if (!has_action('h5p_alter_library_scripts') && !has_action('h5p_alter_library_styles')) {
            return;
        }

        // Refactor dependency list
        $libraries = array();
        foreach ($dependencies as $dependency) {
            $libraries[$dependency['machineName']] = array(
                'majorVersion' => $dependency['majorVersion'],
                'minorVersion' => $dependency['minorVersion']
            );
        }

        /**
         * Allows you to alter which JavaScripts are loaded for H5P. This is
         * useful for adding your own custom scripts or replacing existing once.
         *
         * @since 1.5.3
         *
         * @param array &$scripts List of JavaScripts to be included.
         * @param array $libraries The list of libraries that has the scripts.
         * @param string $embed_type Possible values are: div, iframe, external, editor.
         */
        do_action_ref_array('h5p_alter_library_scripts', array(&$files['scripts'], $libraries, $embed));

        /**
         * Allows you to alter which stylesheets are loaded for H5P. This is
         * useful for adding your own custom stylesheets or replacing existing once.
         *
         * @since 1.5.3
         *
         * @param array &$styles List of stylesheets to be included.
         * @param array $libraries The list of libraries that has the styles.
         * @param string $embed_type Possible values are: div, iframe, external, editor.
         */
        do_action_ref_array('h5p_alter_library_styles', array(&$files['styles'], $libraries, $embed));
    }

    /**
     * Enqueue assets for content embedded by div.
     *
     * @param array $assets
     */
    public function enqueue_assets(&$assets)
    {
        $url_base = OLabUtilities::get_path_info()['siteRelativeUrl'];
        $url_base = OLabUtilities::concat_path( $url_base, '/h5p' );
        $cache_buster = '?ver=' . H5PPlugin::VERSION;

        foreach ($assets['scripts'] as $script) {

          $url = OLabUtilities::concat_path( $url_base, $script->path ) . $script->version . $cache_buster;

          if (!in_array($url, self::$settings['loadedJs'])) {
            self::$settings['loadedJs'][] = $url;
            Log::debug( "Adding loadedJs asset: " . $url );
            CustomAssetManager::addScript($this->asset_handle(trim($script->path, '/')) . '-js', $url );
          }

        }

        foreach ($assets['styles'] as $style) {

          $url = OLabUtilities::concat_path( $url_base, $style->path ) . $style->version . $cache_buster;

          if (!in_array($url, self::$settings['loadedCss'])) {
            self::$settings['loadedCss'][] = $url;
            Log::debug( "Adding loadedCss asset: " . $url );
            CustomAssetManager::addStyle($this->asset_handle(trim($style->path, '/')) . '-css', $url );
          }

        }
    }

    /**
     * Removes the file extension and replaces all specialchars with -
     *
     * @since 1.0.0
     * @param string $path
     * @return string
     */
    public function asset_handle($path)
    {
        return $this->plugin_slug . '-' . preg_replace(array('/\.[^.]*$/', '/[^a-z0-9]/i'), array('', '-'),
            strtolower($path));
    }

    /**
     * Get generic h5p settings
     *
     * @since 1.3.0
     */
    public function get_core_settings()
    {
      $current_user = HostSystemApi::getUserInfo();

      $is_logged_in = !empty($current_user);
      $path_info = OLabUtilities::get_path_info();

      $settings = array(

          'baseUrl' => $path_info['siteBaseUrl'],
          'url' => self::get_h5p_url(),
          'postUserStatistics' => ($is_logged_in && (OLabUtilities::get_option('h5p_track_user', '1') === '1')),
          //'ajaxPath' => OLabUtilities::admin_url('/h5p/ajax_'),
          'ajaxPath' => OLabUtilities::concat_path( $path_info['apiBaseUrl'], '/h5p/ajax_' ),
          'ajax' => array(
              'setFinished' => OLabUtilities::concat_path( $path_info['apiRelativePath'] , 'h5p/saveResult' ),
              'contentUserData' => OLabUtilities::concat_path( $path_info['apiRelativePath'] , 
                                                               'h5p/contentUserData?content_id=:contentId&data_type=:dataType&sub_content_id=:subContentId' ),
          ),
          'tokens' => array(
              'result' => '',
              'contentUserData' => ''
          ),

          'saveFreq' => OLabUtilities::get_option('h5p_save_content_state', false) ? 
                        OLabUtilities::get_option('h5p_save_content_frequency', 30) : 
                        false,

          'siteUrl' => $path_info['siteBaseUrl'],
          'l10n' => array(
              'H5P' => array(
                  'fullscreen' => __('Fullscreen'),
                  'disableFullscreen' => __('Disable fullscreen'),
                  'download' => __('Download'),
                  'copyrights' => __('Rights of use'),
                  'embed' => __('Embed'),
                  'size' => __('Size'),
                  'showAdvanced' => __('Show advanced'),
                  'hideAdvanced' => __('Hide advanced'),
                  'advancedHelp' => __('Include this script on your website if you want dynamic sizing of the embedded content:'),
                  'copyrightInformation' => __('Rights of use'),
                  'close' => __('Close'),
                  'title' => __('Title'),
                  'author' => __('Author'),
                  'year' => __('Year'),
                  'source' => __('Source'),
                  'license' => __('License'),
                  'thumbnail' => __('Thumbnail'),
                  'noCopyrights' => __('No copyright information available for this content.'),
                  'downloadDescription' => __('Download this content as a H5P file.'),
                  'copyrightsDescription' => __('View copyright information for this content.'),
                  'embedDescription' => __('View the embed code for this content.'),
                  'h5pDescription' => __('Visit H5P.org to check out more cool content.'),
                  'contentChanged' => __('This content has changed since you last used it.'),
                  'startingOver' => __("You'll be starting over."),
                  'confirmDialogHeader' => __('Confirm action'),
                  'confirmDialogBody' => __('Please confirm that you wish to proceed. This action is not reversible.'),
                  'cancelLabel' => __('Cancel'),
                  'confirmLabel' => __('Confirm')
              )
          )
      );

      if ($is_logged_in) {
        $settings['user'] = array(
            'name' => $current_user['name'],
            'mail' => $current_user['email']
        );
      } else {
        unset($settings['siteUrl']);
      }

      return $settings;
    }

    /**
     * Set core JavaScript settings and add core assets.
     *
     * @since 1.0.0
     */
    public function add_core_assets()
    {
        if (self::$settings !== null) {
            return; // Already added
        }

        self::$settings = $this->get_core_settings();
        self::$settings['core'] = array(
            'styles' => array(),
            'scripts' => array()
        );
        self::$settings['loadedJs'] = array();
        self::$settings['loadedCss'] = array();
        $cache_buster = '?ver=' . self::VERSION;

        $url_base = OLabUtilities::get_path_info()['siteRelativeUrl'];

        // Add core stylesheets
        foreach (H5PCore::$styles as $style) {
            $url = OLabUtilities::concat_path( self::H5P_URL_ROOT, $style ) . $cache_buster;
            CustomAssetManager::addStyle($this->asset_handle('core-' . $style), $url);
            self::$settings['core']['styles'][] = OLabUtilities::concat_path( $url_base, $url );
        }

        // Add core JavaScript
        foreach (H5PCore::$scripts as $script) {
            $url = OLabUtilities::concat_path( self::H5P_URL_ROOT, $script ) . $cache_buster;
            CustomAssetManager::addScript($this->asset_handle('core-' . $script), $url);
            self::$settings['core']['scripts'][] = OLabUtilities::concat_path( $url_base, $url );
        }
    }

    /**
     * Add H5P JavaScript settings to the bottom of the page.
     *
     * @since 1.0.0
     */
    public function add_settings()
    {
        if (self::$settings !== null) {
            $this->print_settings(self::$settings);
        }
    }

    /**
     * JSON encode and print the given H5P JavaScript settings.
     *
     * @since 1.0.0
     * @param array $settings
     */
    public function print_settings(&$settings, $obj_name = 'H5PIntegration')
    {
        static $printed;
        if (!empty($printed[$obj_name])) {
            return; // Avoid re-printing settings
        }

        $json_settings = json_encode($settings);
        if ($json_settings !== false) {
            $printed[$obj_name] = true;
            print '<script>' . $obj_name . ' = ' . $json_settings . ';</script>';
        }
    }

    public function getSettingsHTML(&$settings, $obj_name = 'H5PIntegration')
    {
        ob_start();
        $this->print_settings($settings, $obj_name);

        return ob_get_clean();
    }

    /**
     * Get added JavaScript settings.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_settings()
    {
        return self::$settings;
    }

    /**
     * This function will unlink tmp editor files for content
     * that has never been saved.
     *
     * @since 1.0.0
     */
    public function remove_old_tmp_files()
    {
        $plugin = H5PPlugin::get_instance();

        $h5p_path = $plugin->get_h5p_path();
        $editor_path = $h5p_path . DIRECTORY_SEPARATOR . 'editor';
        if (!is_dir($h5p_path) || !is_dir($editor_path)) {
            return;
        }

        $dirs = glob($editor_path . DIRECTORY_SEPARATOR . '*');
        if (empty($dirs)) {
            return;
        }

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $files = glob($dir . DIRECTORY_SEPARATOR . '*');
            if (empty($files)) {
                continue;
            }

            foreach ($files as $file) {
                if (time() - filemtime($file) > 86400) {
                    // Not modified in over a day
                    unlink($file);
                }
            }
        }
    }

    /**
     * Try to connect with H5P.org and look for updates to our libraries.
     * Can be disabled through settings
     *
     * @since 1.2.0
     */
    public function get_library_updates()
    {
        $core = $this->get_h5p_instance('core');
        $core->fetchLibrariesMetadata();
    }

    /**
     * Remove any log messages older than the set limit.
     *
     * @since 1.6
     */
    public function remove_old_log_events()
    {
        /*global $wpdb;

        $older_than = (time() - H5PEventBase::$log_time);

        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->prefix}h5p_events
                      WHERE created_at < %d
            ", $older_than));*/
    }
}
