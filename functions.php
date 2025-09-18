<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// =========================================================================
// CARGADOR PRINCIPAL DEL TEMA
// Este archivo ahora solo se encarga de cargar las diferentes partes
// de la funcionalidad del tema de forma ordenada.
// =========================================================================

$theme_includes = [
    '/includes/setup.php',                      // Carga de CSS/JS, logo, etc.
    '/includes/woocommerce-shop.php',           // Filtros y shortcodes de la tienda.
    '/includes/woocommerce-checkout.php',       // Hooks de la página de pago.
    '/includes/email-manager.php',              // Gestión de correos transaccionales.
    '/includes/account-tabs.php',               // Pestañas personalizadas en "Mi Cuenta".
];

foreach ($theme_includes as $file) {
    $filepath = get_stylesheet_directory() . $file;
    if (file_exists($filepath)) {
        require_once $filepath;
    }
}

// =========================================================================
// INICIALIZACIÓN DEL SISTEMA DE E-BOOK OPTIMIZADO
// Esta es la lógica que implementamos anteriormente para el producto variable.
// =========================================================================

add_action('init', function() {
    $ebook_system_files = [
        '/includes/ThemeLogger.php',
        '/includes/EbookStatusManager.php',
        '/includes/ThemeController.php',
    ];

    foreach ($ebook_system_files as $file) {
        $filepath = get_stylesheet_directory() . $file;
        if (file_exists($filepath)) {
            require_once $filepath;
        }
    }

    if (class_exists('TutorstarterChild\ThemeController')) {
        (new TutorstarterChild\ThemeController())->init();
    }
});

/**
 * Encola el script principal del tema y pasa los datos necesarios
 * desde PHP a JavaScript cuando es necesario.
 */
add_action('wp_enqueue_scripts', function () {
    $main_script_path = get_stylesheet_directory() . '/assets/js/main.js';
    
    if (file_exists($main_script_path)) {
        wp_enqueue_script(
            'tutorstarter-child-main-script',
            get_stylesheet_directory_uri() . '/assets/js/main.js',
            [],
            filemtime($main_script_path),
            true
        );
    }
});

