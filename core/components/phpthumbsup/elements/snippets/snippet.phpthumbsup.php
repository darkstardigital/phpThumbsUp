<?php
/**
 * Turns a set of arguments passed to this snippet into a phpThumbsUp url for a thumbnail
 *
 *
 * @package   phpThumbsUp
 * @author    Darkstar Design (info@darkstardesign.com)
 */

// input
$image = !empty($input) ? trim($input) : '';
$options = !empty($options) ? trim($options) : '';
$options = explode('&', str_replace('=', '/', $options));

// path to model
$default_path = $modx->getOption('core_path') . 'components/phpthumbsup/';
$path = $modx->getOption('phpthumbsup.core_path', NULL, $default_path) . 'model/phpthumbsup/';
$thumbsup = $modx->getService('thumbsup', 'PhpThumbsUp', $path, $scriptProperties);

// make sure we have an image and options
if (!empty($image) && !empty($options)) {
    $image = $thumbsup->options_to_path($image, $options);
}

// return image path
return $image;