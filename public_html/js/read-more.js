// Funcionalidad "ver más" - estilo Chollometro
document.addEventListener('DOMContentLoaded', function() {
    initializeReadMore();
});

// Función para implementar "ver más" en textos largos
function initializeReadMore() {
    document.querySelectorAll('.read-more-content').forEach(function(element) {
        const text = element.textContent.trim();
        const maxLength = 120;

        if (text.length > maxLength) {
            const truncatedText = text.substring(0, maxLength) + '...';
            element.innerHTML = truncatedText + ' <span class="read-more-btn" onclick="toggleReadMore(this)">ver más</span>';

            element.setAttribute('data-full-text', text);
            element.setAttribute('data-truncated-text', truncatedText);
            element.classList.add('truncated');
        }
    });
}

// Función para alternar entre texto completo y truncado
function toggleReadMore(button) {
    const content = button.parentElement;
    const isTruncated = content.classList.contains('truncated');

    if (isTruncated) {
        // Mostrar texto completo
        content.innerHTML = content.getAttribute('data-full-text') + ' <span class="read-more-btn" onclick="toggleReadMore(this)">ver menos</span>';
        content.classList.remove('truncated');
    } else {
        // Mostrar texto truncado
        content.innerHTML = content.getAttribute('data-truncated-text') + ' <span class="read-more-btn" onclick="toggleReadMore(this)">ver más</span>';
        content.classList.add('truncated');
    }
}
