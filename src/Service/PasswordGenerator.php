<?php

namespace App\Service;

/**
 * Generador de contraseñas aleatorias, seguras y legibles por humanos.
 *
 * Características:
 * - Usa CSPRNG (random_int) para seleccionar índices de forma criptográficamente segura.
 * - Evita caracteres confusos como O/0 y l/1 para reducir errores de tipeo.
 * - Permite inyectar la longitud mínima desde configuración (con límite inferior razonable).
 */
class PasswordGenerator
{
    // Longitud por defecto del password (puede sobreescribirse por DI)
    private int $length;

    // Alfabeto permitido (sin caracteres fácilmente confundibles). Incluye símbolos para aumentar entropía.
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@$%#*!?';

    /**
     * @param int $length Longitud deseada; se fuerza mínimo de 10 por seguridad/usabilidad.
     */
    public function __construct(int $length = 12)
    {
        // max(10, $length): asegura que la longitud mínima sea 10 (puedes subirlo si tu política lo requiere).
        $this->length = max(10, $length);
    }

    /**
     * Genera un password aleatorio en claro (NO HASH).
     *
     * @return string Password temporal en claro (se debe hashear antes de persistir).
     */
    public function generate(): string
    {
        $alphabet = self::ALPHABET;          // Copia local del alfabeto permitido.
        $maxIndex = strlen($alphabet) - 1;   // Índice máximo válido para substracción por posición.

        $password = '';                      // Acumulador del password generado.

        // Repite tantas veces como longitud deseada, eligiendo posiciones aleatorias del alfabeto.
        for ($i = 0; $i < $this->length; $i++) {
            // random_int(0, $maxIndex) es CSPRNG (criptográficamente seguro) y adecuado para contraseñas.
            $idx = random_int(0, $maxIndex);
            // Concatena el carácter elegido al password.
            $password .= $alphabet[$idx];
        }

        // Devuelve el password en claro (NO lo persistas tal cual; úsalo sólo para enviarlo por correo).
        return $password;
    }
}
