#!/bin/bash
#
# Script d'installation pour FINDint sur Linux
# Installe toutes les dépendances nécessaires pour le projet
#
# Usage: ./install-linux.sh
#

set -euo pipefail

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Fonction pour vérifier si une commande existe
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Fonction pour détecter la distribution Linux
detect_distro() {
    if [[ -f /etc/os-release ]]; then
        . /etc/os-release
        echo "$ID"
    elif [[ -f /etc/debian_version ]]; then
        echo "debian"
    elif [[ -f /etc/redhat-release ]]; then
        echo "rhel"
    else
        echo "unknown"
    fi
}

# Fonction pour installer les paquets selon la distribution
install_packages() {
    local distro=$1
    log_info "Installation des paquets pour $distro..."
    
    # Définir la commande d'installation selon si on est root ou non
    local install_cmd
    if [[ $EUID -eq 0 ]]; then
        install_cmd=""
    else
        install_cmd="sudo"
    fi
    
    case $distro in
        "ubuntu"|"debian")
            $install_cmd apt update
            $install_cmd apt install -y \
                php \
                php-cli \
                php-zip \
                php-gd \
                php-mbstring \
                php-xml \
                php-curl \
                php-intl \
                apache2 \
                libapache2-mod-php \
                libreoffice \
                composer \
                git \
                curl \
                unzip
            ;;
        "fedora"|"rhel"|"centos")
            $install_cmd dnf install -y \
                php \
                php-cli \
                php-zip \
                php-gd \
                php-mbstring \
                php-xml \
                php-curl \
                php-intl \
                httpd \
                php \
                libreoffice \
                composer \
                git \
                curl \
                unzip
            ;;
        "arch"|"manjaro")
            $install_cmd pacman -S --noconfirm \
                php \
                php-gd \
                php-intl \
                apache \
                php-apache \
                libreoffice-fresh \
                composer \
                git \
                curl \
                unzip
            ;;
        *)
            log_error "Distribution non supportée: $distro"
            log_info "Veuillez installer manuellement:"
            log_info "- PHP 7.4+ avec extensions: zip, gd, mbstring, xml, curl, intl"
            log_info "- Apache/Nginx"
            log_info "- LibreOffice"
            log_info "- Composer"
            log_info "- Git"
            return 1
            ;;
    esac
}

# Fonction pour configurer Apache
configure_apache() {
    log_info "Configuration d'Apache..."
    
    # Définir la commande selon si on est root ou non
    local apache_cmd
    if [[ $EUID -eq 0 ]]; then
        apache_cmd=""
    else
        apache_cmd="sudo"
    fi
    
    # Activer le module PHP avec gestion d'erreur
    log_info "Activation du module PHP..."
    if $apache_cmd a2enmod php 2>/dev/null; then
        log_success "Module PHP activé"
    else
        log_warning "Module PHP générique non trouvé, recherche de modules disponibles..."
        
        # Vérifier les modules PHP installés
        log_info "Recherche des modules PHP installés..."
        local installed_php_modules=$(find /usr/lib/apache2/modules/ -name "*php*" 2>/dev/null | sed 's/.*\///' | sed 's/\.so$//' | sort -u)
        
        if [[ -n "$installed_php_modules" ]]; then
            log_info "Modules PHP installés trouvés: $installed_php_modules"
            
            # Essayer d'activer les modules PHP un par un
            local activated=false
            for module in $installed_php_modules; do
                log_info "Tentative d'activation de $module..."
                if $apache_cmd a2enmod "$module" 2>/dev/null; then
                    log_success "Module $module activé avec succès"
                    activated=true
                    break
                else
                    log_warning "Échec de l'activation de $module"
                fi
            done
            
            if [[ "$activated" == false ]]; then
                log_warning "Aucun module PHP n'a pu être activé automatiquement"
            fi
        else
            log_warning "Aucun module PHP installé trouvé"
            log_info "Vérification de l'installation du paquet libapache2-mod-php..."
            
            # Vérifier si le paquet est installé
            if dpkg -l | grep -q libapache2-mod-php; then
                log_info "Paquet libapache2-mod-php installé, tentative de configuration manuelle..."
                # Créer un lien symbolique si nécessaire
                if [[ ! -f /etc/apache2/mods-available/php.load ]]; then
                    log_info "Création du fichier de configuration PHP..."
                    cat > /etc/apache2/mods-available/php.load << 'EOF'
LoadModule php_module /usr/lib/apache2/modules/libphp.so
EOF
                fi
                
                if [[ ! -f /etc/apache2/mods-available/php.conf ]]; then
                    cat > /etc/apache2/mods-available/php.conf << 'EOF'
<FilesMatch ".+\.ph(ar|p|tml)$">
    SetHandler application/x-httpd-php
</FilesMatch>
EOF
                fi
                
                # Essayer d'activer à nouveau
                $apache_cmd a2enmod php 2>/dev/null && log_success "Module PHP activé manuellement" || log_warning "Échec de l'activation manuelle"
            else
                log_error "Paquet libapache2-mod-php non installé. Veuillez l'installer manuellement."
            fi
        fi
    fi
    
    # Activer d'autres modules nécessaires
    $apache_cmd a2enmod rewrite
    $apache_cmd a2enmod headers
    
    # Créer la configuration Apache pour le projet
    local project_dir=$(pwd)
    local apache_config="/etc/apache2/sites-available/findint.conf"
    
    $apache_cmd tee "$apache_config" > /dev/null <<EOF
<VirtualHost *:80>
    ServerName findint.local
    DocumentRoot $project_dir
    
    <Directory $project_dir>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Configuration PHP
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>
    
    ErrorLog \${APACHE_LOG_DIR}/findint_error.log
    CustomLog \${APACHE_LOG_DIR}/findint_access.log combined
</VirtualHost>
EOF

    # Activer le site
    $apache_cmd a2ensite findint.conf
    $apache_cmd a2dissite 000-default.conf
    
    # Redémarrer Apache
    $apache_cmd systemctl restart apache2
    $apache_cmd systemctl enable apache2
    
    log_success "Apache configuré avec succès"
}

# Fonction pour configurer les permissions et LibreOffice pour www-data
configure_permissions() {
    log_info "Configuration des permissions et LibreOffice pour www-data..."
    
    # Définir la commande selon si on est root ou non
    local perm_cmd
    if [[ $EUID -eq 0 ]]; then
        perm_cmd=""
    else
        perm_cmd="sudo"
    fi
    
    # 1. Créer les dossiers nécessaires
    log_info "Création des dossiers nécessaires..."
    mkdir -p lettre log
    
    # 2. Configuration des permissions des fichiers
    log_info "Configuration des permissions des fichiers..."
    $perm_cmd chown -R www-data:www-data .
    $perm_cmd chmod -R 755 .
    $perm_cmd chmod +x docx2pdf.sh
    $perm_cmd chmod +x diagnose_libreoffice.sh
    
    # 3. Créer la configuration LibreOffice pour www-data
    log_info "Configuration de LibreOffice pour www-data..."
    
    # Créer le dossier de configuration
    $perm_cmd mkdir -p /var/www/.config
    $perm_cmd chown www-data:www-data /var/www/.config
    $perm_cmd chmod 755 /var/www/.config
    
    # Créer le dossier LibreOffice
    $perm_cmd mkdir -p /var/www/.config/libreoffice
    $perm_cmd chown www-data:www-data /var/www/.config/libreoffice
    $perm_cmd chmod 755 /var/www/.config/libreoffice
    
    # 4. Configurer les permissions des dossiers temporaires
    log_info "Configuration des dossiers temporaires..."
    
    # S'assurer que /tmp est accessible
    $perm_cmd chmod 1777 /tmp
    
    # Créer un dossier temporaire spécifique pour www-data
    $perm_cmd mkdir -p /tmp/www-data
    $perm_cmd chown www-data:www-data /tmp/www-data
    $perm_cmd chmod 755 /tmp/www-data
    
    # 5. Configuration finale des dossiers du projet
    log_info "Configuration finale des dossiers du projet..."
    
    # S'assurer que le dossier lettre a les bonnes permissions
    $perm_cmd chown www-data:www-data lettre/
    $perm_cmd chmod 755 lettre/
    
    # Configurer les logs
    $perm_cmd chown www-data:www-data log/
    $perm_cmd chmod 755 log/
    
    # 6. Test de conversion avec www-data
    log_info "Test de conversion avec www-data..."
    
    # Créer un fichier de test
    cat > /tmp/test_template.docx << 'EOF'
<?xml version="1.0" encoding="UTF-8"?>
<document>
    <title>Test de conversion</title>
    <content>Ceci est un test de conversion DOCX vers PDF.</content>
</document>
EOF
    
    # Tester la conversion
    log_info "Test de conversion LibreOffice..."
    if $perm_cmd -u www-data soffice --headless --convert-to pdf /tmp/test_template.docx --outdir /tmp/www-data/ 2>&1; then
        log_success "Conversion LibreOffice réussie avec www-data"
        
        # Vérifier le fichier généré
        if [ -f "/tmp/www-data/test_template.pdf" ]; then
            log_success "Fichier PDF généré avec succès"
            ls -la /tmp/www-data/test_template.pdf
        else
            log_warning "Fichier PDF non généré"
        fi
    else
        log_warning "Conversion LibreOffice échouée avec www-data (code: $?)"
    fi
    
    # Nettoyer le fichier de test
    rm -f /tmp/test_template.docx
    rm -f /tmp/www-data/test_template.pdf
    
    # 7. Test final avec le script docx2pdf.sh
    log_info "Test final avec le script docx2pdf.sh..."
    
    if [ -f "modele-lettre.docx" ]; then
        log_info "Test avec le template existant..."
        if $perm_cmd -u www-data ./docx2pdf.sh modele-lettre.docx lettre/test_final.pdf; then
            log_success "Script docx2pdf.sh fonctionne avec www-data"
            
            # Vérifier le fichier généré
            if [ -f "lettre/test_final.pdf" ]; then
                log_success "Fichier PDF généré par le script"
                ls -la lettre/test_final.pdf
                rm -f lettre/test_final.pdf
            else
                log_warning "Fichier PDF non généré par le script"
            fi
        else
            log_warning "Script docx2pdf.sh échoue avec www-data (code: $?)"
        fi
    else
        log_warning "Fichier modele-lettre.docx non trouvé"
    fi
    
    log_success "Configuration des permissions et LibreOffice terminée"
    log_info "Résumé des actions effectuées :"
    log_info "✅ Permissions des fichiers configurées"
    log_info "✅ Configuration LibreOffice pour www-data créée"
    log_info "✅ Dossiers temporaires configurés"
    log_info "✅ Test de conversion effectué"
}

# Fonction pour installer les dépendances Composer
install_composer_deps() {
    log_info "Installation des dépendances Composer..."
    
    # Définir la commande selon si on est root ou non
    local composer_cmd
    if [[ $EUID -eq 0 ]]; then
        composer_cmd=""
    else
        composer_cmd="sudo"
    fi
    
    if command_exists composer; then
        composer install --no-dev --optimize-autoloader
        log_success "Dépendances Composer installées"
    else
        log_error "Composer non trouvé. Installation..."
        curl -sS https://getcomposer.org/installer | php
        $composer_cmd mv composer.phar /usr/local/bin/composer
        composer install --no-dev --optimize-autoloader
        log_success "Composer et dépendances installés"
    fi
}

# Fonction pour vérifier les prérequis
check_prerequisites() {
    log_info "Vérification des prérequis..."
    
    # Vérifier si on est root ou si on a les permissions sudo
    if [[ $EUID -eq 0 ]]; then
        log_info "Exécution en tant que root détectée"
    elif ! sudo -n true 2>/dev/null; then
        log_error "Permissions sudo requises ou exécuter en tant que root"
        exit 1
    fi
    
    log_success "Prérequis vérifiés"
}

# Fonction pour créer le fichier de configuration local
create_local_config() {
    log_info "Création de la configuration locale..."
    
    if [[ ! -f config.local.php ]]; then
        cat > config.local.php << 'EOF'
<?php
// Configuration locale pour FINDint
// Ce fichier est généré automatiquement par install-linux.sh

return [
    'app' => [
        'name' => 'FINDint',
        'version' => '1.0.0',
        'debug' => false,
        'timezone' => 'Europe/Paris'
    ],
    'files' => [
        'template_path' => 'modele-lettre.docx',
        'lettres_folder' => 'lettre/',
        'log_folder' => 'log/',
        'csv_file' => 'candidatures.csv'
    ],
    'email' => [
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'from_email' => '',
        'from_name' => 'FINDint'
    ],
    'security' => [
        'max_file_size' => 10485760, // 10MB
        'allowed_extensions' => ['docx', 'pdf'],
        'rate_limit' => 10 // requêtes par minute
    ]
];
EOF
        log_success "Configuration locale créée"
    else
        log_info "Configuration locale déjà existante"
    fi
}

# Fonction pour afficher les informations post-installation
show_post_install_info() {
    log_success "Installation terminée avec succès !"
    echo
    echo -e "${GREEN}=== INFORMATIONS POST-INSTALLATION ===${NC}"
    echo
    echo -e "${BLUE}1. Configuration requise:${NC}"
    echo "   - Éditez config.local.php pour configurer l'email SMTP"
    echo "   - Placez votre CV dans le dossier racine"
    echo "   - Personnalisez modele-lettre.docx"
    echo
    echo -e "${BLUE}2. Accès à l'application:${NC}"
    echo "   - URL: http://localhost/ ou http://findint.local/"
    echo "   - Interface: Interface web intuitive"
    echo
    echo -e "${BLUE}3. Dossiers créés:${NC}"
    echo "   - lettre/ : Lettres générées"
    echo "   - log/ : Logs d'application"
    echo
    echo -e "${BLUE}4. Scripts disponibles:${NC}"
    echo "   - docx2pdf.sh : Conversion DOCX vers PDF (Linux) - Optimisé pour www-data"
    echo "   - diagnose_libreoffice.sh : Diagnostic des problèmes LibreOffice"
    echo "   - convertir-docx-en-pdf.ps1 : Conversion (Windows)"
    echo
    echo -e "${BLUE}5. Services démarrés:${NC}"
    echo "   - Apache : Serveur web"
    echo "   - LibreOffice : Conversion de documents"
    echo
    echo -e "${YELLOW}6. Prochaines étapes:${NC}"
    echo "   - Ouvrez l'application dans votre navigateur"
    echo "   - Configurez l'email SMTP via l'interface"
    echo "   - Testez la génération d'une lettre"
    echo "   - La conversion PDF est maintenant optimisée pour www-data"
    echo
    echo -e "${GREEN}Installation terminée ! 🚀${NC}"
}

# Fonction principale
main() {
    echo -e "${GREEN}"
    echo "╔══════════════════════════════════════════════════════════════╗"
    echo "║                    FINDint - Installation Linux              ║"
    echo "║              Générateur de Lettres de Motivation            ║"
    echo "╚══════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    
    # Vérifier les prérequis
    check_prerequisites
    
    # Détecter la distribution
    local distro=$(detect_distro)
    log_info "Distribution détectée: $distro"
    
    # Installer les paquets
    install_packages "$distro"
    
    # Configurer Apache
    configure_apache
    
    # Configurer les permissions
    configure_permissions
    
    # Installer les dépendances Composer
    install_composer_deps
    
    # Créer la configuration locale
    create_local_config
    
    # Afficher les informations post-installation
    show_post_install_info
}

# Exécuter le script principal
main "$@"
