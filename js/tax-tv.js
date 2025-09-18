jQuery(document).ready(function ($) {
    // Función para actualizar el reproductor dinámicamente
    function updatePlayer(videoId, videoTitle, clickedElement) {
        console.log(`Reproduciendo video con ID: ${videoId}`); // Debugging
        $('#video-frame').attr('src', `https://www.youtube.com/embed/${videoId}?autoplay=1`);
        $('#video-title').text(videoTitle);

        // Resaltar el video seleccionado
        $('.video-link').removeClass('active');
        $(clickedElement).addClass('active');
    }

    // Cargar el primer video automáticamente
    const firstVideo = $('#video-list .video-link').first();
    if (firstVideo.length > 0) {
        const firstVideoId = firstVideo.data('video-id');
        const firstVideoTitle = firstVideo.data('video-title');
        updatePlayer(firstVideoId, firstVideoTitle, firstVideo);
    }

    // Cambiar el video al hacer clic en la lista
    $(document).on('click', '.video-link', function (e) {
        e.preventDefault();

        const videoId = $(this).data('video-id');
        const videoTitle = $(this).data('video-title');

        if (videoId && videoTitle) {
            updatePlayer(videoId, videoTitle, this);
        } else {
            console.error('Video ID o título faltante.');
        }
    });
});
