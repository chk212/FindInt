<?php
// Inclure PHPMailer pour l'envoi d'emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Charger la configuration
require_once 'config.php';
require_once 'email_template_helpers.php'; // Fonctions: addSignatureToEmailTemplate, extractContentWithoutSignature

// Configuration pour la conversion DOCX vers PDF sur Linux
$conversionScript = __DIR__ . DIRECTORY_SEPARATOR . 'docx2pdf.sh';
$commandTemplate = 'bash %s %s %s';

// Protection des droits d'auteur
require_once 'protection-droits-auteur.php';
protectCopyright();

// Vérification de sécurité
require_once 'verification-securite.php';

// Traitement de la vérification de sécurité
if (isset($_POST['security_check'])) {
    $securityReport = checkAppSecurity();
    if ($securityReport['secure']) {
        // Redirection vers generer-lettre.php après vérification réussie
        header('Location: generer-lettre.php?security_verified=1');
        exit;
    } else {
        $securityError = "Problèmes de sécurité détectés : " . implode(', ', $securityReport['errors']);
    }
}

// Si le formulaire est soumis, générer le PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'vendor/autoload.php';
    require __DIR__ . '/config_php_send_mail/src/PHPMailer.php';
    require __DIR__ . '/config_php_send_mail/src/SMTP.php';
    require __DIR__ . '/config_php_send_mail/src/Exception.php';

    // Fonction pour enregistrer les logs
    function log_message($filename, $message) {
        $log_dir = getConfig('files.logs_folder', 'log/');
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        file_put_contents($log_dir . $filename, $message . PHP_EOL, FILE_APPEND);
    }
    
    // Fonction pour envoyer l'email automatiquement
    function sendAutomaticEmail($email, $entreprise, $nomFichierPDF) {
        $mail = new PHPMailer(true);
        try {
            
                // Configuration SMTP depuis la configuration
        $mail->isSMTP();
        $mail->Host       = getConfig('email.smtp.host');
        $mail->SMTPAuth   = true;
        $mail->Username   = getConfig('email.smtp.username');
        $mail->Password   = getConfig('email.smtp.password');
        $mail->SMTPSecure = getConfig('email.smtp.encryption') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getConfig('email.smtp.port');
        $mail->CharSet    = getConfig('app.charset', 'UTF-8');
        $mail->SMTPDebug  = getConfig('email.smtp.debug', 0);
        $mail->SMTPOptions = getConfig('smtp_options', []);

            
            // Configuration de l'email
            $mail->setFrom(getConfig('email.from.email'), getConfig('email.from.name'));
            $mail->addAddress($email);
            $mail->addBCC(getConfig('email.bcc_email'));
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = mb_encode_mimeheader(getConfig('email.subject'), 'UTF-8', 'B');
            
            // Corps de l'email avec remplacement des variables et signature automatique
            $bodyTemplate = addSignatureToEmailTemplate(getConfig('email.body_template'));
            
            // Debug: Vérifier la configuration
            log_message('pdf_email_sent.log', "Debug - bodyTemplate: " . ($bodyTemplate === null ? 'NULL' : 'TROUVÉ'));
            log_message('pdf_email_sent.log', "Debug - Subject: " . $mail->Subject);
            log_message('pdf_email_sent.log', "Debug - Configuration complète email: " . json_encode(getConfig('email')));
            
            if ($bodyTemplate === null) {
                throw new Exception('Configuration manquante: email.body_template');
            }
            
            // Récupérer les valeurs de configuration (priorité aux champs Candidat)
            $nomExpediteur = getConfig('candidat.nom', getConfig('email.from.name', 'Nom non configuré'));
            $emailExpediteur = getConfig('candidat.email', getConfig('email.from.email', 'Email non configuré'));
            $portfolioUrl = getConfig('candidat.portfolio_url', 'Portfolio non configuré');
            $telephone = getConfig('candidat.telephone', '');
            $signatureImage = getConfig('email.from.signature_image', '');
            
            // Debug: Log des valeurs pour vérification
            log_message('pdf_email_sent.log', "Debug - Nom: $nomExpediteur, Email: $emailExpediteur, Portfolio: $portfolioUrl, Téléphone: $telephone");
            
            $mail->Body = str_replace([
                '{entreprise}',
                '{nom_expediteur}',
                '{email_expediteur}',
                '{portfolio_url}',
                '{telephone}',
                '{signature_image}'
            ], [
                $entreprise,
                $nomExpediteur,
                $emailExpediteur,
                $portfolioUrl,
                $telephone,
                $signatureImage
            ], $bodyTemplate);
            
            // Version texte du message (générée automatiquement à partir du HTML)
            $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $mail->Body));
            $mail->AltBody = $textBody;
            
            // Ajouter les pièces jointes
            $lettresFolder = rtrim(getConfig('files.lettres_folder', 'lettre/'), "\\/");
            $lettrePath = __DIR__ . DIRECTORY_SEPARATOR . $lettresFolder . DIRECTORY_SEPARATOR . $nomFichierPDF;

            // Chercher un fichier CV existant
            $cvRelative = getConfig('files.cv_path');
            if (!empty($cvRelative)) {
                $cvPath = __DIR__ . DIRECTORY_SEPARATOR . ltrim($cvRelative, "\\/");
                if (is_file($cvPath) && is_readable($cvPath)) {
                    // Construire un nom de fichier propre: CV_Nom_Prenom.pdf
                    $fullName = getConfig('candidat.nom', getConfig('email.from.name', 'Candidat'));
                    $normalized = trim($fullName);
                    if (function_exists('iconv')) {
                        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $normalized);
                    }
                    $normalized = preg_replace('/[^a-zA-Z0-9]+/', '_', $normalized);
                    $normalized = trim($normalized, '_');
                    $normalized = strtoupper($normalized);
                    $cvFilename = 'CV_' . ($normalized !== '' ? $normalized : 'CANDIDAT') . '.pdf';
                    $mail->addAttachment($cvPath, $cvFilename);
                    log_message('pdf_email_sent.log', "CV joint à l'email: $cvPath sous le nom $cvFilename");
                } else {
                    log_message('pdf_email_error.log', "CV introuvable ou illisible: $cvPath");
                }
            } else {
                log_message('pdf_email_sent.log', 'CV non configuré (files.cv_path vide) : aucune pièce jointe CV');
            }

            if (is_file($lettrePath) && is_readable($lettrePath)) {
                $mail->addAttachment($lettrePath, 'Lettre_de_motivation_' . $entreprise . '.pdf');
                log_message('pdf_email_sent.log', "Lettre jointe à l'email: $lettrePath");
            } else {
                log_message('pdf_email_error.log', "Lettre introuvable ou illisible: $lettrePath");
            }
            
            $mail->send();
            log_message('pdf_email_sent.log', "Email envoyé avec succès à $email pour l'entreprise $entreprise");
            return true;
            
        } catch (Exception $e) {
            $errorMessage = "Erreur envoi email: " . $e->getMessage();
            if (isset($mail)) {
                $errorMessage .= " | SMTP Error: " . $mail->ErrorInfo;
                $errorMessage .= " | Host: " . $mail->Host;
                $errorMessage .= " | Port: " . $mail->Port;
                $errorMessage .= " | Username: " . $mail->Username;
            }
            log_message('pdf_email_error.log', $errorMessage);
            return false;
        }
    }

    // Récupérer les données du formulaire
    $entreprise = trim($_POST['entreprise'] ?? 'Nom_de_ton_entreprise');
    $adresse = trim($_POST['adresse'] ?? '123 Rue de Paris, 75000 Paris');
    $email = trim($_POST['email'] ?? '');
    $date = $_POST['date'] ?? (new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE))->format(time());
    
    // Validation de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez entrer une adresse email valide.";
    } else {
        
        // Vérifier que le template existe
        $templatePath = getConfig('files.template_path', 'modele-lettre.docx');
        if (!file_exists($templatePath)) {
            $error = "Erreur : Le fichier template.docx n'existe pas dans le dossier racine.";
        } else {
            try {
                // 1. Charger le template DOCX
                $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

        // 2. Remplacer les balises par les données
        $templateProcessor->setValue('entreprise', $entreprise);
        $templateProcessor->setValue('adresse', $adresse);
        $templateProcessor->setValue('email', $email);
        $templateProcessor->setValue('date', $date);

        // 3. Sauvegarder le DOCX modifié (chemin absolu)
        $tempDocx = __DIR__ . DIRECTORY_SEPARATOR . 'temp.docx';
        $templateProcessor->saveAs($tempDocx);

        // Vérifier que le fichier DOCX est bien créé
        if (!file_exists($tempDocx)) {
            die("Erreur : Le fichier DOCX temporaire n'a pas été créé. Vérifie les permissions du dossier.");
        }

        // 4. Créer le dossier "lettre" s'il n'existe pas
        $lettresFolder = getConfig('files.lettres_folder', 'lettre/');
        $dossierLettre = __DIR__ . DIRECTORY_SEPARATOR . rtrim($lettresFolder, '/');
        if (!file_exists($dossierLettre)) {
            mkdir($dossierLettre, 0777, true);
        }

        // 5. Chemin du PDF final (avec le nom de l'entreprise)
        $nomFichierPDF = str_replace([' ', '/', '\\'], '_', $entreprise) . '.pdf';
        $cheminPDF = $dossierLettre . DIRECTORY_SEPARATOR . $nomFichierPDF;

        // 6. Appeler le script de conversion Linux
        // Vérifier que le script existe
        if (!file_exists($conversionScript)) {
            die("Erreur : Script de conversion introuvable : " . $conversionScript);
        }
        
        // Construire la commande
        $command = sprintf(
            $commandTemplate,
            escapeshellarg($conversionScript),
            escapeshellarg(realpath($tempDocx)),
            escapeshellarg($cheminPDF)
        );
        
        // Exécuter la commande et capturer la sortie et les erreurs
        $output = '';
        $returnCode = 0;
        
        // Utiliser exec pour capturer le code de retour
        exec($command . ' 2>&1', $outputLines, $returnCode);
        $output = implode("\n", $outputLines);
        
        // Log pour debug
        error_log("Commande exécutée: $command");
        error_log("Code de retour: $returnCode");
        error_log("Sortie: $output");
        
        // Log détaillé pour debug
        log_message('pdf_email_error.log', "=== DEBUG CONVERSION PDF ===");
        log_message('pdf_email_error.log', "Commande: $command");
        log_message('pdf_email_error.log', "Code retour: $returnCode");
        log_message('pdf_email_error.log', "Sortie complète: $output");
        log_message('pdf_email_error.log', "Fichier DOCX existe: " . (file_exists($tempDocx) ? 'OUI' : 'NON'));
        log_message('pdf_email_error.log', "Dossier lettre existe: " . (is_dir($dossierLettre) ? 'OUI' : 'NON'));
        log_message('pdf_email_error.log', "Permissions dossier lettre: " . (is_writable($dossierLettre) ? 'ECRITURE' : 'LECTURE SEULE'));

        // 7. Vérifier que le PDF a bien été généré
        if (file_exists($cheminPDF) && $returnCode === 0) {
            $success = true;
            // Supprimer le fichier DOCX temporaire
            unlink($tempDocx);
            
            // 8. Envoyer l'email automatiquement (toujours)
            $emailSent = sendAutomaticEmail($email, $entreprise, $nomFichierPDF);
            
            // 9. Sauvegarder les données dans un fichier CSV (même si l'email échoue)
            $csvFile = __DIR__ . DIRECTORY_SEPARATOR . getConfig('files.csv_file', 'candidatures.csv');
            $csvData = [
                'Date_creation' => date('Y-m-d H:i:s'),
                'Entreprise' => $entreprise,
                'Adresse' => $adresse,
                'Email' => $email,
                'Date_document' => $date,
                'Fichier_PDF' => $nomFichierPDF,
                'Email_Envoye' => $emailSent ? 'Oui' : 'Non',
                'Notes' => ''
            ];
            
            // Vérifier si le fichier CSV existe déjà
            $fileExists = file_exists($csvFile);
            
            // Ouvrir le fichier en mode append
            $handle = fopen($csvFile, 'a');
            
            // Si le fichier n'existe pas, ajouter l'en-tête
            if (!$fileExists) {
                fputcsv($handle, array_keys($csvData), ';');
            }
            
            // Ajouter les données
            fputcsv($handle, $csvData, ';');
            fclose($handle);
            
        } else {
            $success = false;
            $errorDetails = "Erreur lors de la génération du PDF.<br><br>";
            $errorDetails .= "<strong>Détails techniques :</strong><br>";
            $errorDetails .= "• Fichier DOCX temporaire : " . (file_exists($tempDocx) ? "Créé" : "Non créé") . "<br>";
            $errorDetails .= "• Dossier lettre : " . (is_dir($dossierLettre) ? "Existe" : "N'existe pas") . "<br>";
            $errorDetails .= "• Chemin PDF attendu : " . htmlspecialchars($cheminPDF) . "<br>";
            $errorDetails .= "• Code de retour du script : " . $returnCode . "<br>";
            $errorDetails .= "• Sortie du script de conversion : <pre style='background:#f8f9fa;padding:10px;border-radius:5px;margin:10px 0;'>" . htmlspecialchars($output) . "</pre>";
            
            $error = $errorDetails;
            
            // Log de l'erreur pour debug
            error_log("Erreur génération PDF - Entreprise: $entreprise, Output: $output");
        }
            } catch (Exception $e) {
                $error = "Erreur lors du traitement du template : " . $e->getMessage();
                error_log("Erreur template: " . $e->getMessage());
            }
        }
        
    }
}

// Définir le fuseau horaire
date_default_timezone_set(getConfig('app.timezone', 'Europe/Paris'));

// Formatage de la date du jour pour le formulaire
$dateDuJour = (new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE))->format(time());
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo getConfig('app.name', 'FINDint'); ?> - Générateur de Lettres de Motivation</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container container--form">
        <div class="header">
            <div class="brand">
                <img src="logo.png" alt="<?php echo getConfig('app.name', 'Findint'); ?>" class="brand-logo">
                <span class="brand-name"><?php echo getConfig('app.name', 'Findint'); ?></span>
            </div>
            <h1>Générateur de Lettres de Motivation</h1>
            <p>Votre assistant intelligent pour la recherche de stage</p>
            <div class="subtitle">Générez des lettres de motivation personnalisées en quelques clics et envoyez les.</div>
            <div style="margin-top: 20px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="carnet-de-suivi.php" style="display: inline-flex; align-items: center; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 6px; font-size: 0.9rem; font-weight: 500; text-decoration: none; transition: all 0.2s ease;">
                    Carnet de suivi
                </a>
                <a href="logs.php" style="display: inline-flex; align-items: center; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 6px; font-size: 0.9rem; font-weight: 500; text-decoration: none; transition: all 0.2s ease;">
                    Logs du système
                </a>
                <a href="config_setup.php" style="display: inline-flex; align-items: center; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 6px; font-size: 0.9rem; font-weight: 500; text-decoration: none; transition: all 0.2s ease;">
                    Configuration
                </a>

            </div>
        </div>

        <div class="form-container">
            <?php if (isset($error)): ?>
                <div class="alert error show"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($securityError)): ?>
                <div class="alert error show">
                    <strong>Erreur de sécurité :</strong><br>
                    <?php echo htmlspecialchars($securityError); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success) && $success): ?>
                <div class="alert success show">
                    <strong>PDF généré avec succès !</strong><br>
                    <a href="lettre/<?php echo $nomFichierPDF; ?>" target="_blank">
                        Télécharger <?php echo $nomFichierPDF; ?>
                    </a>
                    <?php if (isset($emailSent) && $emailSent): ?>
                        <br><br><strong>Email envoyé automatiquement à <?php echo htmlspecialchars($email); ?> avec la lettre de motivation personnalisée !</strong>
                    <?php elseif (isset($emailSent) && !$emailSent): ?>
                        <br><br><strong>PDF généré avec succès !</strong><br>
                        <span style="color: #dc2626; font-size: 0.9em;">⚠️ L'email n'a pas pu être envoyé automatiquement. Vérifiez votre configuration email dans les paramètres.</span><br>
                        <span style="color: #6b7280; font-size: 0.85em;">La candidature a été enregistrée dans votre carnet de suivi.</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="post" id="pdfForm">
                <div class="form-group">
                    <label for="entreprise">Nom de l'entreprise</label>
                    <input type="text" id="entreprise" name="entreprise" placeholder="Ex: Google, Microsoft, StartupXYZ..." required>
                </div>

                <div class="form-group">
                    <label for="adresse">Adresse complète</label>
                    <input type="text" id="adresse" name="adresse" placeholder="123 Rue de Paris, 75000 Paris" required>
                </div>

                <div class="form-group">
                    <label for="email">Adresse email de contact</label>
                    <input type="email" id="email" name="email" placeholder="rh@entreprise.com" required>
                </div>

                <div class="form-group">
                    <label for="date">Date de candidature</label>
                    <input type="text" id="date" name="date" value="<?php echo $dateDuJour; ?>" required>
                </div>

                

                <button type="submit" class="submit-btn">
                    <span>Envoyer ma candidature</span>
                </button>
            </form>

            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p id="loadingText">Génération du PDF en cours...</p>
            </div>
        </div>
        
        <footer class="footer">
            <div class="footer-content">
                <?php echo displayCopyrightFooter(); ?>
            </div>
        </footer>
    </div>

    <script>
        // Animation de chargement
        document.getElementById('pdfForm').addEventListener('submit', function() {
            document.getElementById('loading').style.display = 'block';
            document.querySelector('.submit-btn').style.display = 'none';
            const loadingText = document.getElementById('loadingText');
            loadingText.textContent = 'Génération du PDF et envoi de l\'email en cours...';
        });

        // Animation des messages d'alerte
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.add('show');
                }, 100);
            });
        });

        // Effet de focus sur les inputs
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });

        // Validation de l'email en temps réel
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('input', function() {
            const isValid = this.checkValidity();
            if (this.value) {
                if (isValid) {
                    this.style.borderColor = '#51cf66';
                    this.style.background = '#f8fff9';
                } else {
                    this.style.borderColor = '#ff6b6b';
                    this.style.background = '#fff5f5';
                }
            }
        });

        // Validation du formulaire avant soumission
        document.getElementById('pdfForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            if (email && !isValidEmail(email)) {
                e.preventDefault();
                alert('Veuillez entrer une adresse email valide.');
                return false;
            }
        });

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Fonction de vérification de sécurité
        function checkSecurity() {
            // Créer un formulaire temporaire pour la vérification
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'security_check';
            input.value = '1';
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        // Afficher un message si la vérification de sécurité a réussi
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('security_verified') === '1') {
                showSecuritySuccess();
            }
        });

        function showSecuritySuccess() {
            const alert = document.createElement('div');
            alert.className = 'alert success notification';
            alert.innerHTML = '<strong>Vérification de sécurité réussie !</strong><br>L\'application est sécurisée et prête à l\'utilisation.';
            document.body.appendChild(alert);
            
            // Supprimer le paramètre de l'URL
            const url = new URL(window.location);
            url.searchParams.delete('security_verified');
            window.history.replaceState({}, '', url);
            
            // Masquer l'alerte après 4 secondes
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 300);
            }, 4000);
        }
    </script>
    
    <?php echo generateCopyrightScript(); ?>
</body>
</html>
