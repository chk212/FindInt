<?php
// Charger la configuration
require_once 'config.php';

// Protection des droits d'auteur
require_once 'protection-droits-auteur.php';
protectCopyright();

// Fonctions partagées pour les templates email
require_once 'email_template_helpers.php';

// Vérifier si le fichier de configuration locale existe
$hasLocalConfig = file_exists(__DIR__ . '/config.local.php');

// Traitement du formulaire de configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Construire le chemin du CV depuis un nom sans extension
    $cvNameInput = trim($_POST['cv_filename'] ?? '');
    $cvNameInput = preg_replace('/\s+/', ' ', $cvNameInput);
    $cvNameNoExt = preg_replace('/\.?pdf$/i', '', $cvNameInput);
    $cvPathBuilt = $cvNameNoExt !== '' ? ($cvNameNoExt . '.pdf') : '';

    $configData = [
        'email' => [
            'smtp' => [
                'host' => $_POST['smtp_host'] ?? '',
                'port' => (int)($_POST['smtp_port'] ?? 465),
                'encryption' => $_POST['smtp_encryption'] ?? 'ssl',
                'username' => $_POST['smtp_username'] ?? '',
                'password' => $_POST['smtp_password'] ?? '',
                'debug' => (int)($_POST['smtp_debug'] ?? 0),
            ],
            'from' => [
                'email' => $_POST['candidat_email'] ?? $_POST['from_email'] ?? '',
                'name' => $_POST['candidat_nom'] ?? $_POST['from_name'] ?? '',
                'signature_image' => $_POST['candidat_signature'] ?? '',
            ],
            'bcc_email' => $_POST['bcc_email'] ?? '',
            'subject' => $_POST['email_subject'] ?? '',
            'body_template' => $_POST['email_body_template'] ?? '',
        ],
        'candidat' => [
            'nom' => $_POST['candidat_nom'] ?? '',
            'email' => $_POST['candidat_email'] ?? '',
            'telephone' => $_POST['candidat_telephone'] ?? '',
            'portfolio_url' => $_POST['candidat_portfolio'] ?? '',
            'poste_recherche' => getValue('candidat.poste_recherche', 'Administrateur Système'),
            'periode_stage' => getValue('candidat.periode_stage', 'du 30 Mars au 03 Juillet 2026'),
            'formation' => getValue('candidat.formation', 'Licence Pro Administrateur Système')
        ],
        'files' => [
            'template_path' => $_POST['template_path'] ?? 'modele-lettre.docx',
            'cv_path' => $cvPathBuilt,
            'lettres_folder' => $_POST['lettres_folder'] ?? 'lettre/',
            'logs_folder' => $_POST['logs_folder'] ?? 'log/',
            'csv_file' => $_POST['csv_file'] ?? 'candidatures.csv',
        ],
        'app' => [
            // Les paramètres de l'application sont protégés et ne peuvent pas être modifiés
            'name' => getValue('app.name', 'FINDint'),
            'version' => getValue('app.version', '1.0.0'),
            'author' => getValue('app.author', 'CHAFIK EL HIRACH'),
            'debug' => getValue('app.debug', false),
            'timezone' => getValue('app.timezone', 'Europe/Paris'),
            'charset' => getValue('app.charset', 'UTF-8'),
        ],
        'notifications' => [
            'position' => $_POST['notification_position'] ?? 'top-right',
            'animation' => $_POST['notification_animation'] ?? 'slide-in-right',
            'auto_close' => isset($_POST['auto_close']),
            'auto_close_delay' => (int)($_POST['auto_close_delay'] ?? 5000),
        ]
    ];
    
    // Générer le contenu du fichier de configuration
    $configContent = "<?php\n";
    $configContent .= "/**\n";
    $configContent .= " * Configuration locale pour FINDint - Générateur de Lettres de Motivation\n";
    $configContent .= " * \n";
    $configContent .= " * Ce fichier contient vos configurations personnelles.\n";
    $configContent .= " * Il ne doit JAMAIS être commité sur GitHub pour des raisons de sécurité.\n";
    $configContent .= " * \n";
    $configContent .= " * Généré le " . date('Y-m-d H:i:s') . "\n";
    $configContent .= " */\n\n";
    $configContent .= "return " . var_export($configData, true) . ";\n";
    $configContent .= "?>\n";
    
    // Sauvegarder le fichier
    if (file_put_contents(__DIR__ . '/config.local.php', $configContent)) {
        $success = "Configuration sauvegardée avec succès dans config.local.php";
        $hasLocalConfig = true;
    } else {
        $error = "Erreur lors de la sauvegarde du fichier de configuration";
    }
}

// Récupérer les valeurs actuelles pour l'affichage
$currentConfig = [];
if ($hasLocalConfig) {
    $currentConfig = require __DIR__ . '/config.local.php';
}

// Fonction helper pour récupérer une valeur
function getValue($key, $default = '') {
    global $currentConfig;
    $keys = explode('.', $key);
    $value = $currentConfig;
    
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default;
        }
    }
    
    return $value;
}

/**
 * Fonction pour extraire le contenu du template sans la signature HTML
 * Utilisée pour afficher uniquement le contenu principal dans l'interface
 */
// extractContentWithoutSignature fourni par email_template_helpers.php

/**
 * Fonction pour ajouter la signature HTML au template lors de l'envoi des emails
 * Cette fonction est utilisée uniquement lors de l'envoi, pas lors de la sauvegarde
 * L'utilisateur ne voit pas la signature dans son interface de configuration
 * 
 * UTILISATION : Appeler cette fonction dans le fichier d'envoi d'emails (ex: generer-lettre.php)
 * Exemple : $emailBody = addSignatureToEmailTemplate(getConfig('email.body_template'));
 */
// addSignatureToEmailTemplate fourni par email_template_helpers.php
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Configuration - FINDint</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        // Protection JavaScript pour empêcher la modification des champs protégés
        document.addEventListener('DOMContentLoaded', function() {
            // Désactiver tous les champs en lecture seule
            const readonlyFields = document.querySelectorAll('.readonly-field');
            readonlyFields.forEach(field => {
                field.addEventListener('keydown', function(e) {
                    e.preventDefault();
                    return false;
                });
                
                field.addEventListener('paste', function(e) {
                    e.preventDefault();
                    return false;
                });
                
                field.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    return false;
                });
            });

            // Empêcher la modification via les outils de développement
            const readonlySection = document.querySelector('.readonly-section');
            if (readonlySection) {
                readonlySection.addEventListener('DOMNodeInserted', function(e) {
                    if (e.target.classList && e.target.classList.contains('readonly-field')) {
                        e.target.setAttribute('readonly', 'readonly');
                        e.target.setAttribute('disabled', 'disabled');
                    }
                });
            }
        });

        // Avertissement si quelqu'un essaie de modifier le code source
        console.warn('ATTENTION: Cette section est protégée. Les modifications des paramètres de l\'application ne sont pas autorisées via cette interface.');

        // Gestion de l'upload de CV supprimée (mode upload désactivé)

        // Synchronisation automatique des champs entre profil et email
        document.addEventListener('DOMContentLoaded', function() {
            const candidatNom = document.getElementById('candidat_nom');
            const candidatEmail = document.getElementById('candidat_email');
            const candidatSignature = document.getElementById('candidat_signature');
            const candidatTelephone = document.getElementById('candidat_telephone');
            const candidatPortfolio = document.getElementById('candidat_portfolio');
            const fromName = document.getElementById('from_name');
            const smtpUsername = document.getElementById('smtp_username');
            const signaturePreviewHtml = document.getElementById('signature-preview-html');

            const renderSignaturePreview = () => {
                if (!signaturePreviewHtml) return;
                const nom = (candidatNom && candidatNom.value) ? candidatNom.value : '';
                const email = (candidatEmail && candidatEmail.value) ? candidatEmail.value : '';
                const telephone = (candidatTelephone && candidatTelephone.value) ? candidatTelephone.value.trim() : '';
                const portfolio = (candidatPortfolio && candidatPortfolio.value) ? candidatPortfolio.value : '';
                const imageUrl = (candidatSignature && candidatSignature.value) ? candidatSignature.value : '';

                const telephoneLine = telephone !== '' 
                    ? '<p style="margin: 0; font-size: 14px; color: #666;">Téléphone: ' + telephone + '</p>' 
                    : '';

                const html =
                    '<table style="font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6;">' +
                    '  <tbody>' +
                    '    <tr>' +
                    '      <td style="padding: 0; vertical-align: top;">' +
                    '        <div>' +
                    '          <img style="width: 80px; height: auto; border-radius: 50%; margin-right: 10px;" alt="Profil" src="' + (imageUrl || '') + '"><br>' +
                    '        </div>' +
                    '      </td>' +
                    '      <td style="padding: 0; vertical-align: top;">' +
                    '        <p style="margin: 0; font-weight: bold; font-size: 16px; color: #1a73e8;">' + (nom || '') + '<br></p>' +
                    '        <p style="margin: 0; font-size: 14px; color: #666;">Étudiant en Informatique<br></p>' +
                    '        <p style="margin: 0; font-size: 14px; color: #666;">Email: ' + (email || '') + '</p>' +
                             telephoneLine +
                    '        <p style="margin: 0; font-size: 14px; color: #666;">Mon Portfolio : ' + (portfolio || '') + ' <br></p>' +
                    '        <div><hr style="border: 0; border-top: 1px solid #ccc;"><br></div>' +
                    '      </td>' +
                    '    </tr>' +
                    '  </tbody>' +
                    '</table>' +
                    '<div><br></div>';

                signaturePreviewHtml.innerHTML = html;
            };

            // Synchroniser nom du profil vers nom expéditeur (bidirectionnel)
            if (candidatNom && fromName) {
                candidatNom.addEventListener('input', function() {
                    fromName.value = this.value;
                });
                fromName.addEventListener('input', function() {
                    candidatNom.value = this.value;
                });
            }

            // Forcer "Votre email" à être identique à "Nom d'utilisateur SMTP" et en lecture seule
            if (smtpUsername && candidatEmail) {
                const syncProfileEmail = () => {
                    candidatEmail.value = smtpUsername.value || '';
                };
                // Init et écoute des changements du SMTP username
                syncProfileEmail();
                smtpUsername.addEventListener('input', syncProfileEmail);

                // Rendre non modifiable côté UI (mais soumis au POST via readonly)
                candidatEmail.readOnly = true;
            }

            // Mise à jour du preview complet quand l'URL de l'image change
            if (candidatSignature) {
                candidatSignature.addEventListener('input', function() {
                    renderSignaturePreview();
                });
            }

            // Mises à jour live du preview complet
            if (candidatNom) candidatNom.addEventListener('input', renderSignaturePreview);
            if (candidatEmail) candidatEmail.addEventListener('input', renderSignaturePreview);
            if (candidatTelephone) candidatTelephone.addEventListener('input', renderSignaturePreview);
            if (candidatPortfolio) candidatPortfolio.addEventListener('input', renderSignaturePreview);

            // Premier rendu
            renderSignaturePreview();
        });

    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Configuration FINDint</h1>
            <p>Configurez votre générateur de lettres de motivation</p>
        </div>

        <div class="content">
            <?php if (isset($success)): ?>
                <div class="alert success">
                    <span class="status-indicator success"></span>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert error">
                    <span class="status-indicator error"></span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Alertes d'upload CV supprimées -->

            <?php if (!$hasLocalConfig): ?>
                <div class="alert info">
                    <span class="status-indicator warning"></span>
                    <strong>Configuration manquante :</strong> Aucun fichier de configuration locale trouvé. 
                    Veuillez remplir le formulaire ci-dessous pour créer votre configuration.
                </div>
            <?php endif; ?>

            <div class="alert info">
                <span class="status-indicator success"></span>
                <strong>Synchronisation automatique :</strong> Les champs "Nom expéditeur" et "Email expéditeur" sont automatiquement synchronisés avec les informations de votre profil. 
                Le template de signature HTML est inclus par défaut dans le corps de l'email.
            </div>

            <div class="alert info">
                <span class="status-indicator success"></span>
                <strong>Signature automatique :</strong> La signature avec votre photo de profil sera automatiquement ajoutée lors de l'envoi des emails, sans apparaître dans ce champ de configuration. 
                Cela garantit que tous vos emails auront une signature professionnelle cohérente, sans encombrer l'interface de configuration.
            </div>

            <!-- Formulaire principal de configuration -->
            <form method="post" id="configForm">
                <!-- Configuration Email -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Configuration Email</h2>
                        <p class="section-description">Paramètres SMTP et configuration de l'expéditeur</p>
                    </div>
                    <div class="section-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="smtp_host">Serveur SMTP</label>
                                <input type="text" id="smtp_host" name="smtp_host" 
                                       value="<?php echo htmlspecialchars(getValue('email.smtp.host', 'smtp.votre-fournisseur.com')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="smtp_port">Port SMTP</label>
                                <input type="number" id="smtp_port" name="smtp_port" 
                                       value="<?php echo getValue('email.smtp.port', 465); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="smtp_encryption">Chiffrement</label>
                                <select id="smtp_encryption" name="smtp_encryption">
                                    <option value="ssl" <?php echo getValue('email.smtp.encryption') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="tls" <?php echo getValue('email.smtp.encryption') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="smtp_username">Nom d'utilisateur SMTP</label>
                                <input type="text" id="smtp_username" name="smtp_username" 
                                       value="<?php echo htmlspecialchars(getValue('email.smtp.username')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="smtp_password">Mot de passe SMTP</label>
                                <input type="password" id="smtp_password" name="smtp_password" 
                                       value="<?php echo htmlspecialchars(getValue('email.smtp.password')); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="from_email">Email expéditeur</label>
                                <input type="email" id="from_email" name="from_email" 
                                       value="<?php echo htmlspecialchars(getValue('email.from.email') ?: getValue('candidat.email')); ?>" required>
                                <small class="form-help">Synchronisé avec votre email du profil</small>
                            </div>
                            <div class="form-group">
                                <label for="from_name">Nom expéditeur</label>
                                <input type="text" id="from_name" name="from_name" 
                                       value="<?php echo htmlspecialchars(getValue('email.from.name') ?: getValue('candidat.nom')); ?>" required>
                                <small class="form-help">Synchronisé avec votre nom du profil</small>
                            </div>
                            <div class="form-group">
                                <label for="bcc_email">Email BCC</label>
                                <input type="email" id="bcc_email" name="bcc_email" 
                                       value="<?php echo htmlspecialchars(getValue('email.bcc_email')); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Messages email -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Messages Email</h2>
                        <p class="section-description">Objet et contenu des emails envoyés</p>
                    </div>
                    <div class="section-content">
                        <div class="form-group">
                            <label for="email_subject">Objet de l'email</label>
                            <input type="text" id="email_subject" name="email_subject" 
                                   value="<?php echo htmlspecialchars(getValue('email.subject')); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email_body_template">Corps de l'email</label>
                            <textarea id="email_body_template" name="email_body_template" rows="15"
                                      placeholder="Template HTML utilisé pour l'envoi des emails. Variables disponibles: {entreprise}, {nom_expediteur}, {email_expediteur}, {portfolio_url}, {signature_image}"><?php 
                            $templateFromConfig = getValue('email.body_template');
                            if (empty($templateFromConfig)) {
                                // Template par défaut sans signature (signature ajoutée automatiquement lors de l'envoi)
                                $displayTemplate = 'Bonjour Madame, Monsieur,<br><br>
Je vous adresse en pièce jointe ma lettre de motivation personnalisée ainsi que mon CV dans le cadre d\'une candidature à un stage d\'Administrateur Système au sein de {entreprise}, du 30 Mars au 03 Juillet 2026.<br><br>
Je suis à votre entière disposition pour un entretien afin d\'échanger plus en détail sur mes motivations, mes compétences et la manière dont je pourrais contribuer à vos projets.<br><br>
Je vous remercie sincèrement pour l\'attention portée à ma candidature et espère pouvoir prochainement collaborer avec votre équipe.<br><br>
Bien cordialement,<br><br>';
                            } else {
                                // Extraire le contenu sans signature pour l'affichage
                                $displayTemplate = extractContentWithoutSignature($templateFromConfig);
                            }
                            echo htmlspecialchars($displayTemplate); 
                            ?></textarea>
                            <small class="form-help">Ce template HTML sera envoyé avec les variables remplacées automatiquement. <strong>La signature avec votre photo de profil sera automatiquement ajoutée lors de l'envoi (invisible dans ce champ).</strong></small>
                        </div>
                    </div>
                </div>

                <!-- Configuration du profil -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Configuration du profil</h2>
                        <p class="section-description">Informations qui apparaîtront dans la signature de vos emails</p>
                    </div>
                    <div class="section-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="candidat_nom">Votre nom complet</label>
                                <input type="text" id="candidat_nom" name="candidat_nom" 
                                       value="<?php echo htmlspecialchars(getValue('candidat.nom', '')); ?>" 
                                       placeholder="Ex: Votre Nom Prénom">
                            </div>
                            <div class="form-group">
                                <label for="candidat_email">Votre email</label>
                                <input type="email" id="candidat_email" name="candidat_email" 
                                       value="<?php echo htmlspecialchars(getValue('candidat.email', '')); ?>" 
                                       placeholder="Ex: votre-email@example.com">
                            </div>
                            <div class="form-group">
                                <label for="candidat_telephone">Votre téléphone (facultatif)</label>
                                <input type="tel" id="candidat_telephone" name="candidat_telephone" 
                                       value="<?php echo htmlspecialchars(getValue('candidat.telephone', '')); ?>" 
                                       placeholder="Ex: +33 6 12 34 56 78">
                                <small class="form-help">Ce numéro apparaîtra dans la signature de vos emails s'il est renseigné</small>
                            </div>
                            <div class="form-group">
                                <label for="candidat_portfolio">URL de votre portfolio</label>
                                <input type="url" id="candidat_portfolio" name="candidat_portfolio" 
                                       value="<?php echo htmlspecialchars(getValue('candidat.portfolio_url', '')); ?>" 
                                       placeholder="Ex: https://votre-portfolio.com">
                            </div>
                            <div class="form-group">
                                <label for="candidat_signature">URL de votre photo de profil</label>
                                <input type="url" id="candidat_signature" name="candidat_signature" 
                                       value="<?php echo htmlspecialchars(getValue('email.from.signature_image', '')); ?>" 
                                       placeholder="Ex: https://votre-site.com/photo.jpg">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Aperçu complet de la signature</label>
                                <div id="signature-preview-html" style="padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fafafa;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fichier CV -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Fichier CV</h2>
                        <p class="section-description">Indiquez le nom du fichier sans l'extension .pdf</p>
                    </div>
                    <div class="section-content">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="cv_filename">Nom du fichier CV (sans .pdf)</label>
                                <?php
                                $cvPathCurrent = getValue('files.cv_path', '');
                                $cvBase = preg_replace('/\.?pdf$/i', '', (string)$cvPathCurrent);
                                ?>
                                <input type="text" id="cv_filename" name="cv_filename"
                                       value="<?php echo htmlspecialchars($cvBase); ?>"
                                       placeholder="Ex: CV_Votre_Nom_2025">
                                <small class="form-help">Placez le fichier PDF à la racine du projet. L'extension .pdf sera ajoutée automatiquement.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration des fichiers supprimée -->

                <!-- Gestion du CV supprimée (mode upload désactivé) -->

                <!-- Configuration de l'application (READONLY) -->
                <div class="section readonly-section">
                    <div class="section-header">
                        <h2 class="section-title">Configuration de l'application</h2>
                        <p class="section-description">Paramètres généraux de l'application (Lecture seule - Protégé)</p>
                    </div>
                    <div class="section-content">
                        <!-- Notice protégée supprimée -->
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="app_name">Nom de l'application</label>
                                <input type="text" id="app_name" name="app_name" 
                                       value="<?php echo htmlspecialchars(getValue('app.name', 'FINDint')); ?>" 
                                       readonly disabled class="readonly-field">
                            </div>
                            <div class="form-group">
                                <label for="app_version">Version</label>
                                <input type="text" id="app_version" name="app_version" 
                                       value="<?php echo htmlspecialchars(getValue('app.version', '1.0.0')); ?>" 
                                       readonly disabled class="readonly-field">
                            </div>
                            <div class="form-group">
                                <label for="app_author">Auteur</label>
                                <input type="text" id="app_author" name="app_author" 
                                       value="<?php echo htmlspecialchars(getValue('app.author')); ?>" 
                                       readonly disabled class="readonly-field">
                            </div>
                            <!-- Champs Fuseau horaire et Encodage supprimés -->
                        </div>
                        <!-- Champ Mode debug activé supprimé -->
                    </div>
                </div>


                <div class="actions">
                    <button type="submit" class="btn">Sauvegarder la configuration</button>
                    <a href="generer-lettre.php" class="btn btn-secondary">Retour à l'application</a>
                </div>
            </form>
        </div>
        
        <?php echo displayCopyrightFooter(); ?>
    </div>
    
    <?php echo generateCopyrightScript(); ?>
</body>
</html>
