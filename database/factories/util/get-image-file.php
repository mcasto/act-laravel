<?php

function getImageFile($imagePath)
{
    $imageList = glob($imagePath . "/*.{jpg,jpeg,png}", GLOB_BRACE);


    if (count($imageList) == 0) {
        $fileList = glob($imagePath . "/*{png,jpg,jpeg}", GLOB_BRACE);

        foreach ($fileList as $file) {
            $newName = $imagePath . "/" . uniqid() . "." . pathinfo($file, PATHINFO_EXTENSION);

            rename($file, $newName);
        }

        $imageList = glob($imagePath . "/*.{jpg,jpeg,png}", GLOB_BRACE);
    }

    shuffle($imageList);
    $imageFile = array_shift($imageList);

    return $imageFile;
}
