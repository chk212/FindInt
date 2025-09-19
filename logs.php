<?php
/**
 * Page de logs - FINDint
 * Affiche les logs du système en front-end
 */

// Charger la configuration
require_once 'config.php';

// Protection des droits d'auteur
require_once 'protection-droits-auteur.php';
protectCopyright();

// Fonction pour lire les logs
function readLogFile($filePath, $limit = 100) {
    if (!file_exists($filePath)) {
        return [];
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }
    
    // Inverser l'ordre pour avoir les plus récents en premier
    $lines = array_reverse($lines);
    
    // Limiter le nombre de lignes
    if ($limit > 0) {
        $lines = array_slice($lines, 0, $limit);
    }
    
    return $lines;
}

// Fonction pour parser une ligne de log
function parseLogLine($line) {
    $parsed = [
        'raw' => $line,
        'timestamp' => '',
        'level' => 'INFO',
        'message' => $line
    ];
    
    // Essayer de détecter un timestamp au début de la ligne
    if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', $line, $matches)) {
        $parsed['timestamp'] = $matches[1];
        $parsed['message'] = substr($line, strlen($matches[1]) + 1);
    }
    
    // Détecter le niveau de log
    if (stripos($line, 'error') !== false || stripos($line, 'erreur') !== false) {
        $parsed['level'] = 'ERROR';
    } elseif (stripos($line, 'warning') !== false || stripos($line, 'avertissement') !== false) {
        $parsed['level'] = 'WARNING';
    } elseif (stripos($line, 'debug') !== false) {
        $parsed['level'] = 'DEBUG';
    } elseif (stripos($line, 'succès') !== false || stripos($line, 'success') !== false) {
        $parsed['level'] = 'SUCCESS';
    }
    
    return $parsed;
}

// Paramètres de la page
$logType = isset($_GET['type']) ? $_GET['type'] : 'all';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Chemins des fichiers de logs
$logFiles = [
    'sent' => 'log/pdf_email_sent.log',
    'error' => 'log/pdf_email_error.log'
];

// Lire les logs selon le type sélectionné
$allLogs = [];

if ($logType === 'all' || $logType === 'sent') {
    $sentLogs = readLogFile($logFiles['sent'], 0); // Lire tous les logs
    foreach ($sentLogs as $line) {
        $parsed = parseLogLine($line);
        $parsed['type'] = 'sent';
        $allLogs[] = $parsed;
    }
}

if ($logType === 'all' || $logType === 'error') {
    $errorLogs = readLogFile($logFiles['error'], 0); // Lire tous les logs
    foreach ($errorLogs as $line) {
        $parsed = parseLogLine($line);
        $parsed['type'] = 'error';
        $allLogs[] = $parsed;
    }
}

// Trier par timestamp (plus récent en premier)
usort($allLogs, function($a, $b) {
    if (empty($a['timestamp']) && empty($b['timestamp'])) {
        return 0;
    }
    if (empty($a['timestamp'])) {
        return 1;
    }
    if (empty($b['timestamp'])) {
        return -1;
    }
    return strcmp($b['timestamp'], $a['timestamp']);
});

// Filtrer par recherche
if (!empty($search)) {
    $allLogs = array_filter($allLogs, function($log) use ($search) {
        return stripos($log['message'], $search) !== false;
    });
}

// Calculer la pagination
$totalLogs = count($allLogs);
$totalPages = ceil($totalLogs / $limit);
$offset = ($page - 1) * $limit;

// Appliquer la pagination
$allLogs = array_slice($allLogs, $offset, $limit);

// Statistiques (basées sur tous les logs, pas seulement la page actuelle)
$allLogsForStats = [];
if ($logType === 'all' || $logType === 'sent') {
    $sentLogs = readLogFile($logFiles['sent'], 0);
    foreach ($sentLogs as $line) {
        $parsed = parseLogLine($line);
        $parsed['type'] = 'sent';
        $allLogsForStats[] = $parsed;
    }
}
if ($logType === 'all' || $logType === 'error') {
    $errorLogs = readLogFile($logFiles['error'], 0);
    foreach ($errorLogs as $line) {
        $parsed = parseLogLine($line);
        $parsed['type'] = 'error';
        $allLogsForStats[] = $parsed;
    }
}

if (!empty($search)) {
    $allLogsForStats = array_filter($allLogsForStats, function($log) use ($search) {
        return stripos($log['message'], $search) !== false;
    });
}

$stats = [
    'total' => count($allLogsForStats),
    'sent' => count(array_filter($allLogsForStats, function($log) { return $log['type'] === 'sent'; })),
    'error' => count(array_filter($allLogsForStats, function($log) { return $log['type'] === 'error'; })),
    'success' => count(array_filter($allLogsForStats, function($log) { return $log['level'] === 'SUCCESS'; })),
    'debug' => count(array_filter($allLogsForStats, function($log) { return $log['level'] === 'DEBUG'; }))
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs du Système - FINDint</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="styles.css">
    <style>
        .logs-container {
            max-width: var(--container-max);
            margin: 0 auto;
            background: var(--color-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        
        .logs-header {
            background: #1e293b;
            color: white;
            padding: var(--space-4);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--space-2);
        }
        
        .logs-title {
            margin: 0;
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 700;
        }
        
        .logs-controls {
            width: 100%;
        }
        
        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-3);
            align-items: end;
        }
        
        .control-item {
            display: flex;
            flex-direction: column;
            gap: var(--space-1);
        }
        
        .control-item label {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .control-item select,
        .control-item input {
            padding: 10px 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-sm);
            font-size: 14px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(10px);
            transition: all 0.2s ease;
        }
        
        .control-item select:focus,
        .control-item input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
        }
        
        .control-item input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .search-item {
            grid-column: span 2;
        }
        
        .control-actions {
            display: flex;
            gap: var(--space-2);
            align-items: end;
        }
        
        .btn {
            padding: 8px 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .btn-primary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .btn-primary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-3);
            margin: var(--space-4);
        }
        
        .stat-card {
            background: var(--color-bg);
            padding: var(--space-3);
            border-radius: var(--radius-md);
            text-align: center;
            border-left: 4px solid var(--accent);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-card.error {
            border-left-color: #dc3545;
        }
        
        .stat-card.success {
            border-left-color: #28a745;
        }
        
        .stat-card.debug {
            border-left-color: #ffc107;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: var(--space-1);
        }
        
        .stat-label {
            color: var(--color-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .logs-list {
            background: var(--color-bg);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin: var(--space-4);
            border: 1px solid var(--color-border);
        }
        
        .log-entry {
            padding: var(--space-3);
            border-bottom: 1px solid var(--color-border);
            transition: background-color 0.2s ease;
        }
        
        .log-entry:hover {
            background-color: var(--color-surface);
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-1);
            flex-wrap: wrap;
            gap: var(--space-1);
        }
        
        .log-level {
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .log-level.INFO {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .log-level.SUCCESS {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .log-level.WARNING {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .log-level.ERROR {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .log-level.DEBUG {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .log-timestamp {
            color: var(--color-muted);
            font-size: 0.85rem;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        }
        
        .log-message {
            color: var(--color-text);
            line-height: 1.6;
            word-break: break-word;
            font-size: 0.9rem;
        }
        
        .log-type {
            display: inline-block;
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: var(--space-1);
            letter-spacing: 0.5px;
        }
        
        .log-type.sent {
            background: #d4edda;
            color: #155724;
        }
        
        .log-type.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-logs {
            text-align: center;
            padding: var(--space-6);
            color: var(--color-muted);
            font-style: italic;
        }
        
        .search-highlight {
            background: #fff3cd;
            padding: 2px 4px;
            border-radius: var(--radius-sm);
            font-weight: 600;
        }
        
        .pagination-container {
            margin: var(--space-4);
            text-align: center;
        }
        
        .pagination-info {
            margin-bottom: var(--space-2);
            color: var(--color-muted);
            font-size: 0.9rem;
        }
        
        .pagination-controls {
            display: flex;
            justify-content: center;
            gap: var(--space-1);
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .logs-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .controls-grid {
                grid-template-columns: 1fr;
                gap: var(--space-2);
            }
            
            .search-item {
                grid-column: span 1;
            }
            
            .control-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .control-actions .btn {
                width: 100%;
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .log-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logs-container">
            <div class="logs-header">
                <h1 class="logs-title">Logs du Système</h1>
                <div class="logs-controls">
                    <div class="controls-grid">
                        <div class="control-item">
                            <label for="type">Type de logs</label>
                            <select id="type" name="type" onchange="updateFilters()">
                                <option value="all" <?php echo $logType === 'all' ? 'selected' : ''; ?>>Tous les logs</option>
                                <option value="sent" <?php echo $logType === 'sent' ? 'selected' : ''; ?>>Emails envoyés</option>
                                <option value="error" <?php echo $logType === 'error' ? 'selected' : ''; ?>>Erreurs uniquement</option>
                            </select>
                        </div>
                        
                        <div class="control-item">
                            <label for="limit">Nombre par page</label>
                            <select id="limit" name="limit" onchange="updateFilters()">
                                <option value="25" <?php echo $limit === 25 ? 'selected' : ''; ?>>25 logs</option>
                                <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50 logs</option>
                                <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100 logs</option>
                                <option value="200" <?php echo $limit === 200 ? 'selected' : ''; ?>>200 logs</option>
                            </select>
                        </div>
                        
                        <div class="control-item search-item">
                            <label for="search">Recherche</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tapez pour rechercher...">
                        </div>
                        
                        <div class="control-actions">
                            <button class="btn btn-primary" onclick="updateFilters()">Appliquer</button>
                            <a href="generer-lettre.php" class="btn btn-secondary">Retour</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-number"><?php echo $stats['sent']; ?></div>
                    <div class="stat-label">Emails envoyés</div>
                </div>
                <div class="stat-card error">
                    <div class="stat-number"><?php echo $stats['error']; ?></div>
                    <div class="stat-label">Erreurs</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-number"><?php echo $stats['success']; ?></div>
                    <div class="stat-label">Succès</div>
                </div>
                <div class="stat-card debug">
                    <div class="stat-number"><?php echo $stats['debug']; ?></div>
                    <div class="stat-label">Debug</div>
                </div>
            </div>
            
            <div class="logs-list">
                <?php if (empty($allLogs)): ?>
                    <div class="no-logs">
                        <h3>Aucun log trouvé</h3>
                        <p>Il n'y a pas de logs correspondant à vos critères de recherche.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($allLogs as $log): ?>
                        <div class="log-entry">
                            <div class="log-header">
                                <span class="log-level <?php echo $log['level']; ?>">
                                    <?php echo $log['level']; ?>
                                </span>
                                <span class="log-type <?php echo $log['type']; ?>">
                                    <?php echo $log['type']; ?>
                                </span>
                                <span class="log-timestamp">
                                    <?php echo !empty($log['timestamp']) ? $log['timestamp'] : 'N/A'; ?>
                                </span>
                            </div>
                            <div class="log-message">
                                <?php 
                                $message = htmlspecialchars($log['message']);
                                if (!empty($search)) {
                                    $message = str_ireplace($search, '<span class="search-highlight">' . htmlspecialchars($search) . '</span>', $message);
                                }
                                echo $message;
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination-container" style="margin-top: 30px; text-align: center;">
                <div class="pagination-info" style="margin-bottom: 15px; color: #6c757d; font-size: 0.9rem;">
                    Page <?php echo $page; ?> sur <?php echo $totalPages; ?> 
                    (<?php echo $totalLogs; ?> logs au total)
                </div>
                <div class="pagination-controls" style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="btn btn-secondary">Première</a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-secondary">Précédente</a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-secondary">Suivante</a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>" class="btn btn-secondary">Dernière</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function updateFilters() {
            const type = document.getElementById('type').value;
            const limit = document.getElementById('limit').value;
            const search = document.getElementById('search').value;
            
            const params = new URLSearchParams();
            if (type !== 'all') params.append('type', type);
            if (limit !== '50') params.append('limit', limit);
            if (search) params.append('search', search);
            
            window.location.href = 'logs.php?' + params.toString();
        }
        
        // Auto-refresh toutes les 30 secondes
        setTimeout(function() {
            window.location.reload();
        }, 30000);
        
        // Recherche en temps réel
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(updateFilters, 500);
        });
    </script>
</body>
</html>
