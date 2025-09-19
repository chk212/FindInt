<?php
// Fonctions utilitaires partagées pour la gestion des templates email

if (!function_exists('extractContentWithoutSignature')) {
    /**
     * Extraire le contenu du template sans la signature HTML.
     */
    function extractContentWithoutSignature($template) {
        $signaturePattern = '/<table[^>]*style="font-family: Arial, sans-serif[^"]*"[^>]*>.*?<\/table>\s*<div><br><\/div>/s';
        $contentWithoutSignature = preg_replace($signaturePattern, '', $template);
        $contentWithoutSignature = rtrim($contentWithoutSignature, "<br><br>\n\r\t ");
        return $contentWithoutSignature;
    }
}

if (!function_exists('addSignatureToEmailTemplate')) {
    /**
     * Ajouter la signature HTML au template lors de l'envoi des emails.
     */
    function addSignatureToEmailTemplate($template) {
        // Récupérer la configuration pour vérifier si le téléphone est renseigné
        $config = [];
        if (file_exists(__DIR__ . '/config.local.php')) {
            $config = require __DIR__ . '/config.local.php';
        }
        
        $telephone = $config['candidat']['telephone'] ?? '';
        $telephoneLine = !empty(trim($telephone)) ? '<p style="margin: 0; font-size: 14px; color: #666;">Téléphone: {telephone}</p>' : '';
        
        $signatureHtml = '
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
                <p style="margin: 0; font-size: 14px; color: #666;">Email: {email_expediteur}</p>' . 
                $telephoneLine . '
                <p style="margin: 0; font-size: 14px; color: #666;">Mon Portfolio : {portfolio_url} <br></p>
                <div><hr style="border: 0; border-top: 1px solid #ccc;"><br></div>
            </td>
        </tr>
    </tbody>
</table>
<div><br></div>';

        if (empty(trim($template))) {
            return 'Bonjour Madame, Monsieur,<br><br>
Je vous adresse en pièce jointe ma lettre de motivation personnalisée ainsi que mon CV dans le cadre d\'une candidature à un stage d\'Administrateur Système au sein de {entreprise}, du 30 Mars au 03 Juillet 2026.<br><br>
Je suis à votre entière disposition pour un entretien afin d\'échanger plus en détail sur mes motivations, mes compétences et la manière dont je pourrais contribuer à vos projets.<br><br>
Je vous remercie sincèrement pour l\'attention portée à ma candidature et espère pouvoir prochainement collaborer avec votre équipe.<br><br>
Bien cordialement,<br><br>' . $signatureHtml;
        }

        $cleanTemplate = extractContentWithoutSignature($template);

        if (strpos($template, '{signature_image}') !== false && strpos($template, '{nom_expediteur}') !== false) {
            return $template;
        }

        return $cleanTemplate . '<br><br>' . $signatureHtml;
    }
}

?>


