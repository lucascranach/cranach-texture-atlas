# Lucas Cranach Timeline Texture Atlas

PHP Service zur Erstellung von Texture Atlas aus der Lucas Cranach API.

## Installation

```bash
composer install
cp .env.example .env
```

## Konfiguration

Bearbeiten Sie die `.env` Datei:

```env
API_USERNAME=ihr_benutzername
API_PASSWORD=ihr_passwort
ATLAS_OUTPUT_DIR=./output
LOG_LEVEL=INFO
```

## Verwendung

```bash
php bin/generate-atlas.php
```

## Features

- Lädt ~3000 Bilder aus der Lucas Cranach API
- Skaliert Bilder auf 40px Breite (proportional)
- Erstellt Grid-Layout mit 4px Abstand
- Generiert Texture Atlas in **3 Formaten**:
  - **PNG** - Mit Transparenz, verlustfrei
  - **JPEG** - Komprimiert, weißer Hintergrund
  - **WebP** - Moderne Kompression (falls verfügbar)
- Erstellt JSON mit Metadaten und Positionen
- Umfassendes Logging
- 404-Fehler werden übersprungen und geloggt

## Output

Das Script erstellt folgende Dateien:

- `texture_atlas.png` - PNG Atlas (mit Transparenz)
- `texture_atlas.jpg` - JPEG Atlas (weißer Hintergrund)
- `texture_atlas.webp` - WebP Atlas (falls unterstützt)
- `texture_atlas.json` - Metadaten mit Positionen

### JSON Format

```json
{
  "atlas": {
    "width": 2048,
    "height": 2048,
    "formats": {
      "png": "texture_atlas.png",
      "jpg": "texture_atlas.jpg", 
      "webp": "texture_atlas.webp"
    },
    "generated_at": "2025-07-21T...",
    "total_images": 2856
  },
  "images": [
    {
      "filename": "image.jpg",
      "width": 40,
      "height": 60,
      "x": 4,
      "y": 4,
      "original_url": "https://...",
      "entity_type": "Gemälde",
      "work_id": "...",
      "title": "..."
    }
  ]
}
```

## Systemanforderungen

- PHP >= 8.0
- GD Extension
- cURL Extension
- Composer

## Logs

Logs werden gespeichert in:
- Console Output (INFO Level)
- `logs/texture-atlas.log` (DEBUG Level)
