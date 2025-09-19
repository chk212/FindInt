#!/usr/bin/env bash
#
# Usage : ./docx2pdf.sh /chemin/fichier.docx /chemin/sortie.pdf
# Nécessite : libreoffice (ou soffice) installé
# Remplace convertir-docx-en-pdf.ps1 pour Linux

set -euo pipefail

# Fonction pour logger les messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >&2
}

# Fonction pour configurer l'environnement pour www-data
setup_environment() {
    # Définir les variables d'environnement nécessaires
    export DISPLAY=""
    export HOME="/var/www"
    export USER="www-data"
    
    # Créer le dossier de configuration LibreOffice si nécessaire
    if [ ! -d "/var/www/.config/libreoffice" ]; then
        mkdir -p "/var/www/.config/libreoffice"
        chown www-data:www-data "/var/www/.config/libreoffice"
    fi
    
    log_message "Environnement configuré pour www-data"
}

docxPath="${1:-}"
pdfPath="${2:-}"

if [[ -z "$docxPath" || -z "$pdfPath" ]]; then
    log_message "ERREUR : Paramètres manquants. docxPath='$docxPath' pdfPath='$pdfPath'"
    log_message "Usage : $0 <docxPath> <pdfPath>"
    exit 1
fi

log_message "Début de la conversion : $docxPath -> $pdfPath"

# Configurer l'environnement pour www-data
setup_environment

if [[ ! -f "$docxPath" ]]; then
    log_message "ERREUR : Le fichier DOCX n'existe pas : $docxPath"
    exit 1
fi

# Vérifier que LibreOffice est installé
if ! command -v soffice &> /dev/null; then
    log_message "ERREUR : LibreOffice (soffice) n'est pas installé ou n'est pas dans le PATH"
    log_message "Vérifiez l'installation de LibreOffice : sudo apt-get install libreoffice"
    exit 1
fi

log_message "LibreOffice trouvé : $(which soffice)"

pdfDir="$(dirname "$pdfPath")"
if [[ ! -d "$pdfDir" ]]; then
    mkdir -p "$pdfDir"
fi

# Conversion avec LibreOffice (soffice)
# --headless : pas d'interface graphique
# --convert-to pdf : format de sortie
# --outdir : dossier de sortie
# On renomme ensuite pour correspondre au chemin demandé.
tmpDir="$(mktemp -d)"
trap 'rm -rf "$tmpDir"' EXIT

# Exécuter la conversion avec gestion d'erreur améliorée
log_message "Début de la conversion avec LibreOffice..."
log_message "Fichier source: $docxPath"
log_message "Dossier temporaire: $tmpDir"

# Vérifier que le fichier source est accessible
if [ ! -r "$docxPath" ]; then
    log_message "ERREUR : Le fichier source n'est pas accessible en lecture : $docxPath"
    exit 1
fi

# Vérifier que le dossier temporaire est accessible en écriture
if [ ! -w "$tmpDir" ]; then
    log_message "ERREUR : Le dossier temporaire n'est pas accessible en écriture : $tmpDir"
    exit 1
fi

# Capturer la sortie d'erreur pour debug
log_message "Exécution de LibreOffice..."
log_message "Commande complète: soffice --headless --invisible --nodefault --nolockcheck --nologo --norestore --convert-to pdf \"$docxPath\" --outdir \"$tmpDir\""

# Essayer d'abord avec des options minimales
errorOutput=$(soffice --headless --convert-to pdf "$docxPath" --outdir "$tmpDir" 2>&1)
conversionExitCode=$?

log_message "Code de retour LibreOffice (tentative 1): $conversionExitCode"
log_message "Sortie LibreOffice (tentative 1): $errorOutput"

# Si échec, essayer avec plus d'options
if [ $conversionExitCode -ne 0 ]; then
    log_message "Première tentative échouée, essai avec options étendues..."
    errorOutput=$(soffice --headless --invisible --nodefault --nolockcheck --nologo --norestore --convert-to pdf "$docxPath" --outdir "$tmpDir" 2>&1)
    conversionExitCode=$?
    
    log_message "Code de retour LibreOffice (tentative 2): $conversionExitCode"
    log_message "Sortie LibreOffice (tentative 2): $errorOutput"
fi

# Essayer une approche alternative si les deux premières échouent
if [ $conversionExitCode -ne 0 ]; then
    log_message "Tentatives précédentes échouées, essai avec approche alternative..."
    
    # Essayer avec un timeout et des options supplémentaires
    log_message "Tentative 3: avec timeout et options étendues..."
    timeout 60 soffice --headless --invisible --nodefault --nolockcheck --nologo --norestore --nofirststartwizard --convert-to pdf "$docxPath" --outdir "$tmpDir" 2>&1
    conversionExitCode=$?
    
    log_message "Code de retour (tentative 3): $conversionExitCode"
    
    # Si toujours échec, essayer une approche différente
    if [ $conversionExitCode -ne 0 ]; then
        log_message "Tentative 4: approche avec variables d'environnement..."
        
        # Définir des variables d'environnement pour éviter les problèmes de GUI
        export DISPLAY=""
        export HOME="/tmp"
        
        # Essayer avec une approche plus simple
        timeout 60 soffice --headless --convert-to pdf "$docxPath" --outdir "$tmpDir" 2>&1
        conversionExitCode=$?
        
        log_message "Code de retour (tentative 4): $conversionExitCode"
        
        if [ $conversionExitCode -ne 0 ]; then
            log_message "ERREUR : Toutes les tentatives de conversion ont échoué"
            log_message "Code de retour final: $conversionExitCode"
            log_message "Sortie d'erreur: $errorOutput"
            log_message "Solutions possibles :"
            log_message "1. Vérifiez que LibreOffice est correctement installé: sudo apt-get install libreoffice"
            log_message "2. Vérifiez les dépendances: sudo apt-get install libreoffice-java-common"
            log_message "3. Réinitialisez la config: rm -rf ~/.config/libreoffice"
            log_message "4. Testez manuellement: soffice --headless --convert-to pdf '$docxPath'"
            log_message "5. Vérifiez l'espace disque: df -h"
            log_message "6. Vérifiez les permissions: ls -la '$tmpDir'"
            exit 1
        fi
    fi
fi

log_message "Conversion LibreOffice terminée avec succès"

generatedPdf="$tmpDir/$(basename "${docxPath%.*}").pdf"
if [[ ! -f "$generatedPdf" ]]; then
    log_message "ERREUR : Le fichier PDF généré n'existe pas : $generatedPdf"
    log_message "Fichiers dans le dossier temporaire :"
    ls -la "$tmpDir" | while read line; do log_message "  $line"; done
    exit 1
fi

# Vérifier que le fichier PDF n'est pas vide
if [[ ! -s "$generatedPdf" ]]; then
    log_message "ERREUR : Le fichier PDF généré est vide"
    exit 1
fi

# Vérifier que le fichier PDF est valide (commence par %PDF)
if ! head -c 4 "$generatedPdf" | grep -q "%PDF"; then
    log_message "ERREUR : Le fichier généré ne semble pas être un PDF valide"
    log_message "Contenu du début du fichier :"
    head -c 100 "$generatedPdf" | cat -v | while read line; do log_message "  $line"; done
    exit 1
fi

log_message "Fichier PDF généré et validé : $generatedPdf"

mv "$generatedPdf" "$pdfPath"

log_message "Conversion réussie : $pdfPath"