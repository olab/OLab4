<?php

namespace Entrada\Modules\Olab\Http\Controllers;

use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabConstants;
use Entrada\Modules\Olab\Classes\UserStateHandler;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Classes\Autoload\Events\EventHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\Autoload\WikiTags\OlabTagBase;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\CounterManager;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\SecurityContext;

use \H5PCore;
use Entrada\Modules\Olab\Classes\h5p\H5PPlugin;
use Entrada\Modules\Olab;

use Entrada\Modules\Olab\Models\Map;
use Entrada\Modules\Olab\Models\Node;
use Entrada\Modules\Olab\Models\NodeCounter;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;

class OlabH5PController extends OlabController
{
  public function saveResult( Request $request ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    try
    {
      // run common controller initialization
      $this->initialize();

      $content_id = filter_input(INPUT_POST, 'contentId', FILTER_VALIDATE_INT);
      if (!$content_id) {
        H5PCore::ajaxError(__('Invalid content.'));
        die;
      }

      $record = H5pResults::ByUserContent( $this->user_id, $content_id )->first();

      if ( !$record ) {

        $record = new H5pResults();
        $record->user_id = $this->user_id;
        $record->content_id = $content_id;

      }

      $record->score = filter_input(INPUT_POST, 'score', FILTER_VALIDATE_INT);
      $record->max_score = filter_input(INPUT_POST, 'maxScore', FILTER_VALIDATE_INT);
      $record->opened = filter_input(INPUT_POST, 'opened', FILTER_VALIDATE_INT);
      $record->finished = filter_input(INPUT_POST, 'finished', FILTER_VALIDATE_INT);

      $record->time = filter_input(INPUT_POST, 'time', FILTER_VALIDATE_INT);
      if ( $record->time === null) {
        $record->time  = 0;
      }

      $record->save();

      return OLabUtilities::make_api_return();
      
    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
      return OLabUtilities::make_api_return( $tracer, array(), 1, 0, "error", "", $exception );
      //OlabExceptionHandler::restApiError( $exception );
    }

    //H5PCore::ajaxSuccess();
    //exit;
  }

  public function saveXAPIStatement( Request $request ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
  }  

  /**
   * Print page for embed iframe
   */
  public function embed( Request $request, int $id )
  {
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $id . ")" );

    // Allow other sites to embed
    header_remove('X-Frame-Options');

    $url_base = OLabUtilities::get_path_info()['siteRelativeUrl'];
    $url_base .= H5PPlugin::H5P_URL_ROOT;

    // Find content
    if ($id !== null) {

      $plugin = H5PPlugin::get_instance();
      $content = $plugin->get_content($id);
      if (!is_string($content)) {

        // Everyone is allowed to embed, set through settings
        $embed_allowed = ( OLabUtilities::get_option('h5p_embed', true) && !($content['disable'] & H5PCore::DISABLE_EMBED));

        if (!$embed_allowed) {
          // Check to see if embed URL always should be available
          $embed_allowed = (defined('H5P_EMBED_URL_ALWAYS_AVAILABLE') && H5P_EMBED_URL_ALWAYS_AVAILABLE);
        }

        if ($embed_allowed) {
          $lang = $plugin->get_language();
          $cache_buster = '?ver=' . H5PPlugin::VERSION;

          // Get core settings
          $integration = $plugin->get_core_settings();

          // Get core scripts
          $scripts = array();
          foreach (H5PCore::$scripts as $script) {
            $url = OLabUtilities::concat_path( $url_base, $script ) . $cache_buster;
            $scripts[] = $url;
          }

          // Get core styles
          $styles = array();
          foreach (H5PCore::$styles as $style) {
            $url = OLabUtilities::concat_path( $url_base, $style ) . $cache_buster;
            $styles[] = $url;
          }

          // Get content settings
          $integration['contents']['cid-' . $content['id']] = $plugin->get_content_settings($content);
          $core = $plugin->get_h5p_instance('core');

          // Get content assets
          $preloaded_dependencies = $core->loadContentDependencies($content['id'], 'preloaded');
          $files = $core->getDependenciesFiles($preloaded_dependencies);
          //$plugin->alter_assets($files, $preloaded_dependencies, 'external');

          $scripts = array_merge($scripts, $core->getAssetsUrls($files['scripts']));
          $styles = array_merge($styles, $core->getAssetsUrls($files['styles']));

          $base_dir = OLabUtilities::get_path_info()[ 'siteBaseDir' ];
          $base_dir .= H5PPlugin::H5P_URL_ROOT . '/';

          include_once( $base_dir . 'embed.php');
          die;
        }
      }
    }

    // Simple unavailable page
    echo '<body style="margin:0"><div style="background: #fafafa url(/images/olab/h5p/h5p.svg) no-repeat center;background-size: 50% 50%;width: 100%;height: 100%;"></div><div style="width:100%;position:absolute;top:75%;text-align:center;color:#434343;font-family: Consolas,monaco,monospace">' . __('Content unavailable.') . '</div></body>';
    die;
  }

  private function loadAssets()
  {
    $this->templateData = CustomAssetManager::loadAssets($this->templateData);
  }

}

?>