<?php
/**
 * Core class for phpThumbsUp.
 *
 * Provides methods used by the plugins and snippets of phpThumbsUp.
 *
 * @package   phpThumbsUp
 * @author    Darkstar Design (info@darkstardesign.com)
 */
class PhpThumbsUp {

    public $modx;
    public $config = array();


    /**
     * class constructor
     *
     * @param modX &$modx
     * @param array $config
     */
    public function __construct(modX &$modx, array $config = array()) {
        $this->modx = &$modx;
        $base_path = rtrim($this->modx->getOption('base_path', $config, MODX_BASE_PATH), '/') . '/';
        $core_path = rtrim($this->modx->getOption('phpthumbsup.core_path', $config, $this->modx->getOption('core_path') . 'components/phpthumbsup/'), '/') . '/';
        $cache_path = rtrim($this->modx->getOption('phpthumbsup.cache_path', $config, $core_path . 'cache/'), '/') . '/';
        $base_url = rtrim($this->modx->getOption('phpthumbsup.base_url', $config, 'phpthumbsup/'), '/') . '/';
        $auto_create = $this->modx->getOption('phpthumbsup.auto_create', $config, '');
        $clear_cache = ($this->modx->getOption('phpthumbsup.clear_cache', $config, true) ? true : false);
		$available_options = explode(',', trim($this->modx->getOption('phpthumbsup.available_options', $config, ''), ','));
		$available_filters = explode(',', trim($this->modx->getOption('phpthumbsup.available_filters', $config, ''), ','));
                $available_widths = explode(',', trim($this->modx->getOption('phpthumbsup.available_widths', $config, ''), ','));
                $available_heights = explode(',', trim($this->modx->getOption('phpthumbsup.available_heights', $config, ''), ','));
        $responsive = ($this->modx->getOption('phpthumbsup.responsive', $config, true) ? true : false);
        $responsive_threshold = explode(',', trim($this->modx->getOption('phpthumbsup.responsive_threshold', $config, ''), ','));
        $default = $this->modx->getOption('phpthumbsup.default', $config, '');
        $this->config = array_merge(array(
            'basePath' => $this->parse_path($base_path),
            'corePath' => $this->parse_path($core_path),
            'modelPath' => $this->parse_path($core_path) . 'model/',
            'cachePath' => $this->parse_path($cache_path),
            'baseUrl' => $base_url,
            'autoCreate' => $auto_create,
            'clearCache' => $clear_cache,
			'available_options' => $available_options,
			'available_filters' => $available_filters,
			'available_widths' => $available_widths,
			'available_heights' => $available_heights,
            'responsive' => $responsive,
            'responsiveThreshold' => $responsive_threshold,
            'default' => $default,
        ), $config);
    }

	/**
	 * Convert shortcuts like {core_path} into real path values
	 *
	 * @param $path
	 *
	 * @return mixed
	 */
	protected function parse_path($path) {
		$path = str_replace('{core_path}', MODX_CORE_PATH, $path);
		$path = str_replace('{base_path}', MODX_BASE_PATH, $path);
		$path = str_replace('{assets_path}', MODX_ASSETS_PATH, $path);

		return $path;
	}


    /**
     * Helper method for the phpThumbsUp snippet
     *
     * Takes an image url and a set of phpthumb options and returns the url for the thumb.
     *
     * @param string $image relative url to src image (assets/images/foo.jpg)
     * @param array $options key/value modPhpThumb options
     * @return string a phpThumbsUp url for a thumbnail
     */
    public function options_to_path($image, $options) {
        $path = !empty($image) ? rtrim($this->config['baseUrl'], '/') : '';
        if (!empty($options)) {
            $options = explode('&', $options);
            array_walk($options, array($this, 'encode_url'));
            foreach ($options as $opt) {
                if (substr($opt, 0, 4) == 'src/') {
                    $image = substr($opt, 4);
                } else {
                    $path .= "/$opt";
                }
            }
        }
        if (!empty($image)) {
            $path .= '/src/' . ltrim($image, '/');
        }
        return $path;
    }


    /**
     * Clears the phpthumbsup cache.
     *
     * @param bool $force set to true to ignore phpthumbsup.clear_cache setting
     */
    public function clear_cache($force = false) {
        if ($force || $this->config['clearCache']) {
            if (is_dir($this->config['cachePath'])) {
                foreach (scandir($this->config['cachePath']) as $file) {
                    if ($file != '.' && $file != '..') {
                        unlink($this->config['cachePath'] . $file);
                    }
                }
            }
        }
    }


    /**
     * Handler for OnFileManagerUpload event.
     *
     * Auto creates thumbnails on file manager uploads based on settings.
     *
     * @param array $files the php $_FILES array
     * @param string $upload_dir directory path files are being uploaded to
     */
    public function process_upload($files, $upload_dir) {
        $upload_dir = trim($upload_dir, '/');
        $base_url = ltrim($this->config['baseUrl'], '/');
        $dirs = explode(':', trim($this->config['autoCreate'], ':'));
        foreach ($dirs as $dir) {
            $dir = trim($dir, '/');
            $paths = explode('/src/', $dir);
            $options_url = array_shift($paths);
            foreach ($paths as $path) {
                if (strpos($upload_dir, $path) === 0) {
                    foreach ($files as $file) {
                        //move_uploaded_file($file['tmp_name'], MODX_CORE_PATH . "$upload_dir/$file[name]");
                        $url = "/$base_url/$options_url/src/$upload_dir/$file[name]";
                        $options = $this->get_options($url, $base_url);
                        $thumb_path = $this->get_thumb_path($options);
                        $this->create_thumb($thumb_path, $options);
                    }
                }
            }
        }
    }


    /**
     * Handler for OnPageNotFound event.
     *
     * Checks path to see if a thumb is being requested. If so, generates thumb if it doesn't already exist,
     * outputs the content of the thumb as an image, and exits.
     */
    public function process_thumb() {
        if (isset($_REQUEST['q'])) {
            //$url = ltrim($_REQUEST['q'], '/');
            $url = ltrim($_SERVER['REQUEST_URI'], '/');
            $base_url = ltrim($this->config['baseUrl'], '/');
            if (strpos($url, $base_url) === 0) {
                $options = $this->get_options($url, $base_url);
                if (count($options) == 1 && !empty($options['src'])) {
                    $this->display($options['src']);
                } else {
                    $path = $this->get_thumb_path($options);
                    $this->create_thumb($path, $options);
                    $this->display($path);
                }
                exit;
            }
        }
    }


    /**
     * Returns an array of options to be passed to modPhpThumb from the url provided.
     *
     * @param string $url a phpThumbsUp url for a thumbnail
     * @param string $base_url the base url for phpthumbsup
     * @return array key/value options to be passed to modPhpThumb
     */
    protected function get_options($url, $base_url) {
        $thumb_args = explode('/src/', trim(substr($url, strlen($base_url)), '/'));
        $option_args = explode('/', $thumb_args[0]);
		$default_path = ltrim($this->options_to_path('', $this->config['default']), '/');
        $default_args = explode('/', $default_path);

		// since we're coming from $_REQUEST or an already decoded url specified by the user,
		// we don't need to decode again (could cause security concerns)
        //
        // UPDATE: we need to use $_SERVER['REQUEST_URI'] and manually decode in case a filter
        //         contains a "/" in it, as we have to explode before urldecode
        array_walk($default_args, array($this, 'decode_url'));
        array_walk($option_args, array($this, 'decode_url'));
        $options = $this->parse_options($default_args, false);
        $options = $this->parse_options($option_args, true, $options);
        $options = $this->set_width_height($options);
        // NOTE: v1.1.0 allows a thumbsup url to contain only a src with no options. so we can
        // no longer assume exploding on /src/ will give us the src in $thumb_args[1]
        if (count($thumb_args) == 1) {
            $options['src'] = urldecode(preg_replace(':^/?src/?:', '', $thumb_args[0]));
        } else {
            $options['src'] = urldecode($thumb_args[1]);
        }
        return $options;
    }


    /**
     * Turn a path array into an options array
     *
     * @param array     $path_array
     * @param bool      $check_available
     * @param array     $options
     * @return array
     */
    protected function parse_options($path_array, $check_available = true, $options = array()) {
        for ($i = 0, $j = count($path_array) - 1;  $i < $j; $i += 2) {
            // if a filter name ends with [] it is an array
            if (preg_match('/(.+)\[\]$/', $path_array[$i], $m)) {
                if (!$check_available || $this->is_available_option($m[1], $path_array[$i + 1])) {
                    // for array-type options (like filters), default values will not technically be overwritten by
                    // passed options - they will be included side by side. This is because many of these filters
                    // don't have a key/value, so it's hard to determine which filters should be overwritten
                    if (!isset($options[$m[1]])) {
                        $options[$m[1]] = array();
                    }
                    $options[$m[1]][] = $path_array[$i + 1];
                }
            } else if (!$check_available || $this->is_available_option($path_array[$i], $path_array[$i + 1])) {
                $options[$path_array[$i]] = $path_array[$i + 1];
            }
        }

        return $options;
    }


    /**
     * Updates the width (w) and height (h) values in the options array to serve smaller image on mobile devices.
     *
     * Checks a cookie set by javascript that contains the screen width. If the screen width is less than a threshold
     * value set in phpthumbsup.responsiveThreshold then the width option is changed to that threshold. If a height was
     * also specified in options it is updated proportionally to the width.
     *
     * @param array $options key/value options to be passed to modPhpThumb
     * @return array key/value options to be passed to modPhpThumb
     */
    protected function set_width_height($options) {
        if ($this->config['responsive'] && !empty($this->config['responsiveThreshold']) && !empty($_COOKIE['phptu_width'])) {
            $threshold = 0;
            foreach ($this->config['responsiveThreshold'] as $w) {
                if ($_COOKIE['phptu_width'] <= $w) {
                    $threshold = $w;
                    break;
                }
            }
            if ($threshold) {
                if (empty($options['w']) || $options['w'] > $threshold) {
                    $orig_width = empty($options['w']) ? 0 : $options['w'];
                    $options['w'] = $threshold;
                    if (!empty($options['h'])) {
                        if ($orig_width) {
                            $options['h'] = $options['h'] * $threshold / $orig_width;
                        } else {
                            unset($options['h']);
                        }
                    }
                }
            }
        }
        return $options;
    }


    /**
     * method passed to array walk to urldecode() each element
     *
     * @param string $val reference to array element
     */
    protected function decode_url(&$val) {
        $val = urldecode($val);
    }


    /**
     * method passed to array walk to urlencode() each element
     *
     * @param string $val reference to array element
     */
    protected function encode_url(&$val) {
        $parts = explode('=', $val);
        $encoded = '';
        foreach ($parts as $part) {
            $encoded .= "/$part";
        }
        $val = ltrim($encoded, '/');
    }


    /**
     * Returns the path to the thumbnail for the phpThumbsUp url provided.
     *
     * @param array $options key/value modPhpThumb options
     * @return string absolute path to the thumbnail
     */
    protected function get_thumb_path($options) {
        $filename = basename($options['src']);
        $ext = '';
        if (preg_match('/(.+)(\.[^.]+)$/', $filename, $m)) {
            $filename = $m[1];
            $ext = $m[2];
        }
        $file = $this->config['cachePath'] . $filename . '.' . md5($this->options_to_string($options)) . $ext;
        return $file;
    }


    /**
     * Returns options array as a string separated by slashes.
     *
     * Use this to generate a unique md5 hash for each thumb based on the src path and options. Sorts
     * the options so a different file will not get created if the options are the same but in a different
     * order.
     *
     * @param $options
     * @return string
     */
    protected function options_to_string($options) {
        ksort($options);
        $str = '';
        foreach ($options as $k => $v) {
            $str .= '/' . $k;
            if (is_array($v)) {
                $str .= $this->options_to_string($v);
            } else {
                $str .= '/' . $v;
            }
        }
        return $str;
    }


    /**
     * Creates the thumbnail file provided if it doesn't already exist based on the $options array.
     *
     * @param string $file absolute path to the thumbnail
     * @param array $options key/value options passed to modPhpThumb
     * @return bool true if thumb already exists or gets created, false if there is an error
     */
    protected function create_thumb($file, $options) {
        if ($this->check_if_exists($file, $options['src'])) {
            return true;
        }
        if (!$this->check_cache_dir()) {
            return false;
        }
        if (!$this->modx->loadClass('modPhpThumb', $this->modx->getOption('core_path') . 'model/phpthumb/', true, true)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[phpThumbsUp] Could not load modPhpThumb class.');
            return false;
        }
        // NOTE: there's a bug in modPhpThumb that doesn't generate the path correctly when in the manager
        //       context, so we have to manually prepend a slash to the src path if in the mgr context
        if ($this->modx->context->key == 'mgr') {
            $options['src'] = '/' . $options['src'];
        }
        $pt = new modPhpThumb($this->modx);
        $pt->config = array_merge($pt->config, $options);
        $pt->initialize();
        $pt->GenerateThumbnail();
        $pt->RenderToFile($file);
        return true;
    }


    /**
     * Checks if the thumbnail exists and the source image hasn't changed since the thumb was created.
     *
     * @param string $file absolute path to the thumbnail
     * @param string $src relative url for source image
     * @return bool
     */
    protected function check_if_exists($file, $src) {
        $src = $this->config['basePath'] . $src;
        if (file_exists($file)) {
            if (filemtime($file) >= filemtime($src)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Checks if the cache directory exists. tries to create it if not. logs an error message if it can't create it.
     *
     * @return bool false if directory doesn't exist, isn't writable, or could not be created
     */
    protected function check_cache_dir() {
        $dir = $this->config['cachePath'];
        if (!is_dir($dir)) {
            if (!@mkdir($dir)) {
                $this->modx->log(modX::LOG_LEVEL_ERROR, "[phpThumbsUp] Could not create cache directory $dir. Please create this directory manually and make sure it is writable by the web server.");
                return false;
            }
        }
        if (!is_writable($dir)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, "[phpThumbsUp] Cache directory $dir is not writable by the webserver.");
            return false;
        }
        return true;
    }


    /**
     * Displays the thumbnail provided.
     *
     * @param $file absolute path to the thumbnail
     */
    protected function display($file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file);
        finfo_close($finfo);

        $etag = md5_file($file);
        $last_modified = gmstrftime('%a, %d %b %Y %T %Z', filemtime($file));
        if (@strtotime($this->get_server_var('HTTP_IF_MODIFIED_SINCE')) == $last_modified || $this->get_server_var('HTTP_IF_NONE_MATCH') == $etag) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
            return;
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename=' . preg_replace('/\.[^.]+(\.[^.]+)$/', '$1', basename($file)));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: public');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        header('Etag: '. $etag);
        header('Last-Modified: '. $last_modified);
        readfile($file);
    }


	/**
	 * Make sure the given option is available
	 *
	 * @param $option
	 * @param $value
	 * @return bool
	 */
	protected function is_available_option($option, $value) {
		if (in_array($option, $this->config['available_options'])) {
			if ($option === 'fltr') {
				$filter = explode('|', $value);
				return count($filter) > 0 && in_array($filter[0], $this->config['available_filters']);
			}
			// return false if widths and heights are not in available options
			if ($option === 'w') {
			    if (!in_array($value,$this->config['available_widths'])) return false;
			}
			if ($option === 'h') {
			    if (!in_array($value,$this->config['available_heights'])) return false;
			}
			return true;
		}
		return false;
	}


    /**
     * Returns the value of a $_SERVER variable
     *
     * @param $name name of the $_SERVER variable
     * @param bool $default returned if the variable is not set
     * @return bool|string the value of the variable
     */
    protected function get_server_var($name, $default = false) {
        if (isset($_SERVER[$name])) {
            return trim($_SERVER[$name]);
        }
        return $default;
    }

}
