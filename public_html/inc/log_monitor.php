<?php

class LogMonitor {
    
    private $logSources = [];
    
    public function __construct() {
        $this->initializeLogSources();
    }
    
    private function initializeLogSources() {
        // Usar logs copiados que son accesibles desde PHP
        $logBasePath = __DIR__ . '/../logs/system/';
        
        $this->logSources = [
            'apache_error' => [
                'path' => $logBasePath . 'apache_error.log',
                'name' => 'Apache Error',
                'type' => 'error',
                'parser' => 'apache_error'
            ],
            'apache_access' => [
                'path' => $logBasePath . 'apache_access.log',
                'name' => 'Apache Access',
                'type' => 'access',
                'parser' => 'apache_access'
            ],
            'php_error' => [
                'path' => $logBasePath . 'php_error.log',
                'name' => 'PHP Error',
                'type' => 'error',
                'parser' => 'php_error'
            ],
            'php_fpm' => [
                'path' => $logBasePath . 'php_fpm.log',
                'name' => 'PHP-FPM',
                'type' => 'error',
                'parser' => 'php_fpm'
            ],
            'nginx_error' => [
                'path' => $logBasePath . 'nginx_error.log',
                'name' => 'Nginx Error',
                'type' => 'error',
                'parser' => 'nginx_error'
            ],
            'nginx_access' => [
                'path' => $logBasePath . 'nginx_access.log',
                'name' => 'Nginx Access',
                'type' => 'access',
                'parser' => 'nginx_access'
            ],
            'mysql_error' => [
                'path' => $logBasePath . 'mysql_error.log',
                'name' => 'MySQL Error',
                'type' => 'error',
                'parser' => 'mysql_error'
            ],
            'system' => [
                'path' => $logBasePath . 'system.log',
                'name' => 'System',
                'type' => 'system',
                'parser' => 'system'
            ]
        ];
    }
    
    public function getLogSources() {
        return $this->logSources;
    }
    
    public function getLogSourcesStatus() {
        $status = [];
        
        foreach ($this->logSources as $key => $config) {
            $info = [
                'exists' => file_exists($config['path']),
                'readable' => is_readable($config['path']),
                'size' => file_exists($config['path']) ? filesize($config['path']) : 0,
                'modified' => file_exists($config['path']) ? date('Y-m-d H:i:s', filemtime($config['path'])) : null,
                'error' => null
            ];
            
            $status[$key] = array_merge($config, $info);
        }
        
        return $status;
    }
    
    public function readLogs($source = null, $lines = 100, $filters = []) {
        if ($source && isset($this->logSources[$source])) {
            return $this->readSingleLog($source, $lines, $filters);
        }
        
        $allLogs = [];
        foreach ($this->logSources as $key => $config) {
            $logs = $this->readSingleLog($key, $lines, $filters);
            $allLogs = array_merge($allLogs, $logs);
        }
        
        // Ordenar por timestamp descendente
        usort($allLogs, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });
        
        return $allLogs;
    }
    
    public function readSingleLog($source, $lines, $filters) {
        $config = $this->logSources[$source];
        
        if (!file_exists($config['path'])) {
            return [];
        }
        
        // Leer las últimas líneas del archivo
        $logLines = $this->tailFile($config['path'], $lines);
        $parsedLogs = [];
        
        foreach ($logLines as $line) {
            $parsedLine = $this->parseLogLine($line, $config['parser'], $source);
            
            if ($parsedLine && $this->matchesFilters($parsedLine, $filters)) {
                $parsedLogs[] = $parsedLine;
            }
        }
        
        return $parsedLogs;
    }
    
    private function tailFile($file, $lines) {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return [];
        }
        
        $lineCount = 0;
        $pos = -2;
        $text = '';
        
        // Ir al final del archivo
        fseek($handle, -1, SEEK_END);
        
        // Leer hacia atrás línea por línea
        while ($lineCount < $lines) {
            $char = fgetc($handle);
            if ($char === "\n") {
                $lineCount++;
            }
            $text = $char . $text;
            
            if (ftell($handle) <= 1) {
                break;
            }
            fseek($handle, -2, SEEK_CUR);
        }
        
        fclose($handle);
        
        return array_filter(explode("\n", $text));
    }
    
    private function parseLogLine($line, $parser, $source) {
        switch ($parser) {
            case 'apache_error':
                return $this->parseApacheError($line, $source);
            case 'apache_access':
                return $this->parseApacheAccess($line, $source);
            case 'php_error':
                return $this->parsePhpError($line, $source);
            case 'php_fpm':
                return $this->parsePhpFpm($line, $source);
            case 'nginx_error':
                return $this->parseNginxError($line, $source);
            case 'nginx_access':
                return $this->parseNginxAccess($line, $source);
            case 'mysql_error':
                return $this->parseMysqlError($line, $source);
            case 'system':
                return $this->parseSystemLog($line, $source);
            default:
                return $this->parseGenericLog($line, $source);
        }
    }
    
    private function parseApacheError($line, $source) {
        // Formato: [Wed Oct 09 08:22:11.123456 2024] [error] [client 192.168.1.1:12345] message
        if (preg_match('/^\[([^\]]+)\]\s+\[([^\]]+)\]\s+\[([^\]]+)\]\s+(.+)$/', $line, $matches)) {
            return [
                'timestamp' => strtotime($matches[1]),
                'level' => strtolower($matches[2]),
                'source' => $source,
                'message' => $matches[4],
                'raw' => $line,
                'type' => 'error'
            ];
        }
        return null;
    }
    
    private function parseApacheAccess($line, $source) {
        // Formato: 192.168.1.1 - - [09/Oct/2024:08:22:11 +0000] "GET /page HTTP/1.1" 200 1234
        if (preg_match('/^(\S+)\s+\S+\s+\S+\s+\[([^\]]+)\]\s+"([^"]+)"\s+(\d+)\s+(\S+)/', $line, $matches)) {
            $level = ($matches[4] >= 400) ? 'error' : (($matches[4] >= 300) ? 'warning' : 'info');
            return [
                'timestamp' => strtotime(str_replace(['/', ':'], ['-', ' '], $matches[2])),
                'level' => $level,
                'source' => $source,
                'message' => $matches[3],
                'status_code' => $matches[4],
                'ip' => $matches[1],
                'raw' => $line,
                'type' => 'access'
            ];
        }
        return null;
    }
    
    private function parsePhpError($line, $source) {
        // Formato: [09-Oct-2024 08:22:11 UTC] PHP Warning: message in file on line 123
        if (preg_match('/^\[([^\]]+)\]\s+PHP\s+(\w+):\s+(.+?)\s+in\s+(.+?)\s+on\s+line\s+(\d+)$/', $line, $matches)) {
            return [
                'timestamp' => strtotime($matches[1]),
                'level' => strtolower($matches[2]),
                'source' => $source,
                'message' => $matches[3],
                'file' => $matches[4],
                'line' => $matches[5],
                'raw' => $line,
                'type' => 'error'
            ];
        }
        return null;
    }
    
    private function parsePhpFpm($line, $source) {
        // Formato: [09-Oct-2024 08:22:11.123456] WARNING: [pool www] child 1234 exited with signal 11
        if (preg_match('/^\[([^\]]+)\]\s+(\w+):\s+(.+)$/', $line, $matches)) {
            return [
                'timestamp' => strtotime($matches[1]),
                'level' => strtolower($matches[2]),
                'source' => $source,
                'message' => $matches[3],
                'raw' => $line,
                'type' => 'system'
            ];
        }
        return null;
    }
    
    private function parseNginxError($line, $source) {
        // Formato: 2024/10/09 08:22:11 [error] 123#0: *1234 message
        if (preg_match('/^(\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})\s+\[(\w+)\]\s+(.+)$/', $line, $matches)) {
            return [
                'timestamp' => strtotime($matches[1]),
                'level' => strtolower($matches[2]),
                'source' => $source,
                'message' => $matches[3],
                'raw' => $line,
                'type' => 'error'
            ];
        }
        return null;
    }
    
    private function parseNginxAccess($line, $source) {
        // Formato: 192.168.1.1 - - [09/Oct/2024:08:22:11 +0000] "GET /page HTTP/1.1" 200 1234
        return $this->parseApacheAccess($line, $source);
    }
    
    private function parseMysqlError($line, $source) {
        // Formato: 2024-10-09T08:22:11.123456Z 0 [Note] [MY-010116] message
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z)\s+\d+\s+\[(\w+)\]\s+\[([^\]]+)\]\s+(.+)$/', $line, $matches)) {
            return [
                'timestamp' => strtotime($matches[1]),
                'level' => strtolower($matches[2]),
                'source' => $source,
                'message' => $matches[4],
                'code' => $matches[3],
                'raw' => $line,
                'type' => 'error'
            ];
        }
        return null;
    }
    
    private function parseSystemLog($line, $source) {
        // Formato genérico para logs del sistema
        return [
            'timestamp' => time(),
            'level' => 'info',
            'source' => $source,
            'message' => $line,
            'raw' => $line,
            'type' => 'system'
        ];
    }
    
    private function parseGenericLog($line, $source) {
        return [
            'timestamp' => time(),
            'level' => 'info',
            'source' => $source,
            'message' => $line,
            'raw' => $line,
            'type' => 'generic'
        ];
    }
    
    private function matchesFilters($log, $filters) {
        if (empty($filters)) {
            return true;
        }
        
        // Filtro por nivel
        if (isset($filters['level']) && $log['level'] !== $filters['level']) {
            return false;
        }
        
        // Filtro por fuente
        if (isset($filters['source']) && $log['source'] !== $filters['source']) {
            return false;
        }
        
        // Filtro por tipo
        if (isset($filters['type']) && $log['type'] !== $filters['type']) {
            return false;
        }
        
        // Filtro por búsqueda
        if (isset($filters['search']) && stripos($log['message'], $filters['search']) === false) {
            return false;
        }
        
        // Filtro por fecha
        if (isset($filters['date_from']) && $log['timestamp'] < strtotime($filters['date_from'])) {
            return false;
        }
        
        if (isset($filters['date_to']) && $log['timestamp'] > strtotime($filters['date_to'])) {
            return false;
        }
        
        return true;
    }
    
    public function getLogStats() {
        $stats = [
            'total_logs' => 0,
            'error_logs' => 0,
            'warning_logs' => 0,
            'info_logs' => 0,
            'by_source' => [],
            'recent_errors' => 0
        ];
        
        foreach ($this->logSources as $key => $config) {
            $logs = $this->readSingleLog($key, 1000, []);
            $sourceStats = [
                'total' => count($logs),
                'errors' => 0,
                'warnings' => 0,
                'info' => 0
            ];
            
            foreach ($logs as $log) {
                $stats['total_logs']++;
                $level = $log['level'] ?? 'info';
                $sourceStats[$level] = ($sourceStats[$level] ?? 0) + 1;
                
                if ($level === 'error') {
                    $stats['error_logs']++;
                    if ($log['timestamp'] > (time() - 3600)) { // Última hora
                        $stats['recent_errors']++;
                    }
                } elseif ($level === 'warning') {
                    $stats['warning_logs']++;
                } else {
                    $stats['info_logs']++;
                }
            }
            
            $stats['by_source'][$key] = $sourceStats;
        }
        
        return $stats;
    }
    
    public function getCriticalErrors() {
        $criticalErrors = [];
        $allLogs = $this->readLogs(null, 500, ['level' => 'error']);
        
        foreach ($allLogs as $log) {
            if ($this->isCriticalError($log)) {
                $criticalErrors[] = $log;
            }
        }
        
        return array_slice($criticalErrors, 0, 50); // Top 50 errores críticos
    }
    
    private function isCriticalError($log) {
        $criticalKeywords = [
            'fatal', 'critical', 'emergency', 'alert', 'panic',
            'database connection failed', 'memory limit', 'timeout',
            'permission denied', 'file not found', '404', '500',
            'mysql', 'mongodb', 'connection refused'
        ];
        
        $message = strtolower($log['message']);
        foreach ($criticalKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public function getRealTimeLogs($lastTimestamp = null) {
        $allLogs = $this->readLogs(null, 100);
        
        if ($lastTimestamp) {
            $allLogs = array_filter($allLogs, function($log) use ($lastTimestamp) {
                return $log['timestamp'] > $lastTimestamp;
            });
        }
        
        return $allLogs;
    }
}
