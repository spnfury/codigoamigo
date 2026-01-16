document.addEventListener('DOMContentLoaded', function () {
    const trackingLinks = document.querySelectorAll('.track-amazon');

    trackingLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            // No preventDefault() - we want immediate navigation

            const slug = this.getAttribute('data-slug');
            const origin = this.getAttribute('data-origin') || 'unknown';

            if (slug) {
                // Send tracking request to standard actions endpoint
                // We add metodo to URL to ensure $_REQUEST['metodo'] is populated for the switch
                fetch('/myphp/ajax_actions.php?metodo=track_amazon', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        metodo: 'track_amazon',
                        slug: slug,
                        origin: origin
                    }),
                    keepalive: true
                }).catch(error => {
                    console.error('Amazon tracking error:', error);
                });
            }
        });
    });
});
