<?php
namespace FolderManager;
/**
 * Class FolderManager
 * @package FolderManager
 */
class FolderManager
{
    /**
     * @var string
     */
    protected $management_folder;

    /**
     * @var string
     */
    protected $management_file;

    /**
     * @var array
     */
    protected $folder_array = array();

    /**
     * @var array
     */
    protected $file_array = array();

    /**
     * @var array
     */
    protected $protected_files_array = array();
    /**
     * @var array
     */
    protected $errors;

    /**
     * @var array
     */
    protected $messages;

    public function __construct($management_folder=null)
    {
        if(isset($management_folder)){
            $this->setManagementFolder($management_folder);
        }
    }

    /**
     * @param mixed $management_folder
     */
    public function setManagementFolder($management_folder)
    {
        $management_folder = rtrim($management_folder, '/');

        if (!file_exists($management_folder)) {

            return FALSE;
        }
        if (is_dir($management_folder)) {


            $this->management_folder = $management_folder;
        }

        $this->scanFolder();

        return TRUE;

    }

    /**
     * @param $management_file
     * @return bool
     */
    public function setManagementFile($management_file)
    {
        if (!file_exists($management_file)) {
            $this->management_file = null;
            return FALSE;
        }

        $this->management_file = $management_file;

        return TRUE;
    }

    /**
     * @return bool
     */
    public function issetManagementFile()
    {
        return ($this->management_file == null) ? false : true;
    }

    public function issetManagementFolder()
    {
        return ($this->management_folder == null) ? false : true;
    }

    public function getManagementFolder()
    {
        return $this->management_folder;
    }

	/**
	 * @param $file
	 *
	 * @return $this
	 */
    public function protectedFile($file)
    {
        $this->protected_files_array[] = $file;

        return $this;
    }

    /**
     * @param $file
     * @return bool
     */
    public function isProtected($file)
    {

        return in_array($file, $this->protected_files_array);
    }

	/**
	 * @return $this
	 */
    public function reloadManagementFolder()
    {
        $this->setManagementFolder($this->management_folder);

        return $this;
    }

    public function reloadManagementFile()
    {
        $this->setManagementFile($this->management_file);

        return $this;
    }

    /**
     * @param $name
     * @return bool
     */
    public function createFolder($name)
    {
        if ($this->management_folder == null) {
            $this->setError('Management folder not set');
            return false;
        }

        $name = $this->sanitizeFileName($name);

        foreach ($this->folder_array as $folder) {
            if ($folder->name == $name) {
                $this->setError($name . ' Already Exists!');
                return FALSE;
            }
        }

        $new_folder = $this->management_folder . '/' . $name;
        $folderCreated = mkdir($new_folder, 0777, true);

        if ($folderCreated) {
            chmod($new_folder, 0777);
        }
        $this->reloadManagementFolder();
        $this->setMessage($new_folder . ' Created!');
        return $folderCreated;

    }

    /**
     * @param $dir
     * @return bool
     */
    public function delTree($dir)
    {
        if ($this->isProtected($dir)) {
            $this->setError($dir . ' is Protected');
            return false;
        }
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file") && !is_link($dir)) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * @param $filename
     * @return mixed|string
     */
    function sanitizeFileName($filename, $allow_file_name = false)
    {
        if ($allow_file_name) {
            $search = array(' ', '?', '#', "/");
        } else {
            $search = array(' ', '?', '.', '#', "/");
        }

        $filename = trim($filename);
        $filename = str_replace($search, '', $filename);
        $filename = strtolower($filename);
        return $filename;
    }


    /**
     * @param $name
     * @return bool
     */
    public function deleteFolder()
    {

        if ($this->management_folder == null) {
            $this->setError('Management folder not set');
            return false;
        }
        $del_folder = $this->management_folder;
        if (!file_exists($del_folder)) {
            $this->setError($del_folder . ' does not exist!');
            return false;
        }
        $delete = $this->delTree($del_folder);

        $this->reloadManagementFolder();
        $this->setMessage($del_folder . ' deleted');
        return $delete;
    }

    /**
     * @return bool
     */
    public function deleteFile()
    {
        if ($this->management_folder == null) {
            $this->setError('Management_folder_not set');
            return false;
        }
        if ($this->management_file == null) {
            $this->setError('Management file not set');
            return false;
        }

        $delete = unlink($this->management_file);

        if ($delete) {
            $this->setMessage($this->management_file . ' Deleted!');
            $this->reloadManagementFolder();
            $this->reloadManagementFile();
            return true;
        }
        $this->setError($this->management_file . ' Failed to Delete!');
        return false;
    }

    /**
     * @param $newname
     * @return bool
     */
    public function editFile($newname)
    {
        if ($this->management_folder == null) {
            $this->setError('Management folder not set');
            return false;
        }

        if (!(isset($newname) && is_string($newname) && strlen($newname))) {
            $this->setError("Folder names not given correctly!");
            return false;
        }
        $newname = $this->sanitizeFileName($newname, true);

        $new_file = $this->management_folder . '/' . $newname;

        $rename = rename($this->management_file, $new_file);
        if ($rename) {


            $this->setMessage($this->management_file . ' renamed ' . $new_file);
            $this->setManagementFile($new_file);
            $this->reloadManagementFolder();
            return $newname;
        }
        $this->setError('Failed to rename Folder/Directory');

        return $rename;
    }

    /**
     * @param $oldname
     * @param $newname
     * @return bool
     */
    public function editFolder($newname)
    {
        if ($this->management_folder == null) {
            $this->setError('Management folder not set');
            return false;
        }

        if (!(isset($newname) && is_string($newname) && strlen($newname))) {
            $this->setError("Folder names not given correctly!");
            return false;
        }
        $newname = $this->sanitizeFileName($newname);
        foreach ($this->folder_array as $folder) {
            if ($folder->name == $newname) {
                $this->setError($newname . " already exists!");
                return FALSE;
            }
        }

        $new_folder = $this->management_folder . '/../' . $newname;


        if ($this->isProtected($this->management_folder)) {
            $this->setError($this->management_folder . ' is protected!');
            return FALSE;
        }

        $rename = rename($this->management_folder, $new_folder);
        if ($rename) {

            $this->setManagementFolder($new_folder);
            $this->reloadManagementFolder();
            $this->setMessage($this->management_folder . ' renamed ' . $new_folder);
        } else {
            $this->setError('Failed to rename Folder/Directory');
        }
        return $rename;

    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

	/**
	 * @param $error
	 *
	 * @return $this
	 */
    protected function setError($error)
    {

        if (!isset($this->errors)) {
            $this->errors = array();
        }
        $this->errors[] = $error;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMessages()
    {

        return $this->messages;
    }

	/**
	 * @param $message
	 *
	 * @return $this
	 */
    protected function setMessage($message)
    {
        if (!isset($this->messages)) {
            $this->messages = array();
        }
        $this->messages[] = $message;

        return $this;

    }

    /**
     * @return string
     */
    public function getManagementFile()
    {
        return $this->management_file;
    }

	/**
	 * @return $this
	 */
    protected function scanFolder()
    {

        $indir = scandir($this->management_folder);
        $this->folder_array = array();
        $this->file_array = array();
        foreach ($indir as $file) {


            if ($file == '.') {
                continue;
            }
            $file_object = new \stdClass();

            $file_object->permission = $this->getFilePermission($file);

            $file_object->name = $file;
            $file_object->protected = $this->isProtected($this->management_folder . '/' . $file);
            if (is_dir($this->management_folder . '/' . $file)) {
                $this->folder_array[] = $file_object;
            } else {

                $file_object->image_info = @getimagesize($this->management_folder . '/' . $file);
                $file_object->target_name = $this->sanitizeFileName($file);
                $this->file_array[] = $file_object;
            }
        }

        return $this;

    }

    /**
     * @param $file
     * e.g $_FILES['uploaded_file_chosen']
     * @param bool $overwrite
     * @return bool
     */
    public function uploadFile($file, $overwrite = false)
    {

        $target_file = $this->management_folder . '/' . basename($file["name"]);
        if (!$overwrite && file_exists($target_file)) {
            $this->setError('File already exists - specify to overwrite!');
            return false;
        }

        $saved = move_uploaded_file($file["tmp_name"], $target_file);
        if ($saved) {
            $this->setMessage('file uploaded!');
            $this->reloadManagementFolder();
            return true;
        } else {
            $this->setError('File failed to upload!');
            return false;
        }

    }

    /**
     * @param null $file
     * @return array|bool
     */
    public function getImageInfo($file = null)
    {

        if ($file != null) {
            $file = $this->management_folder . '/' . $file;
        } else {
            $file = $this->management_file;
        }

        $info = @getimagesize($file);

        if (is_array($info) && count($info)) {
            return $info;
        }

        return false;
    }

    /**
     * @param null $file
     * @return bool|string
     */
    public function getFilePermission($file = null)
    {
        if ($file != null) {
            $file = $this->management_folder . '/' . $file;
        } elseif (strlen($this->management_file)) {
            $file = $this->management_file;
        } else {
            $file = $this->management_folder;
        }

        if (file_exists($file)) {
            return decoct(fileperms($file) & 0777);
        }
        return false;
    }

    /**
     * @return array
     */
    public function getFolderArray($az = true)
    {
        if ($az && is_array($this->folder_array)) {
            sort($this->folder_array);
        }
        return $this->folder_array;
    }

    /**
     * @return array
     */
    public function getFileArray($az = true)
    {

        if ($az && is_array($this->file_array)) {
            sort($this->file_array);
        }

        return $this->file_array;
    }

    /**
     * @return array
     */
    public function getProtectedFilesArray()
    {
        return $this->protected_files_array;
    }


}