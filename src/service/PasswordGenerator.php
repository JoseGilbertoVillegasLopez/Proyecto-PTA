<?php

namespace App\Service;

/**
 * Generador de contraseñas aleatorias, seguras y legibles.
 * - Usa CSPRNG (random_int / random_bytes) indirectamente vía shuffle seguro.
 * - Evita caracteres confusos (O/0, l/1, etc) para facilitar tipeo.
 */
class PasswordGenerator
{
    // Longitud por defecto del password
    private int $length;

    // Conjunto de caracteres permitidos (sin confusos)
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@$%#*!?';

    public function __construct(int $length = 12)
    {
        // Permitimos inyectar longitud por configuración si alguna vez lo necesitas
        $this->length = max(10, $length); // mínimo razonable: 10
    }

    /**
     * Genera un password aleatorio.
     * @return string plain password (NO HASH)
     */
    public function generate(): string
    {
        $alphabet = self::ALPHABET;         // Tomamos el alfabeto permitido
        $maxIndex = strlen($alphabet) - 1;  // Índice máximo

        $password = '';

        // Construimos carácter a carácter con índices aleatorios (CSPRNG)
        for ($i = 0; $i < $this->length; $i++) {
            // random_int es criptográficamente seguro en PHP
            $idx = random_int(0, $maxIndex);
            $password .= $alphabet[$idx];
        }

        return $password; // Devolvemos el password en claro (solo para email)
    }
}
