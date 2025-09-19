<?php
/**
 * Protection des droits d'auteur - FINDint
 * 
 * Ce fichier protège les droits d'auteur de CHAFIK EL HIRACH
 * et empêche toute utilisation non autorisée du code.
 */

// Charger la configuration
require_once 'config.php';

class CopyrightProtection {
    private $author = 'CHAFIK EL HIRACH';
    private $authorEmail = 'chafik.elhirach@chk-elh.fr';
    private $portfolioUrl = 'https://example.com/';
    private $appName = 'FINDint';
    private $version = '1.0.0';
    
    /**
     * Vérifier que les droits d'auteur sont respectés
     */
    public function checkCopyright() {
        $violations = [];
        
        // Vérifier que l'auteur n'a pas été modifié
        $currentAuthor = getConfig('app.author');
        if ($currentAuthor !== $this->author) {
            $violations[] = "Violation des droits d'auteur: L'auteur a été modifié de '{$this->author}' vers '{$currentAuthor}'";
        }
        
        // Vérifier que le nom de l'application n'a pas été modifié
        $currentAppName = getConfig('app.name');
        if ($currentAppName !== $this->appName) {
            $violations[] = "Violation des droits d'auteur: Le nom de l'application a été modifié de '{$this->appName}' vers '{$currentAppName}'";
        }
        
        return $violations;
    }
    
    /**
     * Afficher le footer de copyright
     */
    public function displayCopyright() {
        return "
        <div style='text-align: center; margin-top: 20px; padding: 15px; background: #f8f9fa; border-top: 1px solid #e9ecef; font-size: 0.9rem; color: #6c757d;'>
            <strong>&copy; " . date('Y') . " {$this->appName}</strong> - Tous droits réservés<br>
            <span style='color: #495057; font-weight: 500;'>Développé par {$this->author}</span><br>
            <a href='mailto:{$this->authorEmail}' style='color: #007bff; text-decoration: none; font-weight: 500;'>{$this->authorEmail}</a> | 
            <a href='{$this->portfolioUrl}' target='_blank' style='color: #007bff; text-decoration: none; font-weight: 500;'>Portfolio</a>
        </div>";
    }
    
    /**
     * Afficher un avertissement de copyright
     */
    public function displayCopyrightWarning() {
        return "
        <div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 0.9rem;'>
            <strong>Avertissement Copyright:</strong> Ce logiciel est la propriété exclusive de {$this->author}. 
            Toute reproduction, distribution ou modification sans autorisation expresse est interdite.
        </div>";
    }
    
    /**
     * Générer un script de protection JavaScript
     */
    public function generateProtectionScript() {
        return "
        <script>
        // Protection des droits d'auteur - FINDint
        document.addEventListener('DOMContentLoaded', function() {
            // Empêcher l'inspection des éléments sensibles
            const protectedElements = document.querySelectorAll('[data-copyright]');
            protectedElements.forEach(element => {
                element.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    return false;
                });
            });
            
            // Avertissement dans la console
            console.log('%cPROTECTION COPYRIGHT', 'color: #dc3545; font-size: 20px; font-weight: bold;');
            console.log('%cCe logiciel est protégé par les droits d\\'auteur de CHAFIK EL HIRACH', 'color: #dc3545; font-size: 14px;');
            console.log('%cToute utilisation non autorisée est interdite', 'color: #dc3545; font-size: 12px;');
            console.log('%cContact: chafik.elhirach@chk-elh.fr', 'color: #007bff; font-size: 12px;');
            
            // Empêcher le copier-coller du code source
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && (e.key === 'u' || e.key === 'U')) {
                    e.preventDefault();
                    alert('Accès au code source interdit - Protection copyright CHAFIK EL HIRACH');
                    return false;
                }
            });
        });
        
        // Détecter les tentatives d'inspection
        let devtools = {open: false, orientation: null};
        setInterval(function() {
            if (window.outerHeight - window.innerHeight > 200 || window.outerWidth - window.innerWidth > 200) {
                if (!devtools.open) {
                    devtools.open = true;
                    console.clear();
                    console.log('%cOUTIL DE DÉVELOPPEMENT DÉTECTÉ', 'color: #dc3545; font-size: 16px; font-weight: bold;');
                    console.log('%cCe logiciel est protégé par les droits d\\'auteur', 'color: #dc3545; font-size: 12px;');
                }
            }
        }, 500);
        </script>";
    }
    
    /**
     * Vérifier et bloquer si violation détectée
     */
    public function enforceCopyright() {
        $violations = $this->checkCopyright();
        
        if (!empty($violations)) {
            // Log de la violation
            $logMessage = "[" . date('Y-m-d H:i:s') . "] VIOLATION COPYRIGHT DÉTECTÉE:\n";
            foreach ($violations as $violation) {
                $logMessage .= "- " . $violation . "\n";
            }
            $logMessage .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
            $logMessage .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n";
            $logMessage .= "---\n";
            
            file_put_contents('log/copyright_violations.log', $logMessage, FILE_APPEND | LOCK_EX);
            
            // Bloquer l'accès
            http_response_code(403);
            die($this->getBlockedPage());
        }
    }
    
    /**
     * Page de blocage en cas de violation
     */
    private function getBlockedPage() {
        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <title>Accès refusé - Violation des droits d'auteur</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .error { color: #dc3545; font-size: 24px; font-weight: bold; margin-bottom: 20px; }
                .message { color: #6c757d; line-height: 1.6; margin-bottom: 30px; }
                .contact { background: #e9ecef; padding: 20px; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='error'>Accès refusé</div>
                <div class='message'>
                    <strong>Violation des droits d'auteur détectée</strong><br><br>
                    Ce logiciel est la propriété exclusive de <strong>{$this->author}</strong>.<br>
                    Toute modification, reproduction ou distribution sans autorisation expresse est interdite.<br><br>
                    Si vous souhaitez utiliser ce logiciel, veuillez contacter l'auteur.
                </div>
                <div class='contact'>
                    <strong>Contact:</strong><br>
                    Email: <a href='mailto:{$this->authorEmail}'>{$this->authorEmail}</a><br>
                    Portfolio: <a href='{$this->portfolioUrl}' target='_blank'>{$this->portfolioUrl}</a>
                </div>
            </div>
        </body>
        </html>";
    }
}

// Fonction utilitaire pour protéger automatiquement les pages
function protectCopyright() {
    $protection = new CopyrightProtection();
    $protection->enforceCopyright();
    return $protection;
}

// Fonction pour afficher le footer de copyright
function displayCopyrightFooter() {
    $protection = new CopyrightProtection();
    return $protection->displayCopyright();
}

// Fonction pour générer le script de protection
function generateCopyrightScript() {
    $protection = new CopyrightProtection();
    return $protection->generateProtectionScript();
}
?>
