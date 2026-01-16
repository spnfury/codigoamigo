<?php
// Script de prueba rápido para verificar 4 tarjetas por slide

$test_codes = [
    ['marca' => 'Amazon', 'descripcion' => '20% descuento', '_id' => '1'],
    ['marca' => 'Netflix', 'descripcion' => '3 meses gratis', '_id' => '2'],
    ['marca' => 'Spotify', 'descripcion' => 'Premium gratis', '_id' => '3'],
    ['marca' => 'Uber', 'descripcion' => '50% descuento', '_id' => '4'],
    ['marca' => 'Airbnb', 'descripcion' => '100€ descuento', '_id' => '5'],
    ['marca' => 'Booking', 'descripcion' => '15% descuento', '_id' => '6'],
    ['marca' => 'Disney+', 'descripcion' => '12 meses al 50%', '_id' => '7'],
    ['marca' => 'Apple', 'descripcion' => '10% en productos', '_id' => '8']
];

$total_slides = count($test_codes);
$slides_per_view = 4;
$dots_needed = ceil($total_slides / $slides_per_view);

echo "Total códigos: $total_slides<br>";
echo "Slides necesarios: $dots_needed<br>";
echo "Códigos por slide: $slides_per_view<br>";
echo "Ancho por tarjeta: " . (100 / $slides_per_view) . "%<br>";
?>
