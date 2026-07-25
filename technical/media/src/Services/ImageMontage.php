<?php

namespace Technical\Media\Services;

class ImageMontage
{
    /**
     * Lay the given images out on a transparent grid and write the result as a PNG.
     *
     * @param  list<string>  $sourcePaths
     */
    public function compose(array $sourcePaths, string $destinationPath, int $cellSize = 512): bool
    {
        if ($sourcePaths === [] || $cellSize < 1) {
            return false;
        }

        $columns = max(1, (int) ceil(sqrt(count($sourcePaths))));
        $rows = max(1, (int) ceil(count($sourcePaths) / $columns));

        $canvas = imagecreatetruecolor($columns * $cellSize, $rows * $cellSize);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);

        if ($transparent === false) {
            imagedestroy($canvas);

            return false;
        }

        imagefilledrectangle($canvas, 0, 0, imagesx($canvas), imagesy($canvas), $transparent);
        imagealphablending($canvas, true);

        foreach ($sourcePaths as $cellIndex => $sourcePath) {
            $this->drawCell($canvas, $sourcePath, $cellIndex, $columns, $cellSize);
        }

        $written = imagepng($canvas, $destinationPath);
        imagedestroy($canvas);

        return $written;
    }

    /**
     * Draw one image, scaled to fit its cell without distortion.
     */
    private function drawCell(\GdImage $canvas, string $sourcePath, int $cellIndex, int $columns, int $cellSize): void
    {
        $source = @imagecreatefromstring((string) file_get_contents($sourcePath));

        if ($source === false) {
            return;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min($cellSize / $sourceWidth, $cellSize / $sourceHeight);

        $targetWidth = (int) round($sourceWidth * $scale);
        $targetHeight = (int) round($sourceHeight * $scale);

        $originX = ($cellIndex % $columns) * $cellSize + (int) (($cellSize - $targetWidth) / 2);
        $originY = intdiv($cellIndex, $columns) * $cellSize + (int) (($cellSize - $targetHeight) / 2);

        imagecopyresampled(
            $canvas,
            $source,
            $originX,
            $originY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        imagedestroy($source);
    }
}
