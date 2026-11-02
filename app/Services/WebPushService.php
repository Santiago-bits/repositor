<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorHandler;
use App\Models\Configuracion;
use RuntimeException;

/**
 * Notificaciones push sin librerías. Se manda un "toque" vacío (sin contenido cifrado):
 * al recibirlo, el celular le pregunta a la app qué mostrar (/avisos/resumen).
 * Las claves VAPID se generan solas la primera vez y quedan en Configuración.
 */
final class WebPushService
{
    public static function clavePublica(): string
    {
        return self::claves()['publica'];
    }

    /** Quién manda los avisos (lo exigen Apple y Google): la dirección de la web. */
    public static function recordarSitio(string $origen): void
    {
        if ($origen !== '' && Configuracion::get('vapid_sub', '') !== $origen) {
            Configuracion::set('vapid_sub', $origen);
        }
    }

    /** true = llegó · null = ese celular ya no existe (borrar la suscripción) · false = error. */
    public static function enviar(string $endpoint): ?bool
    {
        $partes = parse_url($endpoint);
        if (!isset($partes['scheme'], $partes['host']) || $partes['scheme'] !== 'https') {
            return null;
        }
        $claves = self::claves();
        $jwt = self::jwt($partes['scheme'] . '://' . $partes['host'], $claves['privada']);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'TTL: 86400',
                'Urgency: high',
                'Content-Length: 0',
                'Authorization: vapid t=' . $jwt . ', k=' . $claves['publica'],
            ],
        ]);
        $respuesta = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($codigo >= 200 && $codigo < 300) {
            return true;
        }
        if ($codigo === 404 || $codigo === 410) {
            return null;
        }
        ErrorHandler::log(new RuntimeException("Push falló ({$codigo}) {$error} " . substr((string) $respuesta, 0, 200)));
        return false;
    }

    /** @return array{privada: string, publica: string} */
    private static function claves(): array
    {
        $privada = (string) Configuracion::get('vapid_privada', '');
        $publica = (string) Configuracion::get('vapid_publica', '');
        if ($privada !== '' && $publica !== '') {
            return ['privada' => $privada, 'publica' => $publica];
        }

        $opciones = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
        $cnf = dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf'; // XAMPP en Windows lo necesita
        $extra = is_file($cnf) ? ['config' => $cnf] : [];
        $clave = @openssl_pkey_new($opciones) ?: openssl_pkey_new($opciones + $extra);
        if ($clave === false || !openssl_pkey_export($clave, $privada, null, $extra)) {
            throw new RuntimeException('No se pudieron crear las claves de los avisos (OpenSSL).');
        }
        $ec = openssl_pkey_get_details($clave)['ec'];
        $publica = self::b64("\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT));

        Configuracion::set('vapid_privada', $privada);
        Configuracion::set('vapid_publica', $publica);
        return ['privada' => $privada, 'publica' => $publica];
    }

    private static function jwt(string $audiencia, string $privada): string
    {
        $sub = (string) Configuracion::get('vapid_sub', '') ?: 'mailto:avisos@repositores.app';
        $datos = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256']))
            . '.' . self::b64(json_encode(['aud' => $audiencia, 'exp' => time() + 12 * 3600, 'sub' => $sub], JSON_UNESCAPED_SLASHES));
        if (!openssl_sign($datos, $der, $privada, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar el aviso.');
        }
        return $datos . '.' . self::b64(self::derARaw($der));
    }

    /** La firma de OpenSSL viene en DER; el JWT la quiere como R||S (64 bytes). */
    private static function derARaw(string $der): string
    {
        $pos = 2;
        if (ord($der[1]) & 0x80) {
            $pos += ord($der[1]) & 0x7F;
        }
        $partes = [];
        for ($i = 0; $i < 2; $i++) {
            $largo = ord($der[$pos + 1]);
            $valor = substr($der, $pos + 2, $largo);
            $partes[] = str_pad(ltrim($valor, "\0"), 32, "\0", STR_PAD_LEFT);
            $pos += 2 + $largo;
        }
        return $partes[0] . $partes[1];
    }

    private static function b64(string $datos): string
    {
        return rtrim(strtr(base64_encode($datos), '+/', '-_'), '=');
    }
}
