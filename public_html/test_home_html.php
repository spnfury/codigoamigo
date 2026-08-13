<?php
// Just render the homepage and check if tribbu is inside the HTML.
$html = file_get_contents("https://www.codigoamigo.com/");
if (strpos($html, 'tribbu') !== false) {
    echo "TRIBBU IS IN THE HOMEPAGE HTML\n";
    
    // Find where it is
    $pos = strpos($html, 'tribbu');
    echo substr($html, max(0, $pos - 100), 200);
} else {
    echo "TRIBBU IS NOT IN THE HOMEPAGE HTML\n";
}

if (strpos($html, 'Juanlu') !== false) {
    echo "\nJUANLU IS IN THE HOMEPAGE HTML\n";
} else {
    echo "\nJUANLU IS NOT IN THE HOMEPAGE HTML\n";
}
