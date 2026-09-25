#!/usr/bin/env php
<?php
/**
 * Auto-translate all languages
 * Usage: php bin/translate_all.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/Config.php';
require_once __DIR__ . '/../inc/DB.php';
require_once __DIR__ . '/../inc/Translator.php';

Config::load(__DIR__ . '/../.env');
DB::conn();

echo "=== Auto-Translation Tool ===\n\n";

// Check if API key exists
$translator = new Translator();
$apiKey = $translator->getApiKey('openrouter');

if (empty($apiKey)) {
    echo "❌ Error: OpenRouter API key not found in database.\n";
    echo "Please add your API key in Settings page first.\n";
    exit(1);
}

echo "✅ OpenRouter API key found\n\n";

// Get all languages except English
$pdo = DB::conn();
$stmt = $pdo->query("SELECT code, name FROM languages WHERE code != 'en' ORDER BY code");
$languages = $stmt->fetchAll();

echo "Languages to translate: " . count($languages) . "\n";
foreach ($languages as $lang) {
    echo "  - {$lang['name']} ({$lang['code']})\n";
}

echo "\nStarting translation...\n\n";

foreach ($languages as $lang) {
    $langCode = $lang['code'];
    $langName = $lang['name'];
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Translating to: {$langName} ({$langCode})\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    try {
        $stats = Translator::translateMissingKeys($langCode);
        
        echo "✅ Translation completed!\n";
        echo "   Total keys: {$stats['total']}\n";
        echo "   Translated: {$stats['translated']}\n";
        echo "   Already existed: {$stats['existing']}\n";
        echo "   Failed: {$stats['failed']}\n\n";
        
        // Sleep to avoid rate limiting
        if ($stats['translated'] > 0) {
            echo "⏳ Waiting 5 seconds to avoid rate limits...\n\n";
            sleep(5);
        }
    } catch (Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ All translations completed!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Show final statistics
echo "\nFinal Statistics:\n";
$stmt = $pdo->query("
    SELECT 
        l.code,
        l.name,
        COUNT(DISTINCT t.translation_key) as translated,
        (SELECT COUNT(DISTINCT translation_key) FROM translations WHERE language_code = 'en') as total
    FROM languages l
    LEFT JOIN translations t ON l.code = t.language_code
    GROUP BY l.code, l.name
    ORDER BY l.code
");

$results = $stmt->fetchAll();
foreach ($results as $row) {
    $percent = round(($row['translated'] / $row['total']) * 100);
    $bar = str_repeat('█', (int)($percent / 5));
    $empty = str_repeat('░', 20 - (int)($percent / 5));
    echo sprintf(
        "  %s (%s): [%s%s] %3d%% (%d/%d)\n",
        $row['name'],
        $row['code'],
        $bar,
        $empty,
        $percent,
        $row['translated'],
        $row['total']
    );
}

echo "\n";
