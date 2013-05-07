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
        $this->config = array_merge(array(
            'basePath' => $base_path,
            'corePath' => $core_path,
            'modelPath' => $core_path . 'model/',
            'cachePath' => $cache_path,
            'baseUrl' => $base_url,
            'autoCreate' => $auto_create,
            'clearCache' => $clear_cache,
			'available_options' => $available_options,
			'available_filters' => $available_filters
        ), $config);
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
        $path = rtrim($this->config['baseUrl'], '/');
        $options = explode('&', $options);
        array_walk($options, array($this, 'encode_url'));
        foreach ($options as $opt) {
            if (substr($opt, 0, 4) == 'src/') {
                $image = substr($opt, 4);
            } else {
                $path .= "/$opt";
            }
        }
        $path .= "/src/$image";
        return $path;
    }


    /**
     * Clears the phpthumbsup cache.
     *
     * @param bool $force set to true to ignore phpthumbsup.clear_cache setting
     */
    public function clear_cache($force = false) {
        if ($force || $this->config['clearCache']) {
            foreach (scandir($this->config['cachePath']) as $file) {
                if ($file != '.' && $file != '..') {
                    unlink($this->config['cachePath'] . $file);
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
                $path = $this->get_thumb_path($options);
                $this->create_thumb($path, $options);
                $this->display($path);
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
        $options = array();
        $thumb_args = explode('/src/', trim(substr($url, strlen($base_url)), '/'));
        $option_args = explode('/', $thumb_args[0]);
		// since we're coming from $_REQUEST or an already decoded url specified by the user,
		// we don't need to decode again (could cause security concerns)
        //
        // UPDATE: we need to use $_SERVER['REQUEST_URI'] and manually decode in case a filter
        //         contains a "/" in it, as we have to explode before urldecode
        array_walk($option_args, array($this, 'decode_url'));
        for ($i = 0, $j = count($option_args) - 1;  $i < $j; $i += 2) {
            // if a filter name ends with [] it is an array
            if (preg_match('/(.+)\[\]$/', $option_args[$i], $m)) {
				if ($this->is_available_option($m[1], $option_args[$i + 1])) {
					if (!isset($options[$m[1]])) {
						$options[$m[1]] = array();
					}
					$options[$m[1]][] = $option_args[$i + 1];
				}
            } else if ($this->is_available_option($option_args[$i], $option_args[$i + 1])) {
                $options[$option_args[$i]] = $option_args[$i + 1];
            }
        }
        $options['src'] = $thumb_args[1];
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
        list($n, $v) = explode('=', $val);
        $val = urlencode($n) . '/' . urlencode($v);
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
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename=' . preg_replace('/\.[^.]+(\.[^.]+)$/', '$1', basename($file)));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: public');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
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
			return true;
		}
		return false;
	}

}
