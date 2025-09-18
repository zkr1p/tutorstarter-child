<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update the template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $product ); // Muestra el stock del producto.

if ( $product->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

    <?php
    // --- INICIO DE NUESTRA LÓGICA PERSONALIZADA ---

    // Verificamos si es un producto descargable para aplicar nuestra lógica especial.
    if ( $product->is_downloadable() ) :

        $user_id       = get_current_user_id();
        $has_purchased = $user_id ? wc_customer_bought_product( '', $user_id, $product->get_id() ) : false;
        
        // URLs que podríamos necesitar
        $checkout_url  = wc_get_checkout_url() . '?add-to-cart=' . $product->get_id();
        $download_url  = wc_get_endpoint_url( 'downloads', '', wc_get_page_permalink( 'myaccount' ) );
        ?>
        <form class="cart custom-cart-options professional-style" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
            <div class="purchase-options">
                <div class="purchase-option">
                    <label class="purchase-label">
                        <input type="radio" name="purchase_option" value="single" checked style="display: none;">
                        <div class="option-content">
                            <span class="option-title"><?php esc_html_e( 'Compra única', 'Tutorstarterchild' ); ?></span>
                            <span class="option-price">
                                <?php if ( $has_purchased ) : ?>
                                    <span class="status-disponible"><?php esc_html_e( 'Disponible', 'Tutorstarterchild' ); ?></span>
                                <?php else : ?>
                                    <?php echo $product->get_price_html(); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="custom-add-to-cart">
                <?php if ( $has_purchased ) : ?>
                    <a href="<?php echo esc_url( $download_url ); ?>" class="button alt wp-element-button">
                        <?php esc_html_e( 'Ir a mis descargas', 'Tutorstarterchild' ); ?>
                    </a>
                <?php else : ?>
                    <a href="<?php echo esc_url( $checkout_url ); ?>" class="button alt wp-element-button">
                        <?php esc_html_e( 'Ir a pagar', 'Tutorstarterchild' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>

    <?php else : // Esto es para productos que NO son descargables (físicos) ?>

        <form class="cart custom-cart-options professional-style" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
            <div class="purchase-options">
                <div class="purchase-option">
                    <label class="purchase-label">
                        <div class="option-content">
                            <span class="option-title"><?php esc_html_e( 'Compra única', 'Tutorstarterchild' ); ?></span>
                            <span class="option-price"><?php echo $product->get_price_html(); ?></span>
                        </div>
                    </label>
                </div>
            </div>
            <div class="custom-add-to-cart">
                <a href="<?php echo esc_url( wc_get_checkout_url() . '?add-to-cart=' . $product->get_id() ); ?>" class="button alt wp-element-button">
                    <?php esc_html_e( 'Comprar ahora', 'Tutorstarterchild' ); ?>
                </a>
            </div>
        </form>

    <?php endif; // --- FIN DE NUESTRA LÓGICA PERSONALIZADA --- ?>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>