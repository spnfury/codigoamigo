document.addEventListener('DOMContentLoaded', function () {
    // Manejar el volteo de tarjetas en móvil
    const flipTriggers = document.querySelectorAll('.flip-trigger-mobile');

    flipTriggers.forEach(trigger => {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const flipCard = this.closest('.flip-card');
            if (flipCard) {
                flipCard.classList.toggle('flipped');
            }
        });
    });

    // También permitir voltear al tocar la parte trasera para volver al frente
    const flipBacks = document.querySelectorAll('.flip-card-back');
    flipBacks.forEach(back => {
        back.addEventListener('click', function (e) {
            // Si el clic es en el enlace de "Ver más", no voltear
            if (e.target.closest('.btn-more-info')) return;

            const flipCard = this.closest('.flip-card');
            if (flipCard && flipCard.classList.contains('flipped')) {
                flipCard.classList.remove('flipped');
            }
        });
    });

    // Cerrar tarjetas volteadas si se hace clic fuera
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.flip-card')) {
            document.querySelectorAll('.flip-card.flipped').forEach(card => {
                card.classList.remove('flipped');
            });
        }
    });
});
