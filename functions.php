<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// =========================================================================
// CARGADOR PRINCIPAL DEL TEMA
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