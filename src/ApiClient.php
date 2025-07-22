<?php

namespace LucasCranach\TextureAtlas;

use Psr\Log\LoggerInterface;

class ApiClient
{
    private string $username;
    private string $password;
    private LoggerInterface $logger;
    
    public function __construct(string $username, string $password, LoggerInterface $logger)
    {
        $this->username = $username;
        $this->password = $password;
        $this->logger = $logger;
    }
    
    /**
     * Holt alle Werke aus der Lucas Cranach API
     */
    public function fetchWorks(): array
    {
        $url = 'https://mivs02.gm.fh-koeln.de/works?language=de&is_published=true&size=5000';
        
        $this->logger->info('Fetching works from API', ['url' => $url]);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'Lucas-Cranach-Texture-Atlas/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        
        if ($error) {
            throw new \Exception("cURL Error: {$error}");
        }
        
        if ($httpCode !== 200) {
            throw new \Exception("HTTP Error: {$httpCode}");
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("JSON Error: " . json_last_error_msg());
        }
        
        $this->logger->info('Successfully fetched works', ['count' => count($data)]);
        
        return $data['data']['results'] ?? [];
    }
    
    /**
     * Lädt ein Bild von einer URL herunter
     */
    public function downloadImage(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Lucas-Cranach-Texture-Atlas/1.0'
        ]);
        
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            $this->logger->warning("cURL Error downloading image", ['url' => $url, 'error' => $error]);
            return null;
        }
        
        if ($httpCode === 404) {
            $this->logger->warning("Image not found (404)", ['url' => $url]);
            return null;
        }
        
        if ($httpCode !== 200) {
            $this->logger->warning("HTTP Error downloading image", ['url' => $url, 'code' => $httpCode]);
            return null;
        }
        
        return $imageData;
    }
}
