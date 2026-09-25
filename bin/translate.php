#!/usr/bin/env php
<?php
/**
 * CLI tool for auto-translating missing keys
 * Usage: php translate.php <language_code>
 * Example: php translate.php ru
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/Config.php';
require_once __DIR__ . '/../inc/DB.php';
require_once __DIR__ . '/../inc/Translator.php';

Config::load(__DIR__ . '/../.env');

if ($argc < 2) {
    echo "Usage: php translate.php <language_code>\n";
    echo "Available languages: ru, es, de, fr, zh\n";
    echo "\nExample:\n";
    echo "  php translate.php ru     # Translate to Russian\n";
    echo "  php translate.php all    # Translate all languages\n";
    exit(1);
}

$targetLang = $argv[1];

// Initialize (without session)
session_start();
Translator::init();

if ($targetLang === 'all') {
    $languages = ['ru', 'es', 'de', 'fr', 'zh'];
    
    echo "Auto-translating all languages...\n\n";
    
    foreach ($languages as $lang) {
        echo "Translating to $lang...\n";
        $stats = Translator::translateMissingKeys($lang);
        
        echo "  Total keys: {$stats['total']}\n";
        echo "  Translated: {$stats['translated']}\n";
        echo "  Failed: {$stats['failed']}\n";
        echo "  Progress: " . round(($stats['translated'] / $stats['total']) * 100, 2) . "%\n\n";
    }
    
    echo "✓ All translations completed!\n";
} else {
    if (!Translator::isSupported($targetLang)) {
        echo "Error: Language '$targetLang' is not supported\n";
        echo "Available languages: en, ru, es, de, fr, zh\n";
        exit(1);
    }
    
    if ($targetLang === 'en') {
        echo "Error: English is the source language, no translation needed\n";
        exit(1);
    }
    
    echo "Auto-translating to $targetLang...\n";
    
    $stats = Translator::translateMissingKeys($targetLang);
    
    echo "\nTranslation Statistics:\n";
    echo "  Total keys: {$stats['total']}\n";
    echo "  Translated: {$stats['translated']}\n";
    echo "  Failed: {$stats['failed']}\n";
    echo "  Progress: " . round(($stats['translated'] / $stats['total']) * 100, 2) . "%\n";
    
    if ($stats['failed'] > 0) {
        echo "\n⚠ Some translations failed. This might be due to API rate limits.\n";
        echo "  Try running the script again later.\n";
    } else {
        echo "\n✓ Translation completed successfully!\n";
    }
}
