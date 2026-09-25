<?php
/**
 * JWT Authentication Helper
 * Provides JWT token generation and validation for API authentication
 */

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;

class JWT {
    private static ?string $secretKey = null;
    
    /**
     * Get or generate JWT secret key
     */
    private static function getSecretKey(): string {
        if (self::$secretKey !== null) {
            return self::$secretKey;
        }
        
        // Optional: read from environment variable if present and sufficiently long
        $envKey = getenv('JWT_SECRET');
        if ($envKey && strlen($envKey) >= 32) {
            self::$secretKey = $envKey;
            return self::$secretKey;
        }
        
        // Unified schema: settings(namespace='security', key='jwt_secret', JSON value)
        $pdo = DB::conn();
        $stmt = $pdo->prepare('SELECT value FROM settings WHERE namespace = ? AND `key` = ? LIMIT 1');
        $stmt->execute(['security', 'jwt_secret']);
        $result = $stmt->fetch();
        
        if ($result && isset($result['value'])) {
            $val = $result['value'];
            $decoded = json_decode($val, true);
            if (is_string($decoded) && strlen($decoded) >= 32) {
                self::$secretKey = $decoded;
                return self::$secretKey;
            }
        }
        
        // If no secret exists, generate and save using the unified schema
        $newKey = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare('INSERT INTO settings (user_id, namespace, `key`, value) VALUES (NULL, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
        $stmt->execute(['security', 'jwt_secret', json_encode($newKey)]);
        self::$secretKey = $newKey;
        return self::$secretKey;
    }
    
    /**
     * Generate JWT token for user
     * 
     * @param int $userId User ID
     * @param int $expiresIn Token lifetime in seconds (default: 30 days)
     * @return string JWT token
     */
    public static function generate(int $userId, int $expiresIn = 2592000): string {
        $issuedAt = time();
        $expire = $issuedAt + $expiresIn;
        
        $payload = [
            'iss' => 'amnezia-panel',          // Issuer
            'aud' => 'amnezia-api',            // Audience
            'iat' => $issuedAt,                // Issued at
            'exp' => $expire,                  // Expiration
            'sub' => $userId,                  // Subject (user ID)
            'jti' => bin2hex(random_bytes(16)) // JWT ID (unique token identifier)
        ];
        
        return FirebaseJWT::encode($payload, self::getSecretKey(), 'HS256');
    }
    
    /**
     * Validate and decode JWT token
     * 
     * @param string $token JWT token
     * @return object|null Decoded token payload or null if invalid
     */
    public static function decode(string $token): ?object {
        try {
            $decoded = FirebaseJWT::decode($token, new Key(self::getSecretKey(), 'HS256'));
            
            // Verify issuer and audience
            if ($decoded->iss !== 'amnezia-panel' || $decoded->aud !== 'amnezia-api') {
                return null;
            }
            
            return $decoded;
        } catch (Exception $e) {
            error_log('JWT decode error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user ID from JWT token
     * 
     * @param string $token JWT token
     * @return int|null User ID or null if invalid
     */
    public static function getUserId(string $token): ?int {
        $decoded = self::decode($token);
        
        if ($decoded === null) {
            return null;
        }
        
        return (int)$decoded->sub;
    }
    
    /**
     * Verify JWT token and get user data
     * 
     * @param string $token JWT token
     * @return array|null User data or null if invalid
     */
    public static function verify(string $token): ?array {
        $userId = self::getUserId($token);
        
        if ($userId === null) {
            return null;
        }
        
        // Get user from database
        $pdo = DB::conn();
        $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        return $user ?: null;
    }
    
    /**
     * Extract token from Authorization header
     * 
     * @return string|null Token or null if not found
     */
    public static function getTokenFromHeader(): ?string {
        // Try getallheaders() first (Apache/FPM)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback for other environments (nginx, CLI)
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                    $headers[$header] = $value;
                }
            }
        }
        
        // Check Authorization header
        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            
            // Bearer token format: "Bearer {token}"
            if (preg_match('/Bearer\s+(.+)/', $auth, $matches)) {
                return $matches[1];
            }
        }
        
        // Check X-API-Token header (alternative)
        if (isset($headers['X-Api-Token'])) {
            return $headers['X-Api-Token'];
        }
        
        // Also check direct $_SERVER access for Authorization
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.+)/', $auth, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Middleware: Require JWT authentication for API endpoints
     * 
     * @return array|null User data if authenticated, sends 401 response and returns null if not
     */
    public static function requireAuth(): ?array {
        $token = self::getTokenFromHeader();
        
        if ($token === null) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Missing authentication token']);
            return null;
        }
        
        $user = self::verify($token);
        
        if ($user === null) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid or expired token']);
            return null;
        }
        
        return $user;
    }
    
    /**
     * Create API token for user (saves to database)
     * 
     * @param int $userId User ID
     * @param string|null $name Token name/description
     * @param int $expiresIn Token lifetime in seconds (default: 30 days)
     * @return array Token data (id, token, expires_at)
     */
    public static function createApiToken(int $userId, ?string $name = null, int $expiresIn = 2592000): array {
        $token = self::generate($userId, $expiresIn);
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
        
        $pdo = DB::conn();
        $stmt = $pdo->prepare('
            INSERT INTO api_tokens (user_id, token, name, expires_at) 
            VALUES (?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $userId,
            $token,
            $name ?? 'API Token',
            $expiresAt
        ]);
        
        return [
            'id' => (int)$pdo->lastInsertId(),
            'token' => $token,
            'name' => $name ?? 'API Token',
            'expires_at' => $expiresAt
        ];
    }
    
    /**
     * Revoke API token
     * 
     * @param int $tokenId Token ID
     * @param int $userId User ID (for ownership verification)
     * @return bool Success
     */
    public static function revokeApiToken(int $tokenId, int $userId): bool {
        $pdo = DB::conn();
        $stmt = $pdo->prepare('DELETE FROM api_tokens WHERE id = ? AND user_id = ?');
        return $stmt->execute([$tokenId, $userId]);
    }
    
    /**
     * Get all API tokens for user
     * 
     * @param int $userId User ID
     * @return array List of tokens
     */
    public static function getUserTokens(int $userId): array {
        $pdo = DB::conn();
        $stmt = $pdo->prepare('
            SELECT id, name, LEFT(token, 20) as token_preview, created_at, expires_at 
            FROM api_tokens 
            WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY created_at DESC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
