#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use LucasCranach\TextureAtlas\ApiClient;
use LucasCranach\TextureAtlas\ImageProcessor;
use LucasCranach\TextureAtlas\GridPacker;
use LucasCranach\TextureAtlas\TextureAtlasGenerator;

try {
    // .env laden
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    
    // Logger konfigurieren
    $logger = new Logger('texture-atlas');
    
    // Console Handler
    $consoleHandler = new StreamHandler('php://stdout', Logger::INFO);
    $logger->pushHandler($consoleHandler);
    
    // File Handler
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $fileHandler = new RotatingFileHandler(
        $logDir . '/texture-atlas.log',
        0,
        Logger::DEBUG
    );
    $logger->pushHandler($fileHandler);
    
    // Konfiguration validieren
    $username = $_ENV['API_USERNAME'] ?? null;
    $password = $_ENV['API_PASSWORD'] ?? null;
    $outputDir = $_ENV['ATLAS_OUTPUT_DIR'] ?? './output';
    
    if (!$username || !$password) {
        throw new Exception("API credentials not found. Please check your .env file.");
    }
    
    $logger->info("Starting Lucas Cranach Texture Atlas Generator");
    $logger->info("Configuration", [
        'username' => $username,
        'output_dir' => $outputDir
    ]);
    
    // Services erstellen
    $apiClient = new ApiClient($username, $password, $logger);
    $imageProcessor = new ImageProcessor($logger);
    $gridPacker = new GridPacker($logger, 4); // 4px padding
    
    $generator = new TextureAtlasGenerator(
        $apiClient,
        $imageProcessor,
        $gridPacker,
        $logger,
        $outputDir
    );
    
    // Atlas generieren
    $startTime = microtime(true);
    $generator->generate();
    $endTime = microtime(true);
    
    $duration = round($endTime - $startTime, 2);
    $logger->info("Generation completed successfully", ['duration' => "{$duration}s"]);
    
    echo "\n✅ Texture Atlas erfolgreich generiert!\n";
    echo "📁 Output: {$outputDir}/\n";
    echo "⏱️  Dauer: {$duration}s\n\n";
    
} catch (Exception $e) {
    if (isset($logger)) {
        $logger->error("Generation failed", ['error' => $e->getMessage()]);
    }
    
    echo "\n❌ Fehler: " . $e->getMessage() . "\n\n";
    exit(1);
}
