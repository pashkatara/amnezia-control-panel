<?php
class DB {
  private static ?PDO $pdo = null;

  public static function conn(): PDO {
    if (self::$pdo) return self::$pdo;
    $host = Config::get('DB_HOST', '127.0.0.1');
    $port = Config::get('DB_PORT', '3306');
    $db   = Config::get('DB_DATABASE', 'amnezia_panel');
    $user = Config::get('DB_USERNAME', 'amnezia');
    $pass = Config::get('DB_PASSWORD', '');
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);
    $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ];
    self::$pdo = new PDO($dsn, $user, $pass, $options);
    
    // Explicitly set UTF-8 encoding for connection
    self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    return self::$pdo;
  }
}