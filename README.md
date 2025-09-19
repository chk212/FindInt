# 📝 FINDint - Générateur de Lettres de Motivation

**FINDint** est une application web PHP qui automatise la génération et l'envoi de lettres de motivation personnalisées pour les candidatures de stage. L'application utilise des modèles Word (.docx) et génère des PDFs personnalisés avec envoi automatique par email.

## 🚀 Fonctionnalités

- **Génération automatique** de lettres de motivation à partir de modèles Word
- **Personnalisation** des lettres avec données candidat et entreprise
- **Conversion automatique** en PDF
- **Envoi automatique** par email avec pièces jointes
- **Gestion des candidatures** avec suivi CSV
- **Interface web intuitive** et responsive
- **Système de logs** complet
- **Protection des droits d'auteur** intégrée

## 📋 Prérequis

- **Apache2** (serveur web)
- **PHP 7.4+** avec extensions :
  - `php-zip` (pour manipulation des fichiers Word)
  - `php-gd` (pour traitement d'images)
  - `php-mbstring` (pour encodage UTF-8)
  - `php-xml` (pour traitement XML)
  - `php-curl` (pour requêtes HTTP)
  - `php-intl` (pour internationalisation)
- **LibreOffice** (pour conversion DOCX vers PDF)
- **Composer** pour la gestion des dépendances
- **Accès SMTP** pour l'envoi d'emails

## 🛠️ Installation

### Installation Linux (Recommandée)

#### 1. Placer le projet dans le répertoire web
```bash
# Copier le projet dans /var/www/html
sudo cp -r FINDint /var/www/html/
cd /var/www/html/FINDint
```

#### 2. Rendre le script d'installation exécutable
```bash
chmod +x install-linux.sh
```

#### 3. Exécuter l'installation automatique
```bash
./install-linux.sh
```

Le script d'installation automatique va :
- ✅ Installer Apache2 et toutes les dépendances PHP
- ✅ Installer LibreOffice pour la conversion PDF
- ✅ Configurer Apache2 avec le site FINDint
- ✅ Configurer les permissions pour www-data
- ✅ Installer les dépendances Composer
- ✅ Créer la configuration locale

**Distributions Linux supportées :**
- Ubuntu/Debian
- Fedora/RHEL/CentOS
- Arch Linux/Manjaro

#### 4. Configuration via l'interface web
Après l'installation, accédez à la page de configuration :
```
http://localhost/ ou http://findint.local/
```

**Étapes de configuration :**
1. **Préparez d'abord vos fichiers** (CV et modèle de lettre personnalisé)
2. Ouvrez l'application dans votre navigateur
3. Accédez à la page de configuration
4. Remplissez les paramètres :
   - Configuration SMTP pour l'envoi d'emails
   - Vos informations personnelles
   - Chemins des fichiers (CV et modèle de lettre)
5. Sauvegardez la configuration
6. Testez la génération d'une lettre

#### 5. Préparer vos fichiers personnels

**IMPORTANT - Étapes obligatoires avant utilisation :**

1. **Uploader votre CV** :
   - Placez votre **CV** dans le dossier racine du projet (format PDF recommandé)
   - Le fichier doit être nommé de manière claire (ex: `CV_Votre_Nom_2025.pdf`)

2. **Modifier le modèle de lettre** :
   - Ouvrez le fichier `modele-lettre.docx` avec Microsoft Word ou LibreOffice
   - **Personnalisez complètement** le modèle avec :
     - Vos informations personnelles
     - Votre style de rédaction
     - Votre signature
     - Le formatage souhaité
   - Sauvegardez le fichier modifié

> ⚠️ **Note importante** : Le CV et le modèle de lettre personnalisé sont essentiels pour le bon fonctionnement de l'application. Sans ces fichiers personnalisés, l'application ne pourra pas générer des lettres de motivation adaptées à votre profil.

## ⚙️ Configuration

### Interface de Configuration Graphique
L'application propose une interface web intuitive pour configurer tous les paramètres sans édition manuelle de fichiers.

**Accès à la configuration :**
1. Ouvrez l'application dans votre navigateur
2. Utilisez l'interface graphique pour saisir :
   - **Configuration Email** : Paramètres SMTP, expéditeur, signature
   - **Informations Candidat** : Nom, email, portfolio, formation
   - **Paramètres Fichiers** : Chemins des modèles et CV
   - **Options Avancées** : Logs, sécurité, notifications

**Avantages de la configuration graphique :**
- ✅ **Aucune connaissance technique** requise
- ✅ **Validation automatique** des paramètres
- ✅ **Aperçu en temps réel** des modifications
- ✅ **Sauvegarde automatique** des configurations
- ✅ **Interface responsive** et intuitive

## 📖 Utilisation

### 1. Accès à l'application
Ouvrez votre navigateur et accédez à :
```
http://votre-domaine.com/
```

### 2. Configuration (première utilisation)
1. Accédez à l'interface de configuration graphique
2. Remplissez les paramètres :
   - **Vos informations** : Nom, email, formation
   - **Configuration email** : SMTP, signature
   - **Fichiers** : CV et modèle de lettre
3. Sauvegardez la configuration

### 3. Génération d'une lettre
1. Remplissez le formulaire avec :
   - Nom de l'entreprise
   - Email de l'entreprise
   - Informations complémentaires
2. Cliquez sur "Générer et Envoyer"
3. La lettre sera générée et envoyée automatiquement

### 4. Suivi des candidatures
- Les candidatures sont enregistrées dans `candidatures.csv`
- Les logs d'envoi sont disponibles dans `log/`
- Les lettres générées sont stockées dans `lettre/`

## 📁 Structure du Projet

```
FINDint/
├── index.php                 # Point d'entrée
├── generer-lettre.php        # Interface principale
├── config.php               # Configuration par défaut
├── config.local.php         # Configuration personnalisée
├── carnet.php              # Suivi des candidatures
├── logs.php                # Visualisation des logs
├── modele-lettre.docx      # ⚠️ À PERSONNALISER - Modèle de lettre Word
├── CV_Votre_Nom_2025.pdf   # ⚠️ À AJOUTER - CV du candidat
├── lettre/                 # Dossier des lettres générées
├── log/                    # Dossier des logs
├── vendor/                 # Dépendances Composer
└── config_php_send_mail/   # Configuration PHPMailer
```

> ⚠️ **Fichiers obligatoires à personnaliser :**
> - `modele-lettre.docx` : Modèle de lettre à personnaliser avec vos informations
> - `CV_Votre_Nom_2025.pdf` : Votre CV à ajouter dans le dossier racine

## 🔧 Dépendances

- **PHPOffice/PhpWord** : Manipulation des fichiers Word
- **PHPMailer** : Envoi d'emails
- **Composer** : Gestion des dépendances

## 📊 Logs et Monitoring

### Types de logs
- `pdf_email_sent.log` : Envois réussis
- `pdf_email_error.log` : Erreurs d'envoi
- Logs de sécurité automatiques

### Visualisation
Accédez à `logs.php` pour consulter les logs en temps réel.

## 🔒 Sécurité

L'application inclut plusieurs niveaux de protection :
- **Protection des droits d'auteur** automatique
- **Vérification de sécurité** intégrée
- **Headers de sécurité** HTTP
- **Protection contre les injections**
- **Logs de sécurité** détaillés

## 🐛 Dépannage

### Problèmes courants

**Erreur de génération PDF :**
- Vérifiez que le fichier `modele-lettre.docx` existe
- Vérifiez les permissions du dossier `lettre/`
- Vérifiez que LibreOffice est installé et accessible par www-data

**Erreur d'envoi email :**
- Vérifiez la configuration SMTP
- Testez les identifiants email
- Vérifiez les logs dans `log/pdf_email_error.log`

**Erreur de permissions :**
```bash
chmod 755 lettre/
chmod 755 log/
```

**Problèmes Apache2 sur Linux :**
```bash
# Vérifier le statut d'Apache2
sudo systemctl status apache2

# Redémarrer Apache2
sudo systemctl restart apache2

# Vérifier les logs d'erreur
sudo tail -f /var/log/apache2/error.log

# Vérifier la configuration du site
sudo apache2ctl configtest
```

**Problèmes de permissions www-data :**
```bash
# Corriger les permissions
sudo chown -R www-data:www-data /var/www/html/FINDint
sudo chmod -R 755 /var/www/html/FINDint

# Vérifier les permissions des dossiers
ls -la /var/www/html/FINDint/
```


## 📝 Modèle de Lettre

Le fichier `modele-lettre.docx` doit contenir des balises de remplacement :
- `{nom_entreprise}` : Nom de l'entreprise
- `{nom_candidat}` : Nom du candidat
- `{poste_recherche}` : Poste recherché
- `{periode_stage}` : Période du stage

## 🤝 Support

Pour toute question ou problème :
- Consultez les logs dans `log/`
- Vérifiez la configuration dans `config.local.php`
- Contactez l'auteur : [chafik.elhirach@chk-elh.fr]

## 📄 Licence

**© 2025 FINDint - Tous droits réservés**
**Développé par CHAFIK EL HIRACH**
**chafik.elhirach@chk-elh.fr | Portfolio**

Ce logiciel est protégé par les droits d'auteur. Voir `COPYRIGHT.md` pour plus de détails.

## 🔄 Mises à jour

### Version 1.0.0
- Génération automatique de lettres
- Envoi email automatique
- Interface web responsive
- Système de logs complet
- Protection des droits d'auteur

---

**FINDint** - Simplifiez vos candidatures de stage ! 🚀
