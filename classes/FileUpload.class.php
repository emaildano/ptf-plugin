<?php

/*
 * $success datamember is either true or false on execution
 * $error holds an array of error messages on $success = false
 */

class FileUpload {
	public $success;
	public $error;
	public $userFileName;
	public $handle;
	protected $generatedName;
	protected $types;
	protected $sizeLimit;
	protected $ext;
	protected $file_id;

	/*
	 * @param	$file_id	The id of the file input in the submitting form
	 * @param	$dir		The directory to upload to with trailing slash.  Example: /server_path/directory_name/
	 * @param	$types		Comma delimited string of accepted file types
	 * @param	$sizeLimit	File size limit in bytes
	 */

	function __construct($dir=null, $file_id='file', $types='csv', $sizeLimit=10485760) {
		$this->file_id = $file_id;
		$this->userFileName = $_FILES[$this->file_id]['name'];
		$this->setTypes($types);
		$this->getExtension();
		$this->setSizeLimit($sizeLimit);
		$this->setGeneratedName();
		
		if ($this->checkType() && $this->checkSize() && $this->checkUploadErrors()) {
			$this->handle = $dir . $this->generatedName . '.' . $this->ext;
			if (move_uploaded_file($_FILES[$file_id]["tmp_name"], $this->handle)) {
				$this->success = true;
				chmod($this->handle, 0664);
			} else {
				$this->logError("Error storing file");
				print_r($this->error);
			}
		}
	}

	protected function setTypes($types) {
		if ($types) {
			$this->types = explode(",", strtolower($types));
		}
	}

	protected function setSizeLimit($size) {
		$this->sizeLimit = $size;
	}

	protected function setGeneratedName() {
		$this->generatedName = md5($this->userFileName . rand(10000, 99999) . date('D, d M Y H:i:s'));
	}

	protected function getExtension() {
		$ext_arr = explode(".", basename($this->userFileName));
		$this->ext = strtolower($ext_arr[count($ext_arr) - 1]); //Get the last extension
	}

	protected function checkType() {
		if ($this->types) {
			if (in_array($this->ext, $this->types)) {
				return true;
			} else {
				$this->logError("This file type is not accepted.");
				return false;
			}
		}
		return true;
	}

	protected function checkSize() {
		if ($_FILES[$this->file_id]["size"] < $this->sizeLimit || !$this->sizeLimit) {
			return true;
		} else {
			$this->logError("File size is too large.");
			return false;
		}
	}

	protected function checkUploadErrors() {
		if ($_FILES[$this->file_id]["error"] > 0) {
			$this->logError($_FILES[$this->file_id]["error"]);
			return false;
		}
		return true;
	}

	protected function logError($error) {
		if (!is_array($this->error)) {
			$this->error = array();
		}
		array_push($this->error, $error);
		$this->success = false;
	}

}