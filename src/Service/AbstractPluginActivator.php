<?php

namespace WonderWp\Component\PluginSkeleton\Service;

use WonderWp\Component\HttpFoundation\Result;

abstract class AbstractPluginActivator implements ActivatorInterface
{
    /** @var string */
    protected $version;

    /**
     * @param string $version
     */
    public function __construct($version)
    {
        $this->version = $version;
    }

    /** @inheritdoc */
    public function activate()
    {
    }

    /**
     * @param string $srcLangFolder
     *
     * @return Result
     */
    protected function copyLanguageFiles($srcLangFolder)
    {
        $destLangFolder = WP_LANG_DIR . DIRECTORY_SEPARATOR . 'plugins';
        if (is_dir($srcLangFolder)) {
            $langFiles = glob($srcLangFolder . '/*');
            if (!empty($langFiles)) {
                $errors = [];
                foreach ($langFiles as $langFile) {
                    if (!file_exists($destLangFolder . DIRECTORY_SEPARATOR . basename($langFile))) {
                        $copied = copy($langFile, $destLangFolder . DIRECTORY_SEPARATOR . basename($langFile));
                        if(!$copied){
                            $errors[] = 'Copy failed for file '.$langFile;
                        }
                    }
                }
                if(!empty($errors)){
                    $result = new Result(500,['msg'=>'Some files could not be copied','errors'=>$errors]);
                } else {
                    $result = new Result(200,['msg'=>'All files copied successfully']);
                }
            } else {
                $result = new Result(200,['msg'=>'No language file found in source folder, nothing to copy','srcFolder'=>$srcLangFolder]);
            }
        } else {
            $result = new Result(200,['msg'=>'Source lang folder not found, nothing to copy','srcFolder'=>$srcLangFolder]);
        }
        return $result;
    }
}
