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
    /**
     * Obtiene evolución diaria para una keyword específica
     */
    public function getKeywordEvolution($siteUrl, $keyword, $startDate, $endDate) {
        $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['date']);
        
        // Filtro por keyword
        $filter = new \Google\Service\SearchConsole\ApiDimensionFilter();
        $filter->setDimension('query');
        $filter->setOperator('equals');
        $filter->setExpression($keyword);
        
        $filterGroup = new \Google\Service\SearchConsole\ApiDimensionFilterGroup();
        $filterGroup->setFilters([$filter]);
        $request->setDimensionFilterGroups([$filterGroup]);
        
        $request->setRowLimit(100); 

        try {
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            $rows = $response->getRows();
            
            // Ordenar por fecha ascendente
            if ($rows) {
                usort($rows, function($a, $b) {
                    return strtotime($a->keys[0]) - strtotime($b->keys[0]);
                });
            }

            return $rows ?: [];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Detecta "Content Decay": Páginas que han perdido tráfico comparando dos periodos
     * (Periodo actual vs Periodo anterior)
     */
    public function getContentDecay($siteUrl, $currentStart, $currentEnd, $prevStart, $prevEnd) {
        // 1. Obtener datos actuales
        $dataCurrent = $this->getTopPages($siteUrl, $currentStart, $currentEnd, 200); 
        // 2. Obtener datos anteriores
        $dataPrev = $this->getTopPages($siteUrl, $prevStart, $prevEnd, 200);

        if (isset($dataCurrent['error'])) return $dataCurrent;
        if (isset($dataPrev['error'])) return $dataPrev;

        $currentMap = [];
        foreach ($dataCurrent as $row) {
            $currentMap[$row->keys[0]] = $row->clicks;
        }

        $decay = [];
        foreach ($dataPrev as $row) {
            $page = $row->keys[0];
            $prevClicks = $row->clicks;
            
            // Si tenía clicks significativos antes (> 10)
            if ($prevClicks > 10) {
                $currClicks = $currentMap[$page] ?? 0;
                $diff = $currClicks - $prevClicks;
                
                // Si ha perdido tráfico (ej. -20% o más, o pérdida absoluta significativa)
                if ($diff < 0) {
                     $percentChange = ($prevClicks > 0) ? ($diff / $prevClicks) * 100 : -100;
                     
                     // Criterio: Pérdida > 10 clicks Y caída > 10%
                     if ($diff <= -5 && $percentChange <= -10) {
                         $decay[] = [
                             'page' => $page,
                             'prev_clicks' => $prevClicks,
                             'curr_clicks' => $currClicks,
                             'diff' => $diff,
                             'percent' => $percentChange
                         ];
                     }
                }
            }
        }

        // Ordenar por mayor pérdida absoluta
        usort($decay, function($a, $b) {
            return $a['diff'] - $b['diff']; // ascendente porque son negativos (ej -100 antes que -10)
        });

        return array_slice($decay, 0, 50);
    }

    /**
     * Encuentra oportunidades de preguntas (Keywords informativas)
     */
    public function getQuestionOpportunities($siteUrl, $startDate, $endDate) {
        $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['query', 'page']);
        
        // Filtro Regex para preguntas en español (más amplio)
        $filter = new \Google\Service\SearchConsole\ApiDimensionFilter();
        $filter->setDimension('query');
        $filter->setOperator('includingRegex');
        // Captura tanto al inicio como en medio: que/como/cuando/donde/por que/cual/quien/cuanto/puedo/sirve/vale/funciona/merece
        $regex = '(^|\s)(que|como|cuando|donde|por que|cual|quien|cuanto|cómo|qué|cuándo|dónde|cuál|cuánto|puedo|podeis|podeís|podéis|podemos|sirve|vale|funciona|merece|gratis|mejor|opiniones|que es|qué es|donde encontrar|dónde encontrar|como conseguir|cómo conseguir)(\s|\?|$)';
        $filter->setExpression($regex);
        
        $filterGroup = new \Google\Service\SearchConsole\ApiDimensionFilterGroup();
        $filterGroup->setFilters([$filter]);
        $request->setDimensionFilterGroups([$filterGroup]);
        
        $request->setRowLimit(10000); // Aumentamos límite para captar muchísimas más

        try {
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            $rows = $response->getRows();
            
            if (!$rows) return [];

            // Filtrar y limpiar: solo preguntas que tengan sentido y ordenar por relevancia (impresiones)
            usort($rows, function($a, $b) {
                return $b->impressions - $a->impressions;
            });

            return array_slice($rows, 0, 5000); // Devolver hasta 5000
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Detecta "Tendencias Emergentes": Keywords con gran crecimiento en el periodo actual vs anterior
     */
    public function getEmergingKeywords($siteUrl, $currentStart, $currentEnd, $prevStart, $prevEnd) {
        $dataCurrent = $this->getTopQueries($siteUrl, $currentStart, $currentEnd, 1000);
        $dataPrev = $this->getTopQueries($siteUrl, $prevStart, $prevEnd, 1000);

        if (isset($dataCurrent['error'])) return $dataCurrent;
        if (isset($dataPrev['error'])) return $dataPrev;

        $prevMap = [];
        if ($dataPrev) {
            foreach ($dataPrev as $row) {
                $prevMap[$row->keys[0]] = $row->impressions;
            }
        }

        $emerging = [];
        if ($dataCurrent) {
            foreach ($dataCurrent as $row) {
                $query = $row->keys[0];
                $currImpr = $row->impressions;
                $prevImpr = $prevMap[$query] ?? 0;
                
                // Si ha crecido significativamente (ej: +50% y al menos 50 impresiones nuevas)
                // O si es nueva (prevImpr == 0) y tiene al menos 30 impresiones
                $diff = $currImpr - $prevImpr;
                if ($prevImpr > 0) {
                    $percent = ($diff / $prevImpr) * 100;
                    if ($percent >= 50 && $diff >= 40) {
                        $emerging[] = [
                            'query' => $query,
                            'prev_impr' => $prevImpr,
                            'curr_impr' => $currImpr,
                            'diff' => $diff,
                            'percent' => $percent
                        ];
                    }
                } elseif ($currImpr >= 30) {
                    $emerging[] = [
                        'query' => $query,
                        'prev_impr' => 0,
                        'curr_impr' => $currImpr,
                        'diff' => $currImpr,
                        'percent' => 100 // New trend
                    ];
                }
            }
        }

        usort($emerging, function($a, $b) {
            return $b['diff'] - $a['diff'];
        });

        return array_slice($emerging, 0, 50);
    }

    /**
     * Oportunidades de Marcas que no existen o están rindiendo poco
     */
    public function getBrandOpportunities($siteUrl, $startDate, $endDate) {
        $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['query', 'page']);
        
        // Buscamos intenciones de "código" o "descuento"
        $filter = new \Google\Service\SearchConsole\ApiDimensionFilter();
        $filter->setDimension('query');
        $filter->setOperator('includingRegex');
        $filter->setExpression('(codigo|código|cupón|cupon|descuento|promo|amigo|invitación)');
        
        $filterGroup = new \Google\Service\SearchConsole\ApiDimensionFilterGroup();
        $filterGroup->setFilters([$filter]);
        $request->setDimensionFilterGroups([$filterGroup]);
        $request->setRowLimit(2000);

        try {
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            $rows = $response->getRows();
            if (!$rows) return [];

            $opps = [];
            foreach ($rows as $row) {
                $page = $row->keys[1];
                // Si el ranking se va a página de búsqueda o home, o posición > 10, es una oportunidad
                if (strpos($page, '/buscar?') !== false || $page == 'https://www.codigoamigo.com/' || $page == 'https://www.codigoamigo.com' || $row->position > 10) {
                    $opps[] = $row;
                }
            }

            usort($opps, function($a, $b) {
                return $b->impressions - $a->impressions;
            });

            return array_slice($opps, 0, 50);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene TODAS las keywords que generan impresiones para una marca específica
     * Filtra por la URL de la página de marca (/de-{slug})
     */
    public function getKeywordsForBrand($siteUrl, $brandSlug, $startDate, $endDate, $limit = 500) {
        $request = new \Google\Service\SearchConsole\SearchAnalyticsQueryRequest();
        $request->setStartDate($startDate);
        $request->setEndDate($endDate);
        $request->setDimensions(['query']);
        
        // Filtro por URL de la página de marca
        $filter = new \Google\Service\SearchConsole\ApiDimensionFilter();
        $filter->setDimension('page');
        $filter->setOperator('contains');
        $filter->setExpression('/de-' . $brandSlug);
        
        $filterGroup = new \Google\Service\SearchConsole\ApiDimensionFilterGroup();
        $filterGroup->setFilters([$filter]);
        $request->setDimensionFilterGroups([$filterGroup]);
        
        $request->setRowLimit($limit);

        try {
            $response = $this->service->searchanalytics->query($siteUrl, $request);
            $rows = $response->getRows();
            
            if (!$rows) return [];

            $keywords = [];
            foreach ($rows as $row) {
                $keywords[] = [
                    'kw' => $row->keys[0],
                    'clicks' => $row->clicks,
                    'impressions' => $row->impressions,
                    'position' => round($row->position, 1),
                    'ctr' => round($row->ctr * 100, 2),
                    'source' => 'gsc'
                ];
            }

            // Ordenar por impresiones descendente
            usort($keywords, function($a, $b) {
                return $b['impressions'] - $a['impressions'];
            });

            return $keywords;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

