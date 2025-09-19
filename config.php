<?php
/**
 * Configuration centralisée pour FINDint - Générateur de Lettres de Motivation
 * 
 * Ce fichier charge la configuration locale si elle existe, sinon utilise les valeurs par défaut.
 * Pour personnaliser, modifiez config.local.php
 */

// Charger la configuration (locale si elle existe, sinon par défaut)
$configData = [];

if (file_exists(__DIR__ . '/config.local.php')) {
    $configData = require __DIR__ . '/config.local.php';
} else {
    // Configuration par défaut (valeurs d'exemple)
    $configData = [
        
        // === CONFIGURATION EMAIL ===
        'email' => [
            // Configuration SMTP
            'smtp' => [
                'host' => 'smtp.votre-fournisseur.com',
                'port' => 465,
                'encryption' => 'ssl',
                'username' => 'votre-email@votre-domaine.com',
                'password' => '',
                'debug' => 0,
            ],
            
            // Configuration de l'expéditeur
            'from' => [
                'email' => 'votre-email@votre-domaine.com',
                'name' => 'Votre Nom Prénom',
                'signature_image' => 'https://votre-domaine.com/signature.jpg'
            ],
            
            // Email pour les copies cachées (BCC)
            'bcc_email' => 'votre-email@votre-domaine.com',
            
            // Objet par défaut de l'email
            'subject' => 'Candidature pour un stage – [Votre Formation] ([Période])',
            
            // Corps du message par défaut
            'body_template' => 'Bonjour Madame, Monsieur,<br><br>
Je vous adresse en pièce jointe ma lettre de motivation personnalisée ainsi que mon CV dans le cadre d\'une candidature à un stage au sein de {entreprise}, [période du stage].<br><br>
Je suis à votre entière disposition pour un entretien afin d\'échanger plus en détail sur mes motivations, mes compétences et la manière dont je pourrais contribuer à vos projets.<br><br>
Je vous remercie sincèrement pour l\'attention portée à ma candidature et espère pouvoir prochainement collaborer avec votre équipe.<br><br>
Bien cordialement,<br><br>

<table style="font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6;">
    <tbody>
        <tr>
            <td style="padding: 0; vertical-align: top;">
                <div>
                    <img style="width: 80px; height: auto; border-radius: 50%; margin-right: 10px;" alt="Profil" src="{signature_image}"><br>
                </div>
            </td>
            <td style="padding: 0; vertical-align: top;">
                <p style="margin: 0; font-weight: bold; font-size: 16px; color: #1a73e8;">{nom_expediteur}<br></p>
                <p style="margin: 0; font-size: 14px; color: #666;">Étudiant en Informatique<br></p>
                <p style="margin: 0; font-size: 14px; color: #666;">Email: {email_expediteur}</p>
                <p style="margin: 0; font-size: 14px; color: #666;">Mon Portfolio : {portfolio_url} <br></p>
                <div><hr style="border: 0; border-top: 1px solid #ccc;"><br></div>
            </td>
        </tr>
    </tbody>
</table>
<div><br></div>',
            
            // Version texte du message
            'body_text' => 'Bonjour Madame, Monsieur,

Je vous adresse en pièce jointe ma lettre de motivation personnalisée ainsi que mon CV dans le cadre d\'une candidature à un stage d\'Administrateur Système au sein de {entreprise}, pour la période d\'avril à juin.

Réaliser ce stage au sein de {entreprise} représenterait pour moi la concrétisation d\'une opportunité : de prendre part à la gestion des infrastructures informatiques et mettre en œuvre mes compétences techniques (Linux, Windows Server, VPS).

Je suis à votre entière disposition pour un entretien afin d\'échanger plus en détail sur mes motivations, mes compétences et la manière dont je pourrais contribuer à vos projets.

Je vous remercie sincèrement pour l\'attention portée à ma candidature et espère pouvoir prochainement collaborer avec votre équipe.

Bien cordialement,

{nom_expediteur}'
        ],
        
        // === CONFIGURATION DU CANDIDAT ===
        'candidat' => [
            'nom' => 'Votre Nom Prénom',
            'email' => 'votre-email@votre-domaine.com',
            'portfolio_url' => 'https://votre-portfolio.com/',
            'poste_recherche' => '[Votre Poste Recherché]',
            'periode_stage' => '[Période du stage]',
            'formation' => '[Votre Formation]'
        ],
        
        // === CONFIGURATION DES FICHIERS ===
        'files' => [
            'template_path' => 'modele-lettre.docx',
            'cv_path' => 'CV_Votre_Nom_2025.pdf',
            'lettres_folder' => 'lettre/',
            'logs_folder' => 'log/',
            'csv_file' => 'candidatures.csv'
        ],
        
        // === CONFIGURATION DE L'APPLICATION ===
        'app' => [
            'name' => 'FINDint',
            'version' => '1.0.0',
            'author' => 'CHAFIK EL HIRACH',
            'debug' => false,
            'timezone' => 'Europe/Paris',
            'charset' => 'UTF-8'
        ],
        
        // === CONFIGURATION DES NOTIFICATIONS ===
        'notifications' => [
            'position' => 'top-right',
            'animation' => 'slide-in-right',
            'auto_close' => true,
            'auto_close_delay' => 5000
        ]
    ];
}

// Fonctions utilitaires (définies une seule fois)
if (!function_exists('getConfig')) {
    /**
     * Fonction pour récupérer une configuration
     * 
     * @param string $key Clé de configuration (ex: 'email.smtp.host')
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed
     */
    function getConfig($key, $default = null) {
        global $configData;
        
        $keys = explode('.', $key);
        $value = $configData;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        // Traiter les balises dynamiques
        if (is_string($value)) {
            $value = processDynamicTags($value);
        }
        
        return $value;
    }
    
}

// Fonction pour traiter les balises dynamiques (définie une seule fois)
if (!function_exists('processDynamicTags')) {
    function processDynamicTags($value) {
        // Définir les valeurs par défaut pour les balises
        $defaultValues = [
            '{candidat_nom}' => 'Votre Nom',
            '{candidat_email}' => 'votre-email@example.com',
            '{candidat_portfolio_url}' => 'https://votre-portfolio.com',
            '{candidat_signature_image}' => 'https://votre-site.com/signature.jpg',
            // Note: {entreprise} n'est pas remplacé ici car elle est gérée dynamiquement dans generer-lettre.php
        ];
        
        // Remplacer les balises par les valeurs par défaut
        foreach ($defaultValues as $tag => $default) {
            $value = str_replace($tag, $default, $value);
        }
        
        return $value;
    }
}

if (!function_exists('validateConfig')) {
    /**
     * Fonction pour vérifier si la configuration est valide
     * 
     * @return array Liste des erreurs de configuration
     */
    function validateConfig() {
        $errors = [];
        
        // Vérifier les fichiers requis
        $requiredFiles = [
            'modele-lettre.docx' => getConfig('files.template_path'),
            'CV' => getConfig('files.cv_path')
        ];
        
        foreach ($requiredFiles as $name => $path) {
            if (!file_exists($path)) {
                $errors[] = "Le fichier $name n'existe pas : $path";
            }
        }
        
        // Vérifier les dossiers
        $requiredFolders = [
            'lettres_folder' => getConfig('files.lettres_folder'),
            'logs_folder' => getConfig('files.logs_folder')
        ];
        
        foreach ($requiredFolders as $name => $path) {
            if (!is_dir($path)) {
                $errors[] = "Le dossier $name n'existe pas : $path";
            }
        }
        
        // Vérifier la configuration email
        if (!filter_var(getConfig('email.from.email'), FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email de l'expéditeur n'est pas valide";
        }
        
        if (!filter_var(getConfig('email.bcc_email'), FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email BCC n'est pas valide";
        }
        
        return $errors;
    }
}

// Retourner les données de configuration
return $configData;
?>