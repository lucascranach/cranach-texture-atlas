<?php

namespace LucasCranach\TextureAtlas;

use Psr\Log\LoggerInterface;

class TextureAtlasGenerator
{
    private ApiClient $apiClient;
    private ImageProcessor $imageProcessor;
    private GridPacker $gridPacker;
    private LoggerInterface $logger;
    private string $outputDir;
    
    public function __construct(
        ApiClient $apiClient,
        ImageProcessor $imageProcessor,
        GridPacker $gridPacker,
        LoggerInterface $logger,
        string $outputDir
    ) {
        $this->apiClient = $apiClient;
        $this->imageProcessor = $imageProcessor;
        $this->gridPacker = $gridPacker;
        $this->logger = $logger;
        $this->outputDir = $outputDir;
    }
    
    /**
     * Generiert den Texture Atlas
     */
    public function generate(): void
    {
        $this->logger->info("Starting texture atlas generation");
        
        // Stelle sicher, dass Output-Verzeichnis existiert
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
        
        // 1. API-Daten laden (deutsch und englisch)
        $baseUrl = 'https://mivs02.gm.fh-koeln.de/works?is_published=true&size=5000&language=';
        
        $worksDE = $this->apiClient->fetchWorks($baseUrl . 'de');
        $worksEN = $this->apiClient->fetchWorks($baseUrl . 'en');
                
        $this->logger->info('Loaded works in both languages', [
            'german' => count($worksDE),
            'english' => count($worksEN),
        ]);

        
        // 2. Bilder verarbeiten
        $processedImages = [];
        $atlasData = [];
        
        foreach ($worksDE as $index => $work) {
            var_dump($work['img_src']);

            if (!isset($work['img_src']) || empty($work['img_src'])) {
                continue;
            }
            
            // Nimm das erste Bild
            $imageUrl = $work['img_src'] ?? null;
            if (!$imageUrl) {
                $this->logger->warning("No image URL found", ['work_id' => $work['id'] ?? 'unknown']);
                continue;
            }
            
            $this->logger->info("Processing image", [
                'index' => $index + 1,
                'total' => count($worksDE),
                'url' => $imageUrl
            ]);
            
            // Bild herunterladen
            $imageData = $this->apiClient->downloadImage($imageUrl);
            if (!$imageData) {
                continue;
            }
            
            // Bild skalieren
            $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
            $processedImage = $this->imageProcessor->resizeImage($imageData, $filename);
            if (!$processedImage) {
                continue;
            }
            
            $processedImages[] = $processedImage;
            
            // Metadaten sammeln
            $atlasData[] = [
                'filename' => $filename,
                'width' => $processedImage['width'],
                'height' => $processedImage['height'],
                'original_url' => $imageUrl,
                'entity_type' => $work['entity_type'] ?? 'unknown',
                'inventory_number	' => $work['inventory_number'] ?? null,
                'title' => [
                    'de' => $work['title'] ?? null,
                    'en' => $worksEN[$index]['title'] ?? null
                ],
                'sorting_number' => $work['sorting_number'] ?? null
            ];
            $this->logger->info("atlasData", $atlasData);
        }

        
        if (empty($processedImages)) {
            throw new \Exception("No images were successfully processed");
        }
        
        $this->logger->info("Successfully processed images", ['count' => count($processedImages)]);
        
        // 3. Layout berechnen
        $layout = $this->gridPacker->calculateLayout($processedImages);
        
        // 4. Atlas erstellen
        $atlas = imagecreatetruecolor($layout['atlas_width'], $layout['atlas_height']);
        
        // Transparenter Hintergrund
        imagealphablending($atlas, false);
        imagesavealpha($atlas, true);
        $transparent = imagecolorallocatealpha($atlas, 255, 255, 255, 127);
        imagefill($atlas, 0, 0, $transparent);
        imagealphablending($atlas, true);
        
        // 5. Bilder in Atlas einfügen
        foreach ($processedImages as $index => $image) {
            $position = $layout['positions'][$index];
            $imageResource = imagecreatefromstring($image['data']);
            
            imagecopy(
                $atlas,
                $imageResource,
                $position['x'],
                $position['y'],
                0,
                0,
                $image['width'],
                $image['height']
            );
            
            imagedestroy($imageResource);
            
            // Position zu Metadaten hinzufügen
            $atlasData[$index]['x'] = $position['x'];
            $atlasData[$index]['y'] = $position['y'];
        }
        
        // 6. Atlas in verschiedenen Formaten speichern
        $this->saveAtlasInMultipleFormats($atlas, $layout);
        
        imagedestroy($atlas);
        
        // 7. JSON-Datei speichern
        $jsonData = [
            'atlas' => [
                'width' => $layout['atlas_width'],
                'height' => $layout['atlas_height'],
                'formats' => [
                    'png' => 'texture_atlas.png',
                    'jpg' => 'texture_atlas.jpg',
                    'webp' => 'texture_atlas.webp'
                ],
                'generated_at' => date('c'),
                'total_images' => count($processedImages)
            ],
            'images' => $atlasData
        ];
        
        $jsonPath = $this->outputDir . '/texture_atlas.json';
        file_put_contents($jsonPath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->logger->info("Texture atlas generation completed", [
            'png_file' => $this->outputDir . '/texture_atlas.png',
            'jpg_file' => $this->outputDir . '/texture_atlas.jpg',
            'webp_file' => $this->outputDir . '/texture_atlas.webp',
            'json_file' => $jsonPath,
            'images_processed' => count($processedImages)
        ]);
    }
    
    /**
     * Speichert den Atlas in verschiedenen Formaten
     */
    private function saveAtlasInMultipleFormats($atlas, array $layout): void
    {
        // PNG (mit Transparenz)
        $pngPath = $this->outputDir . '/texture_atlas.png';
        $pngSuccess = imagepng($atlas, $pngPath, 9); // Maximale Kompression
        
        if (!$pngSuccess) {
            throw new \Exception("Failed to save PNG atlas");
        }
        
        $this->logger->info("PNG atlas saved", ['file' => $pngPath]);
        
        // JPEG (mit weißem Hintergrund, da JPEG keine Transparenz unterstützt)
        $jpegAtlas = imagecreatetruecolor($layout['atlas_width'], $layout['atlas_height']);
        $white = imagecolorallocate($jpegAtlas, 255, 255, 255);
        imagefill($jpegAtlas, 0, 0, $white);
        
        // Atlas auf weißen Hintergrund kopieren
        imagecopy($jpegAtlas, $atlas, 0, 0, 0, 0, $layout['atlas_width'], $layout['atlas_height']);
        
        $jpegPath = $this->outputDir . '/texture_atlas.jpg';
        $jpegSuccess = imagejpeg($jpegAtlas, $jpegPath, 90); // 90% Qualität
        imagedestroy($jpegAtlas);
        
        if (!$jpegSuccess) {
            $this->logger->warning("Failed to save JPEG atlas");
        } else {
            $this->logger->info("JPEG atlas saved", ['file' => $jpegPath]);
        }
        
        // WebP (wenn verfügbar)
        if (function_exists('imagewebp')) {
            $webpPath = $this->outputDir . '/texture_atlas.webp';
            $webpSuccess = imagewebp($atlas, $webpPath, 90); // 90% Qualität
            
            if (!$webpSuccess) {
                $this->logger->warning("Failed to save WebP atlas");
            } else {
                $this->logger->info("WebP atlas saved", ['file' => $webpPath]);
            }
        } else {
            $this->logger->warning("WebP support not available, skipping WebP output");
        }
    }
}
