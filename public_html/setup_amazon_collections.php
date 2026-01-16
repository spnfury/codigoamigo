<?php
require_once __DIR__ . '/inc/conexion.php';

try {
    $db = createConnection();
    
    // Affiliate Links Collection
    $linksCollection = $db->selectCollection('affiliate_links');
    
    // Create unique index on slug
    $linksCollection->createIndex(['slug' => 1], ['unique' => true]);
    $linksCollection->createIndex(['active' => 1]);
    $linksCollection->createIndex(['order' => 1]);
    
    echo "Created indexes for affiliate_links.\n";

    // Affiliate Clicks Collection
    $clicksCollection = $db->selectCollection('affiliate_clicks');
    
    // Create indexes for reporting
    $clicksCollection->createIndex(['slug' => 1]);
    $clicksCollection->createIndex(['created_at' => -1]);
    $clicksCollection->createIndex(['session_id' => 1]);
    
    echo "Created indexes for affiliate_clicks.\n";
    
    // Seed initial data (optional, but good for testing)
    $prime = $linksCollection->findOne(['slug' => 'prime']);
    if (!$prime) {
        $linksCollection->insertOne([
            'slug' => 'prime',
            'title' => 'Amazon Prime',
            'description' => 'Envíos rápidos · Prime Video incluido · Ofertas exclusivas',
            'cta_text' => 'Probar Prime',
            'destination_url' => 'https://www.amazon.es/prime?tag=spnfuryy-21',
            'image_url' => 'https://m.media-amazon.com/images/G/30/prime/prime_logo_new._CB485934520_.png', // Placeholder
            'category' => 'general',
            'active' => true,
            'order' => 1,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime()
        ]);
        echo "Seeded 'prime' link.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
