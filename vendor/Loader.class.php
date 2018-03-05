<?php

class Loader {

	public function helper($helpers = array()){
		foreach ($this->_prep_filename($helpers, '_helper') as $helper){
			if (isset($this->_helpers[$helper])) {
				continue;
			}
			// app helper
			$base_helper = BASE_PATH . 'helpers/'.$helper.'.php';
			if ( ! file_exists($base_helper)){
				exit('Unable to load the requested file: helpers/'.$helper.'.php');
			}
			include_once($base_helper);
			$this->_helpers[$helper] = TRUE;
			continue;
		}
	}

	public function library($library = array()){

	}

	public function language($language = array()){

	}

	/**
	 * Prep filename
	 *
	 * This function prepares filenames of various items to
	 * make their loading more reliable.
	 *
	 * @param string|string[]	$filename Filename(s)
	 * @param string $extension Filename extension
	 * @return array
	 */
	protected function _prep_filename($filename, $extension)
	{
		if ( ! is_array($filename))
		{
			return array(strtolower(str_replace(array($extension, '.php'), '', $filename).$extension));
		}
		else
		{
			foreach ($filename as $key => $val)
			{
				$filename[$key] = strtolower(str_replace(array($extension, '.php'), '', $val).$extension);
			}

			return $filename;
		}
	}
}
