<?php

declare(strict_types=1);

namespace Vibi\T3zip\Utils;

use ZipArchive;

class Filesystem
{
    public function recursiveCopyFolder(string $src, string $dst): bool
    {
        $dir = \opendir($src);
        if (!$dir) {
            return false;
        }

        if (!\is_dir($dst)) {
            \mkdir($dst);
        }

        while (($file = \readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (\is_dir($src . '/' . $file)) {
                    $this->recursiveCopyFolder($src . '/' . $file, $dst . '/' . $file);
                } else {
                    \copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);

        return true;
    }

    public function getFilesRecursive(string $abs_path_folder): array
    {
        $result = [];
        $root = scandir($abs_path_folder);

        if (!$root) {
            return [];
        }

        foreach ($root as $value) {
            if ($value === '.' || $value === '..') {
                continue;
            }

            $current_file = $abs_path_folder . '/' . $value;
            if (is_file($current_file)) {
                $result[] = $current_file;
                continue;
            }

            foreach ($this->getFilesRecursive($current_file) as $value) {
                $result[] = $value;
            }
        }

        return $result;
    }

    public function extractZipToTempAndReturnTempName(string $abs_path_to_zipfile): string|bool
    {
        $tempFileFolder = "";
        $zip = new ZipArchive();
        $zip_opened = $zip->open($abs_path_to_zipfile); // $zip->open($abs_path_extract_folder)
        if ($zip_opened === true) {
            $tempFileFolder = tempnam(sys_get_temp_dir(), 'unzip-');
            if ($tempFileFolder !== false) {
                if (file_exists($tempFileFolder)) {
                    unlink($tempFileFolder);
                }

                $temp_dir_created = mkdir($tempFileFolder);

                if ($temp_dir_created) {
                    $zip->extractTo($tempFileFolder);
                }
            }

            $zip->close();
        }

        return $tempFileFolder;
    }

    /**
     * recursive deletes a directory
     *
     * @param string $dir
     * @return bool
     */
    public function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        $scandir = scandir($dir);
        if (!$scandir) {
            return false;
        }

        foreach ($scandir as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }
}
