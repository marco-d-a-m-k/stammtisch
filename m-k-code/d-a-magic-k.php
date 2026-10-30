<?php

/**
 * Plugin Name: d-a-magic-k
 * Description: Converts uploaded images to WebP (90% quality) using ImageMagick, skipping GIFs and SVGs and resizing images wider than 3000px.
 * Version: 1.337
 * Author: d-a-m-k
 */

if (!class_exists('Imagick')) {
    error_log('Imagick class not found. Make sure the ImageMagick extension is installed and enabled.');
    return;
}

add_filter('wp_handle_upload', 'convert_image_to_webp');

function convert_image_to_webp($upload)
{
    $file_path = $upload['file'];
    $file_type = $upload['type'];

    $skip_types = ['image/webp', 'image/gif', 'image/svg+xml'];

    if (strpos($file_type, 'image') !== false && !in_array($file_type, $skip_types, true)) {
        try {
            $image = new Imagick($file_path);

            $width = $image->getImageWidth();
            $height = $image->getImageHeight();

            $max_dim = 3000;
            if ($width > $max_dim || $height > $max_dim) {
                if ($width >= $height) {
                    $new_height = intval(($height / $width) * $max_dim);
                    $image->resizeImage($max_dim, $new_height, Imagick::FILTER_LANCZOS, 1);
                } else {
                    $new_width = intval(($width / $height) * $max_dim);
                    $image->resizeImage($new_width, $max_dim, Imagick::FILTER_LANCZOS, 1);
                }
            }

            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(90);

            $webp_path = preg_replace('/\.[^.\s]{3,4}$/', '.webp', $file_path);
            $image->writeImage($webp_path);

            $image->clear();
            $image->destroy();

            $upload['file'] = $webp_path;
            $upload['url'] = str_replace(basename($file_path), basename($webp_path), $upload['url']);
            $upload['type'] = 'image/webp';

            unlink($file_path);
        } catch (Exception $e) {
            error_log('ImageMagick error: ' . $e->getMessage());
        }
    }

    return $upload;
}
