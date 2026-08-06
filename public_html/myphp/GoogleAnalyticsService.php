<?php
include_once __DIR__ . '/../inc/logger.php';
/**
 * Google Analytics Service
 * Handles GA4 API integration for CodigoAmigo and Casinuevo
 */

class GoogleAnalyticsService {
    
    private $client;
    private $propertyIds = [
        'codigoamigo' => 'properties/YOUR_CODIGOAMIGO_PROPERTY_ID',
        'casinuevo' => 'properties/YOUR_CASINUEVO_PROPERTY_ID'
    ];
    
    public function __construct() {
        $this->initializeClient();
    }
    
    /**
     * Initialize Google API Client
     */
    private function initializeClient() {
        try {
            $this->client = new Google_Client();
            
            // Try to load service account credentials
            $credentialsPath = __DIR__ . '/../config/google-analytics-credentials.json';
            
            if (file_exists($credentialsPath)) {
                $this->client->setAuthConfig($credentialsPath);
                $this->client->addScope('https://www.googleapis.com/auth/analytics.readonly');
            } else {
                log_error('Google Analytics credentials file not found at: ' . $credentialsPath);
            }
        } catch (Exception $e) {
            log_error('Error initializing Google Analytics client: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get analytics data for a specific site and date range
     */
    public function getAnalyticsData($site = 'codigoamigo', $dateRange = '30daysAgo', $endDate = 'today') {
        if (!isset($this->propertyIds[$site])) {
            throw new Exception('Invalid site specified');
        }
        
        $propertyId = $this->propertyIds[$site];
        
        try {
            $analyticsData = new Google_Service_AnalyticsData($this->client);
            
            // Build the request
            $request = new Google_Service_AnalyticsData_RunReportRequest();
            $request->setProperty($propertyId);
            
            // Date range
            $dateRangeObj = new Google_Service_AnalyticsData_DateRange();
            $dateRangeObj->setStartDate($dateRange);
            $dateRangeObj->setEndDate($endDate);
            $request->setDateRanges([$dateRangeObj]);
            
            // Metrics
            $metrics = [
                $this->createMetric('activeUsers'),
                $this->createMetric('sessions'),
                $this->createMetric('screenPageViews'),
                $this->createMetric('averageSessionDuration'),
                $this->createMetric('bounceRate'),
                $this->createMetric('conversions')
            ];
            $request->setMetrics($metrics);
            
            // Dimensions for breakdown
            $dimensions = [
                $this->createDimension('date')
            ];
            $request->setDimensions($dimensions);
            
            // Execute request
            $response = $analyticsData->properties->runReport($propertyId, $request);
            
            return $this->parseResponse($response);
            
        } catch (Exception $e) {
            log_error('Error fetching analytics data: ' . $e->getMessage());
            return $this->getMockData($site); // Fallback to mock data for development
        }
    }
    
    /**
     * Get top pages data
     */
    public function getTopPages($site = 'codigoamigo', $dateRange = '30daysAgo', $limit = 10) {
        if (!isset($this->propertyIds[$site])) {
            throw new Exception('Invalid site specified');
        }
        
        $propertyId = $this->propertyIds[$site];
        
        try {
            $analyticsData = new Google_Service_AnalyticsData($this->client);
            
            $request = new Google_Service_AnalyticsData_RunReportRequest();
            $request->setProperty($propertyId);
            
            $dateRangeObj = new Google_Service_AnalyticsData_DateRange();
            $dateRangeObj->setStartDate($dateRange);
            $dateRangeObj->setEndDate('today');
            $request->setDateRanges([$dateRangeObj]);
            
            $metrics = [
                $this->createMetric('screenPageViews'),
                $this->createMetric('activeUsers')
            ];
            $request->setMetrics($metrics);
            
            $dimensions = [
                $this->createDimension('pageTitle'),
                $this->createDimension('pagePath')
            ];
            $request->setDimensions($dimensions);
            
            // Order by pageviews
            $orderBy = new Google_Service_AnalyticsData_OrderBy();
            $metricOrderBy = new Google_Service_AnalyticsData_MetricOrderBy();
            $metricOrderBy->setMetricName('screenPageViews');
            $orderBy->setMetric($metricOrderBy);
            $orderBy->setDesc(true);
            $request->setOrderBys([$orderBy]);
            
            $request->setLimit($limit);
            
            $response = $analyticsData->properties->runReport($propertyId, $request);
            
            return $this->parseTopPagesResponse($response);
            
        } catch (Exception $e) {
            log_error('Error fetching top pages: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get traffic sources data
     */
    public function getTrafficSources($site = 'codigoamigo', $dateRange = '30daysAgo') {
        if (!isset($this->propertyIds[$site])) {
            throw new Exception('Invalid site specified');
        }
        
        $propertyId = $this->propertyIds[$site];
        
        try {
            $analyticsData = new Google_Service_AnalyticsData($this->client);
            
            $request = new Google_Service_AnalyticsData_RunReportRequest();
            $request->setProperty($propertyId);
            
            $dateRangeObj = new Google_Service_AnalyticsData_DateRange();
            $dateRangeObj->setStartDate($dateRange);
            $dateRangeObj->setEndDate('today');
            $request->setDateRanges([$dateRangeObj]);
            
            $metrics = [
                $this->createMetric('sessions'),
                $this->createMetric('activeUsers')
            ];
            $request->setMetrics($metrics);
            
            $dimensions = [
                $this->createDimension('sessionSource'),
                $this->createDimension('sessionMedium')
            ];
            $request->setDimensions($dimensions);
            
            $response = $analyticsData->properties->runReport($propertyId, $request);
            
            return $this->parseTrafficSourcesResponse($response);
            
        } catch (Exception $e) {
            log_error('Error fetching traffic sources: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get device breakdown
     */
    public function getDeviceBreakdown($site = 'codigoamigo', $dateRange = '30daysAgo') {
        if (!isset($this->propertyIds[$site])) {
            throw new Exception('Invalid site specified');
        }
        
        $propertyId = $this->propertyIds[$site];
        
        try {
            $analyticsData = new Google_Service_AnalyticsData($this->client);
            
            $request = new Google_Service_AnalyticsData_RunReportRequest();
            $request->setProperty($propertyId);
            
            $dateRangeObj = new Google_Service_AnalyticsData_DateRange();
            $dateRangeObj->setStartDate($dateRange);
            $dateRangeObj->setEndDate('today');
            $request->setDateRanges([$dateRangeObj]);
            
            $metrics = [
                $this->createMetric('activeUsers'),
                $this->createMetric('sessions')
            ];
            $request->setMetrics($metrics);
            
            $dimensions = [
                $this->createDimension('deviceCategory')
            ];
            $request->setDimensions($dimensions);
            
            $response = $analyticsData->properties->runReport($propertyId, $request);
            
            return $this->parseDeviceResponse($response);
            
        } catch (Exception $e) {
            log_error('Error fetching device breakdown: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Helper: Create metric object
     */
    private function createMetric($name) {
        $metric = new Google_Service_AnalyticsData_Metric();
        $metric->setName($name);
        return $metric;
    }
    
    /**
     * Helper: Create dimension object
     */
    private function createDimension($name) {
        $dimension = new Google_Service_AnalyticsData_Dimension();
        $dimension->setName($name);
        return $dimension;
    }
    
    /**
     * Parse main analytics response
     */
    private function parseResponse($response) {
        $data = [
            'totals' => [],
            'timeline' => []
        ];
        
        if (!$response->getRows()) {
            return $data;
        }
        
        // Calculate totals
        $totalUsers = 0;
        $totalSessions = 0;
        $totalPageviews = 0;
        $totalDuration = 0;
        $totalBounceRate = 0;
        $totalConversions = 0;
        $rowCount = 0;
        
        foreach ($response->getRows() as $row) {
            $metrics = $row->getMetricValues();
            $dimensions = $row->getDimensionValues();
            
            $users = (int) $metrics[0]->getValue();
            $sessions = (int) $metrics[1]->getValue();
            $pageviews = (int) $metrics[2]->getValue();
            $duration = (float) $metrics[3]->getValue();
            $bounceRate = (float) $metrics[4]->getValue();
            $conversions = (int) $metrics[5]->getValue();
            
            $totalUsers += $users;
            $totalSessions += $sessions;
            $totalPageviews += $pageviews;
            $totalDuration += $duration;
            $totalBounceRate += $bounceRate;
            $totalConversions += $conversions;
            $rowCount++;
            
            // Timeline data
            $data['timeline'][] = [
                'date' => $dimensions[0]->getValue(),
                'users' => $users,
                'sessions' => $sessions,
                'pageviews' => $pageviews
            ];
        }
        
        $data['totals'] = [
            'users' => $totalUsers,
            'sessions' => $totalSessions,
            'pageviews' => $totalPageviews,
            'avgSessionDuration' => $rowCount > 0 ? round($totalDuration / $rowCount, 2) : 0,
            'bounceRate' => $rowCount > 0 ? round($totalBounceRate / $rowCount, 2) : 0,
            'conversions' => $totalConversions
        ];
        
        return $data;
    }
    
    /**
     * Parse top pages response
     */
    private function parseTopPagesResponse($response) {
        $pages = [];
        
        if (!$response->getRows()) {
            return $pages;
        }
        
        foreach ($response->getRows() as $row) {
            $dimensions = $row->getDimensionValues();
            $metrics = $row->getMetricValues();
            
            $pages[] = [
                'title' => $dimensions[0]->getValue(),
                'path' => $dimensions[1]->getValue(),
                'pageviews' => (int) $metrics[0]->getValue(),
                'users' => (int) $metrics[1]->getValue()
            ];
        }
        
        return $pages;
    }
    
    /**
     * Parse traffic sources response
     */
    private function parseTrafficSourcesResponse($response) {
        $sources = [];
        
        if (!$response->getRows()) {
            return $sources;
        }
        
        foreach ($response->getRows() as $row) {
            $dimensions = $row->getDimensionValues();
            $metrics = $row->getMetricValues();
            
            $source = $dimensions[0]->getValue();
            $medium = $dimensions[1]->getValue();
            
            $sources[] = [
                'source' => $source . ' / ' . $medium,
                'sessions' => (int) $metrics[0]->getValue(),
                'users' => (int) $metrics[1]->getValue()
            ];
        }
        
        return $sources;
    }
    
    /**
     * Parse device response
     */
    private function parseDeviceResponse($response) {
        $devices = [];
        
        if (!$response->getRows()) {
            return $devices;
        }
        
        foreach ($response->getRows() as $row) {
            $dimensions = $row->getDimensionValues();
            $metrics = $row->getMetricValues();
            
            $devices[] = [
                'device' => $dimensions[0]->getValue(),
                'users' => (int) $metrics[0]->getValue(),
                'sessions' => (int) $metrics[1]->getValue()
            ];
        }
        
        return $devices;
    }
    
    /**
     * Get mock data for development/testing
     */
    private function getMockData($site) {
        return [
            'totals' => [
                'users' => rand(10000, 50000),
                'sessions' => rand(15000, 60000),
                'pageviews' => rand(50000, 200000),
                'avgSessionDuration' => rand(120, 300),
                'bounceRate' => rand(40, 60),
                'conversions' => rand(100, 500)
            ],
            'timeline' => $this->generateMockTimeline(30)
        ];
    }
    
    /**
     * Generate mock timeline data
     */
    private function generateMockTimeline($days) {
        $timeline = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = date('Ymd', strtotime("-$i days"));
            $timeline[] = [
                'date' => $date,
                'users' => rand(300, 2000),
                'sessions' => rand(500, 3000),
                'pageviews' => rand(1500, 8000)
            ];
        }
        return $timeline;
    }
}
