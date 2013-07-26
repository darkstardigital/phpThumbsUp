<?php
/**
 * Array of settings to be installed with package.
 *
 * Returns an array of meta information for settings for the package. Each setting
 * should be an array containing 'key', 'value', 'xtype', 'namespace', and 'area'.
 *
 * Example:
 * return array(
 *     array(
 *         'key' => 'packagename.setting_name',
 *         'value' => '',
 *         'xtype' => 'textfield',
 *         'namespace' => 'packagename',
 *         'area' => 'general'
 *     ),
 *     array(
 *         'key' => 'packagename.setting2_name',
 *         'value' => 'myValue',
 *         'xtype' => 'textfield',
 *         'namespace' => 'packagename',
 *         'area' => 'general'
 *     )
 * );
 *
 * @package phpThumbsUp
 * @subpackage TransportBuilder
 */

return array(
    array(
        'key' => 'phpthumbsup.core_path',
        'value' => '{core_path}components/phpthumbsup/',
        'xtype' => 'textfield',
        'namespace' => 'phpthumbsup',
        'area' => 'paths'
    ),
    array(
        'key' => 'phpthumbsup.cache_path',
        'value' => '{core_path}cache/phpthumbsup/',
        'xtype' => 'textfield',
        'namespace' => 'phpthumbsup',
        'area' => 'paths'
    ),
    array(
        'key' => 'phpthumbsup.base_url',
        'value' => '{base_url}phpthumbsup/',
        'xtype' => 'textfield',
        'namespace' => 'phpthumbsup',
        'area' => 'general'
    ),
    array(
        'key' => 'phpthumbsup.auto_create',
        'value' => '',
        'xtype' => 'textfield',
        'namespace' => 'phpthumbsup',
        'area' => 'general'
    ),
    array(
		'key' => 'phpthumbsup.clear_cache',
		'value' => true,
		'xtype' => 'combo-boolean',
		'namespace' => 'phpthumbsup',
		'area' => 'general'
	),
	array(
		'key' => 'phpthumbsup.available_options',
		'value' => 'w,h,wp,hp,wl,hl,ws,hs,f,q,sx,sy,sw,sh,zc,bg,bc,fltr,xto,ra,ar,sfn,aoe,iar,far,dpi,maxb',
		'xtype' => 'textfield',
		'namespace' => 'phpthumbsup',
		'area' => 'general'
	),
	array(
		'key' => 'phpthumbsup.available_filters',
		'value' => 'brit,cont,gam,sat,ds,gray,th,rcd,clr,sep,usm,blur,sblr,smth,lvl,wb,hist,flip,ric,elip,bvl,bord,fram,drop,crop,rot,size,stc',
		'xtype' => 'textfield',
		'namespace' => 'phpthumbsup',
		'area' => 'general'
	),
	array(
        'key' => 'phpthumbsup.mobile',
        'value' => true,
        'xtype' => 'textfield',
        'namespace' => 'phpthumbsup',
        'area' => 'general'
    ),
	array(
        'key' => 'phpthumbsup.mobile_threshold',
        'value' => '480,1024',
        'xtype' => 'textfield',
        'namespace' => 'phpthumbsup',
        'area' => 'general'
    )
);