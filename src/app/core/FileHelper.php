<?php


namespace app\core;


class FileHelper
{


    public static function isExistFile($filename) {
        return file_exists($filename) && is_file($filename);
    }

    public static function isDirectory($dir) {
        if (file_exists($dir) && is_file($dir))
            return false;
        return true;
    }
    public static function Load($filename)
    {
        if (self::isExistFile($filename)) {
            $fp = fopen($filename, 'r');
            $content = fread($fp, filesize($filename));
            fclose($fp);
            return $content;
        }
        return false;
    }


    public static function save($filename, $content, $force = false)
    {
        if (file_exists($filename) && !$force) {
            Std::out("file already exists: $filename\ntry run app with --overwrite" . PHP_EOL, Std::FG_YELLOW);
            return;
        }

        if(!file_exists($filename)){
            $dirname = dirname($filename);
            if (!file_exists($dirname)) {
                Std::err("creating $dirname\n");
                mkdir($dirname, 0755, true);
            }
        }
        $fp = fopen($filename,'w+');
        fwrite($fp, $content);
        fclose($fp);
    }

    public static function findMod($output) {
        $level = 10;
        while ($level && strlen($output) > 1) {
            if (file_exists("$output/go.mod")) {
                return [$output, self::Load("$output/go.mod")];
            }
            $level++;
            $output = dirname($output);
        }
        return [false,false];
    }
}