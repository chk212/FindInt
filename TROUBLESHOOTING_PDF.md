# Guide de dépannage - Erreur de génération PDF

## Problème identifié
**Code de retour 77** lors de la conversion DOCX vers PDF avec LibreOffice.

## Solutions à essayer

### 1. Vérifier l'installation de LibreOffice

```bash
# Vérifier si LibreOffice est installé
which soffice
soffice --version

# Si non installé, installer LibreOffice
sudo apt-get update
sudo apt-get install libreoffice
```

### 2. Vérifier les permissions

```bash
# Vérifier les permissions du dossier lettre
ls -la lettre/
chmod 755 lettre/

# Vérifier les permissions du script
chmod +x docx2pdf.sh
```

### 3. Tester la conversion manuellement

```bash
# Test simple avec LibreOffice
soffice --headless --convert-to pdf modele-lettre.docx --outdir ./lettre/

# Test avec le script amélioré
./docx2pdf.sh modele-lettre.docx test_output.pdf
```

### 4. Vérifier l'environnement

```bash
# Vérifier les variables d'environnement
echo $DISPLAY
echo $HOME

# Vérifier l'espace disque
df -h
```

### 5. Codes d'erreur LibreOffice courants

- **Code 77** : Problème de configuration ou de permissions
- **Code 1** : Erreur générale
- **Code 2** : Fichier source introuvable
- **Code 3** : Dossier de sortie inaccessible

### 6. Solutions spécifiques au code 77

#### Option A : Réinitialiser la configuration LibreOffice
```bash
# Supprimer la configuration utilisateur LibreOffice
rm -rf ~/.config/libreoffice
rm -rf ~/.libreoffice

# Réessayer la conversion
```

#### Option B : Utiliser des options différentes
Le script a été amélioré avec ces options :
- `--headless` : Mode sans interface
- `--invisible` : Pas de fenêtre visible
- `--nodefault` : Ne pas charger les paramètres par défaut
- `--nolockcheck` : Ignorer les verrous de fichiers
- `--nologo` : Pas de logo
- `--norestore` : Ne pas restaurer les sessions
- `--nofirststartwizard` : Pas d'assistant de premier démarrage

#### Option C : Vérifier les dépendances
```bash
# Vérifier les dépendances manquantes
ldd $(which soffice)

# Installer les dépendances manquantes
sudo apt-get install libreoffice-java-common
sudo apt-get install libreoffice-gtk3
```

### 7. Debug avancé

#### Activer les logs détaillés
Le script amélioré inclut maintenant des logs détaillés qui vous aideront à identifier le problème exact.

#### Vérifier les logs
```bash
# Consulter les logs d'erreur
tail -f log/pdf_email_error.log

# Consulter les logs de succès
tail -f log/pdf_email_sent.log
```

### 8. Alternative : Conversion via Python

Si LibreOffice continue à poser problème, vous pouvez utiliser une alternative Python :

```bash
# Installer les dépendances Python
pip install python-docx2txt
pip install reportlab

# Utiliser un script Python de conversion
```

## Test de validation

Après avoir appliqué les corrections, testez avec :

```bash
# Exécuter le script de test
./test_conversion.sh

# Ou tester manuellement
./docx2pdf.sh modele-lettre.docx test.pdf
```

## Contact

Si le problème persiste, fournissez :
1. La sortie complète du script de test
2. Les logs d'erreur détaillés
3. La version de LibreOffice installée
4. Le système d'exploitation utilisé
