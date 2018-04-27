<?php

/**
 * Class Entrada_LearningObject_Files
 */
class Entrada_LearningObject_Files {
    /**
     * @param $file_realpath
     * @param $mime_type
     * @param string $thumb_width
     * @return bool
     */
    public static function generateLearningObjectThumbnail($file_realpath, $mime_type, $thumb_width = "150") {
        $thumbnail_realpath = substr($file_realpath, 0, strripos($file_realpath, "/") + 1) . "thumbnails/" . substr($file_realpath, strripos($file_realpath, "/") + 1, strlen($file_realpath));

        switch ($mime_type) {
            case "image/jpeg":
                $image = imagecreatefromjpeg($file_realpath);
                break;
            case "image/png":
                $image = imagecreatefrompng($file_realpath);
                break;
            case "image/gif":
                $image = imagecreatefromgif($file_realpath);
                break;
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        $new_width  = $thumb_width;
        $new_height = floor($height * ($thumb_width / $width));

        $tmp_img    = imagecreatetruecolor($new_width, $new_height);
        $background = imagecolorallocate($tmp_img, 0, 0, 0);

        imagecolortransparent($tmp_img, $background);
        imagealphablending($tmp_img, false);
        imagesavealpha($tmp_img, true);

        imagecopyresized($tmp_img, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        if (imagepng($tmp_img, $thumbnail_realpath)) {
            return true;
        }

        return false;
    }
}
