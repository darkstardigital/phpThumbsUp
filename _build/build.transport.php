<?php
/**
 * Build script
 *
 * @package phpThumbsUp
 * @subpackate TransportBuilder
 */

define('PKG_ROOT', dirname(dirname(__FILE__)) . '/');

require_once(PKG_ROOT . '_build/build.config.php');
require_once(MODX_CORE_PATH . 'model/modx/modx.class.php');
require_once(PKG_ROOT . '_build/inc/transport_builder.class.php');

$builder = new TransportBuilder();
$builder->make();

