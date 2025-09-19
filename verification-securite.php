<?php
/**
 * Fichier de vérification de sécurité pour FINDint
 * 
 * Ce fichier vérifie que les paramètres critiques de l'application
 * n'ont pas été modifiés de manière inappropriée.
 */

// Charger la configuration
require_once 'config.php';

class SecurityChecker {
    private $protectedSettings = [
        'app.name' => 'FINDint',
        'app.version' => '1.0.0',
        'app.author' => 'CHAFIK EL HIRACH'
    ];
    
    private $errors = [];
    
    /**
     * Vérifier la sécurité de la configuration
     */
    public function checkSecurity() {
        $this->checkProtectedSettings();
        $this->checkFileIntegrity();
        $this->checkPermissions();
        
        return empty($this->errors);
    }
    
    /**
     * Vérifier que les paramètres protégés n'ont pas été modifiés
     */
    private function checkProtectedSettings() {
        foreach ($this->protectedSettings as $key => $expectedValue) {
            $actualValue = getConfig($key);
            if ($actualValue !== $expectedValue) {
                $this->errors[] = "Paramètre protégé modifié: $key (attendu: $expectedValue, trouvé: $actualValue)";
            }
        }
    }
    
    /**
     * Vérifier l'intégrité des fichiers critiques
     */
    private function checkFileIntegrity() {
        $criticalFiles = [
            'config.php',
            'generer-lettre.php',
            'configuration.php'
        ];
        
        foreach ($criticalFiles as $file) {
            if (!file_exists($file)) {
                $this->errors[] = "Fichier critique manquant: $file";
            }
        }
    }
    
    /**
     * Vérifier les permissions des fichiers
     */
    private function checkPermissions() {
        $configFile = 'config.local.php';
        if (file_exists($configFile)) {
            $perms = fileperms($configFile);
            if (($perms & 0x0002) || ($perms & 0x0020)) {
                $this->errors[] = "Permissions trop permissives sur $configFile";
            }
        }
    }
    
    /**
     * Obtenir la liste des erreurs
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Générer un rapport de sécurité
     */
    public function generateReport() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'secure' => $this->checkSecurity(),
            'errors' => $this->errors,
            'protected_settings' => $this->protectedSettings,
            'config_file_exists' => file_exists('config.local.php'),
            'app_version' => getConfig('app.version'),
            'app_name' => getConfig('app.name'),
            'app_author' => getConfig('app.author')
        ];
        
        return $report;
    }
}

// Fonction utilitaire pour vérifier la sécurité
function checkAppSecurity() {
    $checker = new SecurityChecker();
    return $checker->generateReport();
}

// Si ce fichier est appelé directement, afficher le rapport
if (basename($_SERVER['PHP_SELF']) === 'verification-securite.php') {
    header('Content-Type: application/json');
    echo json_encode(checkAppSecurity(), JSON_PRETTY_PRINT);
    exit;
}
?>
