<?php
// Charger la configuration
require_once 'config.php';

// Protection des droits d'auteur
require_once 'protection-droits-auteur.php';
protectCopyright();

// Traitement des notes si formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notes'])) {
    $csvFile = __DIR__ . DIRECTORY_SEPARATOR . 'donnees_entreprises.csv';
    $entreprise = trim($_POST['entreprise']);
    $notes = trim($_POST['notes']);
    
    if (file_exists($csvFile)) {
        $lines = file($csvFile, FILE_IGNORE_NEW_LINES);
        $updated = false;
        
        for ($i = 1; $i < count($lines); $i++) {
            $data_row = explode(';', $lines[$i]);
            if (count($data_row) >= 6 && trim($data_row[1], '"') === $entreprise) {
                // Mettre à jour la colonne Notes (index 6)
                if (count($data_row) < 7) {
                    $data_row[] = '';
                }
                $data_row[6] = '"' . str_replace('"', '', $notes) . '"';
                $lines[$i] = implode(';', $data_row);
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            file_put_contents($csvFile, implode(PHP_EOL, $lines));
            $success_message = "Notes mises à jour avec succès pour $entreprise";
        }
    }
}

// Lire le fichier CSV
$csvFile = __DIR__ . DIRECTORY_SEPARATOR . 'candidatures.csv';
$data = [];

if (file_exists($csvFile)) {
    $handle = fopen($csvFile, 'r');
    if ($handle !== false) {
        // Lire l'en-tête
        $headers = fgetcsv($handle, 0, ';');
        
        // Lire les données
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) >= 6) { // Au minimum 6 colonnes
                $rowData = [];
                // Mapper les données selon l'ordre des colonnes
                for ($i = 0; $i < count($headers) && $i < count($row); $i++) {
                    $rowData[$headers[$i]] = trim($row[$i], '"');
                }
                // S'assurer que Notes et Email_Envoye existent
                if (!isset($rowData['Notes'])) {
                    $rowData['Notes'] = '';
                }
                if (!isset($rowData['Email_Envoye'])) {
                    $rowData['Email_Envoye'] = 'Non';
                }
                $data[] = $rowData;
            }
        }
        fclose($handle);
    }
}

// Fonction pour formater la date
function formatDate($dateString) {
    try {
        $date = new DateTime($dateString);
        return $date->format('d/m/Y H:i');
    } catch (Exception $e) {
        return $dateString;
    }
}

// Fonction pour formater la date de dernière candidature (sans l'heure)
function formatLastCandidatureDate($dateString) {
    try {
        $date = new DateTime($dateString);
        return $date->format('d/m/Y');
    } catch (Exception $e) {
        return $dateString;
    }
}

// Fonction pour formater la date du document
function formatDocumentDate($dateString) {
    // La date du document est déjà formatée en français
    return $dateString;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>FINDint - Carnet de Suivi</title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand">
                <img src="logo.png" alt="Findint" class="brand-logo">
                <span class="brand-name">Findint</span>
            </div>
            <h1>Carnet de Suivi</h1>
            <a href="generer-lettre.php" style="display: inline-flex; align-items: center; background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 6px; font-size: 0.9rem; font-weight: 500; text-decoration: none; transition: all 0.2s ease;">
                Retour au générateur
            </a>
        </div>


        <div class="content">
            <?php if (empty($data)): ?>
                <div class="empty-state">
                    <h3>Aucune donnée trouvée</h3>
                    <p>Vous n'avez pas encore généré de lettres de motivation.</p>
                    <a href="generer-lettre.php" class="nav-btn">Créer ma première lettre</a>
                </div>
            <?php else: ?>
                <!-- Statistiques -->
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($data); ?></div>
                        <div class="stat-label">Lettres générées</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_unique(array_column($data, 'Entreprise'))); ?></div>
                        <div class="stat-label">Entreprises contactées</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count(array_filter($data, function($row) { return isset($row['Email_Envoye']) && $row['Email_Envoye'] === 'Oui'; })); ?></div>
                        <div class="stat-label">Emails envoyés</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number no-animate"><?php echo formatLastCandidatureDate(end($data)['Date_creation']); ?></div>
                        <div class="stat-label">Dernière candidature</div>
                    </div>
                </div>

                <!-- Tableau des données -->
                <div class="data-table">
                    <div class="table-header">
                        <h3>Détail des candidatures</h3>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date de création</th>
                                    <th>Entreprise</th>
                                    <th>Adresse</th>
                                    <th>Email</th>
                                    <th>Statut Email</th>
                                    <th>Fichier PDF</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($data) as $row): ?>
                                    <tr>
                                        <td class="date-creation"><?php echo formatDate($row['Date_creation']); ?></td>
                                        <td class="entreprise-name"><?php echo htmlspecialchars($row['Entreprise']); ?></td>
                                        <td><?php echo htmlspecialchars($row['Adresse']); ?></td>
                                        <td>
                                            <?php if (!empty($row['Email'])): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($row['Email']); ?>" class="email-link">
                                                    <?php echo htmlspecialchars($row['Email']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #9ca3af;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($row['Email_Envoye']) && $row['Email_Envoye'] === 'Oui'): ?>
                                                <span class="status-badge status-success">✓ Envoyé</span>
                                            <?php else: ?>
                                                <span class="status-badge status-error">✗ Non envoyé</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['Fichier_PDF'])): ?>
                                                <a href="lettre/<?php echo htmlspecialchars($row['Fichier_PDF']); ?>" target="_blank" class="pdf-link">
                                                    <?php echo htmlspecialchars($row['Fichier_PDF']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="color: #9ca3af;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="notes-cell">
                                            <div class="notes-display">
                                                <?php if (!empty($row['Notes'])): ?>
                                                    <span class="notes-text"><?php echo htmlspecialchars($row['Notes']); ?></span>
                                                    <button class="edit-notes-btn" onclick="editNotes('<?php echo htmlspecialchars($row['Entreprise']); ?>', '<?php echo htmlspecialchars($row['Notes']); ?>')">Modifier</button>
                                                <?php else: ?>
                                                    <span class="no-notes">Aucune note</span>
                                                    <button class="add-notes-btn" onclick="editNotes('<?php echo htmlspecialchars($row['Entreprise']); ?>', '')">Ajouter</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="footer">
            <div class="footer-content">
                <?php echo displayCopyrightFooter(); ?>
            </div>
        </footer>
    </div>

    <!-- Modal pour éditer les notes -->
    <div id="notesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Notes pour <span id="modalEntreprise"></span></h3>
                <span class="close">&times;</span>
            </div>
            <form method="post" id="notesForm">
                <div class="modal-body">
                    <input type="hidden" name="entreprise" id="hiddenEntreprise">
                    <label for="notesTextarea">Notes personnelles :</label>
                    <textarea id="notesTextarea" name="notes" placeholder="Ajoutez vos notes sur cette candidature..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" name="update_notes" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert success notification">
            <strong>Succès !</strong><br>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <script>
        // Animation des statistiques (hors éléments marqués no-animate)
        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.stat-number:not(.no-animate)');
            
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent.replace(/[^\d]/g, ''));
                if (!isNaN(target)) {
                    animateNumber(stat, target);
                }
            });
        });

        function animateNumber(element, target) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 30);
        }

        // Animation au survol des cartes
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Gestion du modal des notes
        const modal = document.getElementById('notesModal');
        const closeBtn = document.getElementsByClassName('close')[0];

        function editNotes(entreprise, currentNotes) {
            document.getElementById('modalEntreprise').textContent = entreprise;
            document.getElementById('hiddenEntreprise').value = entreprise;
            document.getElementById('notesTextarea').value = currentNotes;
            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Fermer le modal en cliquant sur X
        closeBtn.onclick = closeModal;

        // Fermer le modal en cliquant en dehors
        window.onclick = function(event) {
            if (event.target === modal) {
                closeModal();
            }
        }

        // Auto-masquer la notification de succès avec animation
        setTimeout(function() {
            const notification = document.querySelector('.alert.notification');
            if (notification) {
                notification.classList.add('fade-out');
                setTimeout(function() {
                    notification.style.display = 'none';
                }, 300);
            }
        }, 4000);

        // Ajouter un effet de pulsation pour les messages importants
        document.addEventListener('DOMContentLoaded', function() {
            const notifications = document.querySelectorAll('.alert.notification');
            notifications.forEach(notification => {
                // Ajouter une pulsation légère pour attirer l'attention
                if (notification.classList.contains('success')) {
                    notification.classList.add('pulse');
                    setTimeout(() => {
                        notification.classList.remove('pulse');
                    }, 3000);
                }
            });
        });

        // Fonction pour créer des notifications dynamiques
        function showNotification(message, type = 'success', duration = 4000) {
            const notification = document.createElement('div');
            notification.className = `alert notification ${type}`;
            notification.innerHTML = message;
            
            document.body.appendChild(notification);
            
            // Animation d'entrée
            setTimeout(() => {
                notification.style.animation = 'slideInFromRight 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
            }, 10);
            
            // Auto-masquer avec animation
            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, duration);
        }
    </script>
    
    <?php echo generateCopyrightScript(); ?>
</body>
</html>