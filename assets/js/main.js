/**
 * Lógica del frontend para la interfaz de compra del producto variable.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof productPageData === 'undefined') {
        return;
    }

    // Ocultamos el contenedor por defecto si nuestro sistema no es aplicable a este producto.
    const container = document.getElementById('ebook-purchase-options-container');
    if (productPageData.status === 'unavailable') {
        if (container) container.style.display = 'none';
        return;
    }

    // Referencias a los elementos del DOM.
    const radioEbook = document.querySelector('input[name="purchase_option"][value="ebook"]');
    const radioBook = document.querySelector('input[name="purchase_option"][value="book"]');
    const btnDownload = document.getElementById('btn-download');
    const btnBuy = document.getElementById('btn-buy');
    const msgExhausted = document.getElementById('msg-exhausted');
    const msgUnavailable = document.getElementById('msg-unavailable'); // Nuevo mensaje
    const ebookPriceEl = document.getElementById('ebook-price-placeholder');
    const bookPriceEl = document.getElementById('book-price-placeholder');

    /**
     * Inicializa los datos estáticos, como los precios.
     */
    function initializeStaticData() {
        if (ebookPriceEl && productPageData.ebook_price_html) {
            ebookPriceEl.innerHTML = productPageData.ebook_price_html;
        }
        if (bookPriceEl && productPageData.book_price_html) {
            bookPriceEl.innerHTML = productPageData.book_price_html;
        }
    }

    /**
     * Actualiza la visibilidad de los botones y mensajes de acción.
     */
    function updateUI() {
        // Ocultar todos los elementos de acción al principio.
        btnDownload.style.display = 'none';
        btnBuy.style.display = 'none';
        msgExhausted.style.display = 'none';
        msgUnavailable.style.display = 'none'; // Ocultar nuevo mensaje

        if (radioBook.checked) {
            btnBuy.style.display = 'block';
            btnBuy.dataset.link = productPageData.book_url;
        } else {
            // Lógica para el E-book basada en el estado.
            switch (productPageData.status) {
                case 'can_download':
                    btnDownload.style.display = 'block';
                    btnDownload.href = productPageData.download_page_url;
                    break;
                case 'exhausted':
                    msgExhausted.style.display = 'block';
                    btnBuy.style.display = 'block';
                    btnBuy.dataset.link = productPageData.ebook_url;
                    break;
                case 'purchased_but_unavailable': // Nuevo estado
                    msgUnavailable.style.display = 'block';
                    break;
                case 'purchase_required':
                default:
                    btnBuy.style.display = 'block';
                    btnBuy.dataset.link = productPageData.ebook_url;
                    break;
            }
        }
    }

    // Listeners
    if (radioEbook && radioBook) {
        radioEbook.addEventListener('change', updateUI);
        radioBook.addEventListener('change', updateUI);
    }
    
    btnBuy.addEventListener('click', function () {
        if (this.dataset.link) {
            window.location.href = this.dataset.link;
        }
    });

    // Ejecutar al cargar la página
    initializeStaticData();
    updateUI();
});