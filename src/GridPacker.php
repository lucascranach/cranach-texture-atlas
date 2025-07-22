<?php

namespace LucasCranach\TextureAtlas;

use Psr\Log\LoggerInterface;

class GridPacker
{
    private LoggerInterface $logger;
    private int $padding;
    
    public function __construct(LoggerInterface $logger, int $padding = 4)
    {
        $this->logger = $logger;
        $this->padding = $padding;
    }
    
    /**
     * Berechnet optimale Atlas-Größe und Positionen für die Bilder
     */
    public function calculateLayout(array $images): array
    {
        $totalImages = count($images);
        
        // Berechne Grid-Dimensionen (möglichst quadratisch)
        $cols = (int) ceil(sqrt($totalImages));
        $rows = (int) ceil($totalImages / $cols);
        
        // Finde maximale Bild-Dimensionen
        $maxWidth = 0;
        $maxHeight = 0;
        
        foreach ($images as $image) {
            $maxWidth = max($maxWidth, $image['width']);
            $maxHeight = max($maxHeight, $image['height']);
        }
        
        // Berechne Atlas-Dimensionen
        $atlasWidth = ($cols * $maxWidth) + (($cols + 1) * $this->padding);
        $atlasHeight = ($rows * $maxHeight) + (($rows + 1) * $this->padding);
        
        $this->logger->info("Calculated atlas layout", [
            'images' => $totalImages,
            'grid' => "{$cols}x{$rows}",
            'cell_size' => "{$maxWidth}x{$maxHeight}",
            'atlas_size' => "{$atlasWidth}x{$atlasHeight}",
            'padding' => $this->padding
        ]);
        
        // Berechne Positionen
        $positions = [];
        $index = 0;
        
        for ($row = 0; $row < $rows && $index < $totalImages; $row++) {
            for ($col = 0; $col < $cols && $index < $totalImages; $col++) {
                $x = $this->padding + ($col * ($maxWidth + $this->padding));
                $y = $this->padding + ($row * ($maxHeight + $this->padding));
                
                $positions[$index] = [
                    'x' => $x,
                    'y' => $y
                ];
                
                $index++;
            }
        }
        
        return [
            'atlas_width' => $atlasWidth,
            'atlas_height' => $atlasHeight,
            'cell_width' => $maxWidth,
            'cell_height' => $maxHeight,
            'cols' => $cols,
            'rows' => $rows,
            'positions' => $positions
        ];
    }
}
