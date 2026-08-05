<?php
// Script de prueba para la lógica de ordenación semi-aleatoria
function test_weighted_sort() {
    $now = time();
    $codes = [];
    
    // Crear datos de prueba (15 códigos con diferentes días restantes)
    for ($i = 0; $i <= 14; $i++) {
        $codes[] = [
            'id' => "code_$i",
            'days_remaining' => $i,
            'name' => "Código con $i días restantes"
        ];
    }
    
    // Probar 10 iteraciones
    echo "Simulación de ordenación semi-aleatoria (10 iteraciones):\n";
    echo str_repeat("-", 50) . "\n";
    
    // Contar cuántas veces cada uno aparece en el top 4
    $top4_counts = array_fill(0, 15, 0);
    
    for ($iter = 1; $iter <= 1000; $iter++) {
        $sorted_codes = $codes;
        
        foreach ($sorted_codes as &$code) {
            $days = $code['days_remaining'];
            // Peso: 50 (base) + hasta 50 (por los días) = de 50 a 100
            // Usamos max(0, min(14, $days)) por seguridad
            $weight = 50 + (max(0, min(14, $days)) * (50 / 14)); 
            
            // Factor aleatorio (0.01 a 1.0)
            $random_factor = mt_rand(1, 100) / 100;
            
            // Score final
            $code['score'] = $weight * $random_factor;
        }
        
        // Ordenar por score descendente
        usort($sorted_codes, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Registrar top 4
        for ($i = 0; $i < 4; $i++) {
            $top4_counts[$sorted_codes[$i]['days_remaining']]++;
        }
    }
    
    echo "Resultados después de 1000 recargas de página (veces en el Top 4):\n";
    for ($i = 14; $i >= 0; $i--) {
        echo "Código con $i días restantes: " . $top4_counts[$i] . " veces (" . round(($top4_counts[$i]/1000)*100, 1) . "% de las recargas)\n";
    }
}

test_weighted_sort();
