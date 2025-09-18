<?php
/**
 * Plantilla de correo “Preorder”
 * Ruta: theme/tutorstarter-child/email-templates/preorder-email.php
 *
 * Variables disponibles:
 *  - $customer_name  (string)
 *  - $product_list   (string)  lista en HTML (<li>…</li>)
 */
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preorder confirmado</title>
    <style>
        /* Copia aquí todas las reglas CSS de tu otro template */
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; color: #343a40; margin:0; padding:0; }
        .email-wrapper { width:100%; padding:20px 0; background-color:#f8f9fa; }
        .email-container { background:#fff; max-width:600px; margin:0 auto; border-radius:8px; box-shadow:0 4px 8px rgba(0,0,0,0.1); overflow:hidden; }
        .email-header { background:#e93700; color:#fff; padding:20px; text-align:center; }
        .email-body { padding:20px; }
        .email-body p { margin:0 0 15px; line-height:1.6; }
        .email-footer { background:#f1f1f1; padding:20px; text-align:center; font-size:.9em; color:#6c757d; }
        .btn { display:inline-block; padding:12px 20px; color:#fff; background:#e93700; text-decoration:none; border-radius:5px; margin-top:10px; }
        .btn:hover { background:#c62900; }
    </style>
</head>
<body>
    <div class="email-wrapper">
      <div class="email-container">
        <div class="email-header">
          <h1>¡Tu pedido en preventa está confirmado!</h1>
        </div>
        <div class="email-body">
          <p>Hola <?php echo esc_html( $customer_name ); ?>,</p>
          <p>Gracias por tu confianza: has reservado estos productos en preventa:</p>
          <ul>
            <?php echo $product_list; ?>
          </ul>
          <p>En cuanto estén disponibles, te avisaremos con un nuevo correo.</p>
          <p>Puedes ver el estado de tu pedido en tu cuenta:</p>
          <p><a href="<?php echo esc_url( wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="btn">Ver mi pedido</a></p>
        </div>
        <div class="email-footer">
          <p>Gracias por elegirnos.</p>
          <p><a href="<?php echo esc_url( home_url() ); ?>">Volver al sitio</a></p>
        </div>
      </div>
    </div>
</body>
</html>
