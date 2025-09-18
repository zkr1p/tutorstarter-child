<?php
/**
 * Clase de Logging para el Tema.
 *
 * Escribe logs en un archivo dedicado dentro de wp-content para una depuración sencilla.
 * Habilita o deshabilita los logs cambiando la constante WP_DEBUG.
 */
namespace TutorstarterChild;

class ThemeLogger {
    private static $log_file = WP_CONTENT_DIR . '/debug-theme.log';

    /**
     * Escribe un mensaje en el archivo de log.
     *
     * @param string $message El mensaje a registrar.
     * @param string $level El nivel del log (INFO, WARNING, ERROR).
     */
    public static function log($message, $level = 'INFO') {
        // Solo registrar si WP_DEBUG está activado en wp-config.php
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] [$level]: $message" . PHP_EOL;

        // file_put_contents con LOCK_EX es seguro para escrituras concurrentes.
        file_put_contents(self::$log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}