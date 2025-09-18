<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Carga de estilos y scripts del tema.
 */
add_action( 'wp_enqueue_scripts', function() {
    // Estilo del tema padre.
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );

    // Estilo personalizado para la página de producto.
    if ( is_product() ) {
        $custom_css_path = get_stylesheet_directory() . '/css/custom-product-page.css';
        if (file_exists($custom_css_path)) {
            wp_enqueue_style(
                'custom-product-page-style',
                get_stylesheet_directory_uri() . '/css/custom-product-page.css',
                ['parent-style'],
                filemtime($custom_css_path) // Versión automática
            );
        }
    }
});

/**
 * Agrega preload/prefetch para recursos de video en una página específica.
 * RECOMENDACIÓN: Reemplaza el ID '25741' por el slug de la página para mayor robustez.
 * Ejemplo: is_page('mi-pagina-de-video')
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

/**
 * Personaliza el logo en la página de login de WordPress.
 */
add_action('login_enqueue_scripts', function() {
    echo '<style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url(' . get_stylesheet_directory_uri() . '/uploads/2023/11/TAXES4PROS-2-1.svg);
            height:65px;
            width:320px;
            background-size: 320px 65px;
            background-repeat: no-repeat;
            padding-bottom: 30px;
        }
    </style>';
});

/**
 * Redirige la página de registro por defecto de WordPress a la página "Mi Cuenta".
 */
add_action('init', function() {
    global $pagenow;
    if ($pagenow === 'wp-login.php' && isset($_GET['action']) && $_GET['action'] === 'register') {
        wp_redirect(home_url('/cuenta/'));
        exit;
    }
});