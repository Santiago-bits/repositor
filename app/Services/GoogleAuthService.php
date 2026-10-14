<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Verifica el "ID token" del botón "Iniciar sesión con Google" (Google Identity Services).
 * Sin librerías: la firma RS256 se valida con openssl contra las claves públicas de Google.
 * Copiado del proyecto sh-servicios; acá el ID de cliente se carga en Gestión → Configuración.
 * Solo confirma de quién es el email: quién puede entrar lo decide AuthController.
 */
final class GoogleAuthService
{
    private const JWKS_URL   = 'https://www.googleapis.com/oauth2/v3/certs';
    private const CACHE_FILE = BASE_PATH . '/storage/cache/google-jwks.json';
    private const CACHE_TTL  = 3600; // 1 hora

    public static function clientId(): string
    {
        return trim((string) \App\Models\Configuracion::get('google_client_id', ''));
    }

    public static function isConfigured(): bool
    {
        return self::clientId() !== '';
    }

    /**
     * Verifica el ID token (JWT) que manda Google. Devuelve los datos de
     * la cuenta si es válido y corresponde a este sitio, o null si algo
     * no cierra (firma inválida, vencido, de otra app, email sin
     * verificar, etc.) — nunca lanza excepciones.
     *
     * @return array{email:string,name:string}|null
     */
    public static function verifyIdToken(string $idToken): ?array
    {
        $clientId = self::clientId();
        if ($clientId === '' || $idToken === '') {
            return null;
        }

        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            return null;
        }
        [$headerB64, $payloadB64, $sigB64] = $parts;

        $header    = json_decode(self::base64UrlDecode($headerB64), true);
        $payload   = json_decode(self::base64UrlDecode($payloadB64), true);
        $signature = self::base64UrlDecode($sigB64);

        if (!is_array($header) || !is_array($payload) || $signature === '') {
            return null;
        }
        if (($header['alg'] ?? '') !== 'RS256') {
            return null;
        }

        $jwk = self::findKey((string) ($header['kid'] ?? ''));
        if ($jwk === null) {
            return null;
        }

        $publicKeyPem = self::jwkToPem($jwk);
        if ($publicKeyPem === null) {
            return null;
        }

        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if ($publicKey === false) {
            return null;
        }

        $signedData = $headerB64 . '.' . $payloadB64;
        $valid      = openssl_verify($signedData, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($valid !== 1) {
            return null;
        }

        // --- Claims ---
        $iss = (string) ($payload['iss'] ?? '');
        if (!in_array($iss, ['https://accounts.google.com', 'accounts.google.com'], true)) {
            return null;
        }
        if ((string) ($payload['aud'] ?? '') !== $clientId) {
            return null;
        }
        if ((int) ($payload['exp'] ?? 0) <= time()) {
            return null;
        }

        $emailVerified = $payload['email_verified'] ?? false;
        if ($emailVerified === 'true') {
            $emailVerified = true;
        }
        if ($emailVerified !== true) {
            return null;
        }

        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return [
            'email' => $email,
            'name'  => trim((string) ($payload['name'] ?? '')),
        ];
    }

    // -----------------------------------------------------------------
    // Claves públicas de Google (JWKS), con caché en archivo
    // -----------------------------------------------------------------

    /** @return array<string,mixed>|null */
    private static function findKey(string $kid): ?array
    {
        if ($kid === '') {
            return null;
        }
        foreach (self::fetchJwks() as $key) {
            if (is_array($key) && ($key['kid'] ?? '') === $kid) {
                return $key;
            }
        }
        return null;
    }

    /** @return array<int,array<string,mixed>> */
    private static function fetchJwks(): array
    {
        if (is_file(self::CACHE_FILE) && (time() - (int) @filemtime(self::CACHE_FILE)) < self::CACHE_TTL) {
            $cached = self::decodeKeys((string) @file_get_contents(self::CACHE_FILE));
            if ($cached !== null) {
                return $cached;
            }
        }

        [$status, $body] = self::httpGet(self::JWKS_URL);

        if ($status === 200) {
            $keys = self::decodeKeys($body);
            if ($keys !== null) {
                $dir = dirname(self::CACHE_FILE);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }
                @file_put_contents(self::CACHE_FILE, $body);
                return $keys;
            }
        }

        // Sin red: mejor usar la caché vieja que nada.
        if (is_file(self::CACHE_FILE)) {
            return self::decodeKeys((string) @file_get_contents(self::CACHE_FILE)) ?? [];
        }
        return [];
    }

    /** @return array<int,array<string,mixed>>|null */
    private static function decodeKeys(string $json): ?array
    {
        $data = json_decode($json, true);
        return is_array($data) && isset($data['keys']) && is_array($data['keys']) ? $data['keys'] : null;
    }

    /** @return array{0:int,1:string} */
    private static function httpGet(string $url): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $body   = (string) curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [$status, $body];
        }

        $context = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 8, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);
        $body   = @file_get_contents($url, false, $context);
        $status = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
                $status = (int) $m[1];
            }
        }
        return [$status, $body === false ? '' : $body];
    }

    // -----------------------------------------------------------------
    // JWK (RSA) → PEM, y utilidades base64url / DER
    // -----------------------------------------------------------------

    /** @param array<string,mixed> $jwk */
    private static function jwkToPem(array $jwk): ?string
    {
        if (($jwk['kty'] ?? '') !== 'RSA' || !isset($jwk['n'], $jwk['e'])) {
            return null;
        }

        $modulus  = self::base64UrlDecode((string) $jwk['n']);
        $exponent = self::base64UrlDecode((string) $jwk['e']);
        if ($modulus === '' || $exponent === '') {
            return null;
        }

        $rsaPublicKey = self::derSequence(self::derInteger($modulus) . self::derInteger($exponent));

        // AlgorithmIdentifier: OID rsaEncryption (1.2.840.113549.1.1.1) + NULL
        $algId = self::derSequence("\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00");

        $bitString = "\x00" . $rsaPublicKey; // byte de "bits sin usar"
        $bitStringEncoded = "\x03" . self::derLength(strlen($bitString)) . $bitString;

        $spki = self::derSequence($algId . $bitStringEncoded);

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function derInteger(string $bytes): string
    {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '') {
            $bytes = "\x00";
        }
        if ((ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00" . $bytes;
        }
        return "\x02" . self::derLength(strlen($bytes)) . $bytes;
    }

    private static function derSequence(string $contents): string
    {
        return "\x30" . self::derLength(strlen($contents)) . $contents;
    }

    private static function derLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xFF) . $bytes;
            $length >>= 8;
        }
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private static function base64UrlDecode(string $data): string
    {
        $padded    = strtr($data, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder > 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode($padded, true);
        return $decoded === false ? '' : $decoded;
    }
}
