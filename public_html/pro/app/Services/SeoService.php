<?php

namespace Casinuevo\Services;

use Google\Client;
use Google\Service\SearchConsole;

class SeoService {
    private $client;
    private $service;
    private $authJsonPath;

    public function __construct($authJsonPath) {
        $this->authJsonPath = $authJsonPath;
        $this->initializeClient();
    }

    private function initializeClient() {
        $this->client = new Client();
        $this->client->setAuthConfig($this->authJsonPath);
        $this->client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
        $this->service = new SearchConsole($this->client);
    }

    /**
     * Obtiene métricas de rendimiento (clicks, impresiones, CTR, posición)
     */
    public function getPerformanceMetrics($siteUrl, $startDate, $endDate, $dimensions = ['date']) {
        $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions($dimensions);
        $request->setRowLimit(1000);

        try {
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            return $response->getRows();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene las consultas (keywords) más populares
     */
    public function getTopQueries($siteUrl, $startDate, $endDate, $limit = 50) {
        $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['query']);
        $request->setRowLimit($limit);

        try {
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            return $response->getRows();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene las páginas más visitadas
     */
    public function getTopPages($siteUrl, $startDate, $endDate, $limit = 50) {
        $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['page']);
        $request->setRowLimit($limit);

        try {
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            return $response->getRows();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    /**
     * Lista los sitios accesibles
     */
    public function listSites() {
        try {
            $response = $this->service->sites->listSites();
            return $response->getSiteEntry();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Detecta "Low Hanging Fruit": Keywords en posición 4-20 con muchas impresiones
     */
    public function getLowHangingFruit($siteUrl, $startDate, $endDate, $minImpressions = 100) {
        $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['query', 'page']);
        $request->setRowLimit(5000); 

        try {
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            $rows = $response->getRows();
            $opportunities = [];

            if ($rows) {
                foreach ($rows as $row) {
                    if ($row->position >= 4 && $row->position <= 20 && $row->impressions >= $minImpressions) {
                        $opportunities[] = $row;
                    }
                }
            }
            
            // Ordenar por impresiones descendente
            usort($opportunities, function($a, $b) {
                return $b->impressions - $a->impressions;
            });

            return array_slice($opportunities, 0, 50);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Detecta Keywords con alto tráfico pero bajo CTR
     */
    public function getLowCTR($siteUrl, $startDate, $endDate, $minImpressions = 500, $maxCTR = 3.0) {
        $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['query', 'page']);
        $request->setRowLimit(5000);

        try {
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            $rows = $response->getRows();
            $lowCtrItems = [];

            if ($rows) {
                foreach ($rows as $row) {
                    // Convertir CTR decimal a porcentaje para comparar (0.015 -> 1.5%)
                    $ctrPercent = $row->ctr * 100;
                    if ($row->impressions >= $minImpressions && $ctrPercent <= $maxCTR) {
                        $lowCtrItems[] = $row;
                    }
                }
            }

            usort($lowCtrItems, function($a, $b) {
                return $b->impressions - $a->impressions;
            });

            return array_slice($lowCtrItems, 0, 50);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Detecta canibalización: Múltiples páginas rankeando por la misma keyword
     */
    public function getCannibalizationIssues($siteUrl, $startDate, $endDate) {
        $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['query', 'page']);
        $request->setRowLimit(5000); 

        try {
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            $rows = $response->getRows();
            $keywords = [];

            if ($rows) {
                foreach ($rows as $row) {
                    $query = $row->keys[0];
                    $page = $row->keys[1];
                    
                    if (!isset($keywords[$query])) {
                        $keywords[$query] = [];
                    }
                    $keywords[$query][] = [
                        'page' => $page,
                        'clicks' => $row->clicks,
                        'impressions' => $row->impressions,
                        'position' => $row->position
                    ];
                }
            }

            $issues = [];
            foreach ($keywords as $query => $pages) {
                if (count($pages) > 1) {
                    // Filtrar casos irrelevantes (ej. paginación o versiones muy menores)
                    // Solo considerar si al menos 2 páginas tienen tráfico/impresiones significativas
                    $significantPages = array_filter($pages, function($p) {
                        return $p['impressions'] > 50; 
                    });

                    if (count($significantPages) > 1) {
                        // Ordenar páginas por clicks
                        usort($significantPages, function($a, $b) {
                            return $b['clicks'] - $a['clicks'];
                        });
                        $issues[$query] = $significantPages;
                    }
                }
            }
            
            // Convertir a formato más amigable y limitar
            $result = array_slice($issues, 0, 50);
            return $result;

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Helper para verificar si un H1/Título contiene la keyword
     */
    public function checkKeywordInTitle($url, $keyword) {
        // Esta función requiere hacer scraping de la URL o consultar BD
        // Por simplicidad y rendimiento, devolveremos true/false simulado 
        // o implementaremos lógica básica de scraping ligero si el server lo permite.
        // Opción segura: Devolver null para indicar "verificación manual requerida" en UI por ahora.
        return null; 
    }

    /**
     * Detecta páginas "Zombie": Indexadas pero sin clics en el periodo
     */
    public function getZombiePages($siteUrl, $startDate, $endDate, $limit = 50) {
        $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['page']);
        $request->setRowLimit(5000); 

        try {
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            $rows = $response->getRows();
            $zombies = [];

            if ($rows) {
                foreach ($rows as $row) {
                    // Criterio zombie: 0 clicks. Se puede ajustar (ej. < 5 clicks)
                    if ($row->clicks == 0) {
                        $zombies[] = $row;
                    }
                }
            }
            
            // Ordenar por impresiones (para ver qué zombies se están mostrando pero ignorando)
            usort($zombies, function($a, $b) {
                return $b->impressions - $a->impressions;
            });

            return array_slice($zombies, 0, $limit);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
