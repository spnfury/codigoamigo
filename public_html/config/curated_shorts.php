<?php
/**
 * Curated shorts videos for Chollos Shorts feature
 * These are popular videos that work as content
 * when YouTube API quota is exceeded or scraping fails
 * 
 * IMPORTANT: These are verified working YouTube video IDs
 * Update this list periodically with fresh content
 * 
 * Para encontrar IDs válidos:
 * 1. Ve a YouTube y busca shorts de tecnología
 * 2. Copia el ID del URL (la parte después de /shorts/ o watch?v=)
 * 3. Verifica que funcione en: https://www.youtube.com/embed/VIDEO_ID
 */

return [
    // Videos verificados que funcionan
    [
        'id' => 'jNQXAC9IVRw', // Me at the zoo - primer video de YouTube
        'title' => 'Video clásico',
        'category' => 'general',
        'keywords' => ['youtube', 'clasico']
    ],
    [
        'id' => '9bZkp7q19f0', // PSY - Gangnam Style
        'title' => 'Viral mundial',
        'category' => 'music',
        'keywords' => ['viral', 'musica']
    ],
    [
        'id' => 'kJQP7kiw5Fk', // Despacito
        'title' => 'Hit musical',
        'category' => 'music',
        'keywords' => ['despacito', 'musica']
    ],
    [
        'id' => 'fJ9rUzIMcZQ', // Bohemian Rhapsody
        'title' => 'Clásico del rock',
        'category' => 'music',
        'keywords' => ['queen', 'rock', 'musica']
    ],
    [
        'id' => 'hTWKbfoikeg', // Smosh - Pokemon Theme
        'title' => 'Gaming nostalgia',
        'category' => 'gaming',
        'keywords' => ['pokemon', 'gaming']
    ],
    [
        'id' => 'OPf0YbXqDm0', // Mark Ronson - Uptown Funk
        'title' => 'Funk vibes',
        'category' => 'music',
        'keywords' => ['funk', 'musica']
    ],
    [
        'id' => 'JGwWNGJdvx8', // Ed Sheeran - Shape of You
        'title' => 'Pop hit',
        'category' => 'music',
        'keywords' => ['pop', 'musica']
    ],
    [
        'id' => 'RgKAFK5djSk', // Wiz Khalifa - See You Again
        'title' => 'Emotional hit',
        'category' => 'music',
        'keywords' => ['emotional', 'musica']
    ],
    [
        'id' => '2Vv-BfVoq4g', // Perfect - Ed Sheeran
        'title' => 'Romántico',
        'category' => 'music',
        'keywords' => ['romantico', 'musica']
    ],
    [
        'id' => 'CevxZvSJLk8', // Katy Perry - Roar
        'title' => 'Empowerment',
        'category' => 'music',
        'keywords' => ['pop', 'katy perry']
    ],
];
