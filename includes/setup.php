<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * =========================================================================
 * SETUP Y CONFIGURACIÓN DEL TEMA HIJO (VERSIÓN FINAL Y DEPURABLE)
 * =========================================================================
 */

/**
 * Carga centralizada de estilos y scripts.
 */
add_action( 'wp_enqueue_scripts', function() {
    // Estilos
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'tutorstarter-child-style',
        get_stylesheet_uri(),
        ['parent-style'], // ¡Esta es la línea que faltaba!
        wp_get_theme()->get('Version') // Usa la versión del tema para el cache busting.
    );
    if ( is_product() ) {
        $custom_css_path = get_stylesheet_directory() . '/css/custom-product-page.css';
        if (file_exists($custom_css_path)) {
            wp_enqueue_style(
                'custom-product-page-style',
                get_stylesheet_directory_uri() . '/css/custom-product-page.css',
                ['parent-style'],
                filemtime($custom_css_path)
            );
        }
    }

    // Script principal (main.js)
    $main_script_path = get_stylesheet_directory() . '/assets/js/main.js';
    if (file_exists($main_script_path)) {
        wp_enqueue_script(
            'tutorstarter-child-main-script',
            get_stylesheet_directory_uri() . '/assets/js/main.js',
            ['jquery'], // Dependencia añadida por buena práctica
            filemtime($main_script_path),
            true
        );

        // ¡CORRECCIÓN CRÍTICA! Pasamos los datos para AJAX con el nonce correcto.
        // El nonce debe usar guion bajo ('_') para coincidir con el que el plugin verifica.
        wp_localize_script(
            'tutorstarter-child-main-script',
            'theme_ajax_object',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('subscriber_prices_nonce') // <--- CORREGIDO
            ]
        );
    }
});

/**
 * Funcionalidades adicionales del tema (sin cambios).
 */
add_action('wp_head', function() {
    if (is_page(25741)) {
        echo '
        <link rel="preload" href="https://player-vz-6ba217d2-450.tv.pandavideo.com.br/embed/css/styles.css" as="style">
        <link rel="prerender" href="https://player-vz-6ba217d2-450.tv.pandavideo.com.br/embed/?v=1e7155cb-cc4f-430f-b7f4-3f73176ee226">
        <link rel="preload" href="https://player-vz-6ba217d2-450.tv.pandavideo.com.br/embed/js/hls.js" as="script">
        <link rel="preload" href="https://player-vz-6ba217d2-450.tv.pandavideo.com.br/embed/js/plyr.polyfilled.min.js" as="script">
        <link rel="dns-prefetch" href="https://b-vz-6ba217d2-450.tv.pandavideo.com.br">
        <link rel="dns-prefetch" href="https://player-vz-6ba217d2-450.tv.pandavideo.com.br">';
    }
});

add_action('login_enqueue_scripts', function() {
    echo '<style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(' . esc_url(get_stylesheet_directory_uri()) . '/uploads/2023/11/TAXES4PROS-2-1.svg);
            height:65px; width:320px; background-size: 320px 65px;
            background-repeat: no-repeat; padding-bottom: 30px;
        }
    </style>';
});

add_action('init', function() {
    global $pagenow;
    if ( !is_admin() && $pagenow === 'wp-login.php' && isset($_GET['action']) && $_GET['action'] === 'register') {
        wp_redirect(home_url('/cuenta/'));
        exit;
    }
});

// El endpoint AJAX que estaba aquí ha sido eliminado para evitar conflictos con el plugin.