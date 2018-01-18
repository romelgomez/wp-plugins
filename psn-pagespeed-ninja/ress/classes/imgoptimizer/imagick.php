<?php
/*
 * RESSIO Responsive Server Side Optimizer
 * https://github.com/ressio/
 *
 * @copyright   Copyright (C) 2013-2018 Kuneri, Ltd. All rights reserved.
 * @license     GNU General Public License version 2
 */

/**
 * Images minification using ImageMagick
 */
class Ressio_ImgOptimizer_IMagick extends Ressio_ImgOptimizer
{
    public $supported_exts = array('jpg', 'gif', 'png');

    public function __construct()
    {
        if (!extension_loaded('imagick')) {
            throw new ERessio_Exception('ImageMagick extension is not loaded.');
        }
        if (count(Imagick::queryFormats('webp'))) {
            $this->supported_exts[] = 'webp';
        }
    }

    /**
     * @param $src_imagepath string
     * @return bool
     * @throws ERessio_UnknownDiKey
     */
    public function run($src_imagepath)
    {
        // @todo extract common code from gd/exec/imagick optimizers to an abstract base class

        $src_ext = pathinfo($src_imagepath, PATHINFO_EXTENSION);
        if ($src_ext === 'jpeg') {
            $src_ext = 'jpg';
        }

        if (!in_array($src_ext, $this->supported_exts, true)) {
            return false;
        }

        $fs = $this->di->filesystem;

        if (!$fs->isFile($src_imagepath)) {
            return false;
        }

        $src_filesize = $fs->size($src_imagepath);

        // @todo skip optimization of small files [performance]

        $src_timestamp = $fs->getModificationTime($src_imagepath);

        $orig_imagepath = $src_imagepath . $this->config->img->origsuffix;
        if ($fs->isFile($orig_imagepath) && $src_timestamp === $fs->getModificationTime($orig_imagepath)) {
            return true;
        }

        parent::backup($src_imagepath, $src_timestamp, $orig_imagepath);

        $src_image = false;
        switch ($src_ext) {
            case 'jpg':
            case 'gif':
            case 'png':
            case 'webp':
                $src_image = new Imagick($src_imagepath);
                break;
        }

        if ($src_image === false) {
            return false;
        }

        if ($src_image->getImageIterations()) {
            // animated gif
            return false;
        }

        $src_image->stripImage();
        $src_image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
        $src_image->setBackgroundColor(new ImagickPixel('transparent'));

        switch ($src_ext) {
            case 'jpg':
                $src_image->setImageCompressionQuality($this->di->config->img->jpegquality);
                break;
        }
        $data = $src_image->getImageBlob();

        $src_image->clear();

        if (strlen($data) >= $src_filesize) {
            return false;
        }

        $ret = $fs->putContents($src_imagepath, $data);
        $fs->touch($src_imagepath, $src_timestamp);
        return $ret;
    }
}