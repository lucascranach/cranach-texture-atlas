<?php

namespace LucasCranach\TextureAtlas;

use Psr\Log\LoggerInterface;

class ImageProcessor
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Skaliert ein Bild auf 40px Breite, proportional
     */
    public function resizeImage(string $imageData, string $filename): ?array
    {
        $image = imagecreatefromstring($imageData);
        if (!$image) {
            $this->logger->warning("Could not create image from data", ['filename' => $filename]);
            return null;
        }
        
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        
        // Berechne neue Dimensionen (40px Breite, proportional)
        $newWidth = 40;
        $newHeight = (int) round(($originalHeight * $newWidth) / $originalWidth);
        
        // Erstelle neues Bild
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Für PNG Transparenz erhalten
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
        imagefill($resizedImage, 0, 0, $transparent);
        
        // Skalieren
        imagecopyresampled(
            $resizedImage, $image,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );
        
        // In String konvertieren
        ob_start();
        imagepng($resizedImage);
        $resizedData = ob_get_contents();
        ob_end_clean();
        
        // Speicher freigeben
        imagedestroy($image);
        imagedestroy($resizedImage);
        
        $this->logger->debug("Image resized", [
            'filename' => $filename,
            'original' => "{$originalWidth}x{$originalHeight}",
            'resized' => "{$newWidth}x{$newHeight}"
        ]);
        
        return [
            'data' => $resizedData,
            'width' => $newWidth,
            'height' => $newHeight
        ];
    }
}
