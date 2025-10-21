<?php
   // Load an image
    $canvas = new Imagick(__DIR__ . '/0_smpl_testfile.jpg');

    // Create a mask image, that is the same size as the canvas image.
    $mask = new Imagick();
    $mask->newPseudoImage(
        $canvas->getImageWidth(),
        $canvas->getImageHeight(),
        'xc:black'
    );

    // Draw a white filled in circle on the mask image
    $drawing = new ImagickDraw();
    $drawing->setBorderColor('white');
    $drawing->setFillColor('white');
    $drawing->circle(
        $mask->getImageWidth() / 2,
        $mask->getImageHeight() / 2,
        2 * $mask->getImageWidth() / 3,
        $mask->getImageHeight() / 2,
    );
    $mask->drawImage($drawing);

    // Use the $mask image as the ...mask.
    $canvas->setImageMask($mask, \Imagick::PIXELMASK_WRITE);

    // Apply blur to the loaded image. Only the bit in white will be affected.
    $canvas->blurImage(15, 4, \Imagick::CHANNEL_ALL);

    // output the image
    header("Content-Type: image/jpeg");
    echo $canvas->getImageBlob();
?>