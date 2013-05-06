<?php
/**
 * The main handler for phpthumbsup. Instantiates model and performs appropriate actions based on event.
 *
 * @package   phpThumbsUp
 * @author    Darkstar Design (info@darkstardesign.com)
 */

// path to model
$default_path = $modx->getOption('core_path') . 'components/phpthumbsup/';
$path = $modx->getOption('phpthumbsup.core_path', NULL, $default_path) . 'model/phpthumbsup/';
$thumbsup = $modx->getService('thumbsup', 'PhpThumbsUp', $path, $scriptProperties);

// make sure model loaded, if not log error and return
if (!($thumbsup instanceof PhpThumbsUp)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[phpThumbsUp] Could not load PhpThumbsUp class.');
    return NULL;
}

// handle events
switch ($modx->event->name) {

    // OnFileManagerUpload we want to auto create thumbs if specified in settings
    case 'OnFileManagerUpload':
        $thumbsup->process_upload($files, $directory);
        break;

    // OnPageNotFound and OnHandleRequest means we need to look for a thumb
    case 'OnHandleRequest':
    case 'OnPageNotFound':
        $thumbsup->process_thumb();
        break;

    // OnSiteRefresh delete phpthumbsup cache
    case 'OnSiteRefresh':
        $thumbsup->clear_cache();
        break;

    // if we didn't match an event just return null
    default:
        return NULL;

}