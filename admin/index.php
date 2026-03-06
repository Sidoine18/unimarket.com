<?php
/**
 * UniMarket - Administration Centrale
 */
require_once '../config.php'; 

// 1. SÉCURITÉ : Vérifier si l'utilisateur est connecté ET est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?msg=access_denied');
    exit;
}

// 2. LOGIQUE DE MODÉRATION (Suppression rapide)
if (isset($_GET['delete_ad'])) {
    $id = intval($_GET['delete_ad']);
    // On récupère l'image pour la supprimer du serveur
    $stmtImg = $pdo->prepare("SELECT image_path FROM ads WHERE id = ?");
    $stmtImg->execute([$id]);
    $img = $stmtImg->fetchColumn();
    if ($img) @unlink("../images/uploads/" . $img);

    $pdo->prepare("DELETE FROM ads WHERE id = ?")->execute([$id]);
    header('Location: index.php?msg=ad_deleted');
    exit;
}

// 3. RÉCUPÉRATION DES STATISTIQUES (Requêtes Experts)
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_ads   = $pdo->query("SELECT COUNT(*) FROM ads")->fetchColumn();
$total_views = $pdo->query("SELECT SUM(views_count) FROM ads")->fetchColumn();
$premium_ads = $pdo->query("SELECT COUNT(*) FROM ads WHERE is_premium = 1")->fetchColumn();

// 4. RÉCUPÉRATION DES DERNIÈRES ANNONCES AVEC INFOS VENDEURS
$sqlAds = "SELECT ads.*, users.fullname, users.whatsapp_phone 
           FROM ads 
           JOIN users ON ads.user_id = users.id 
           ORDER BY ads.created_at DESC LIMIT 15";
$allAds = $pdo->query($sqlAds)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Admin | UniMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root { --admin-dark: #0f172a; }
        body { background: #f1f5f9; display: flex; min-height: 100vh; margin:0; }
        
        /* Sidebar Admin */
        .sidebar { width: 260px; background: var(--admin-dark); color: white; padding: 30px 20px; }
        .sidebar h2 { font-size: 1.2rem; margin-bottom: 40px; color: var(--primary); }
        .nav-link { display: flex; align-items: center; gap: 12px; color: #94a3b8; text-decoration: none; padding: 12px; border-radius: 10px; margin-bottom: 10px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: #1e293b; color: white; }

        /* Main Content */
        .main-admin { flex-grow: 1; padding: 40px; overflow-y: auto; }
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-box { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .stat-box h4 { color: #64748b; font-size: 0.8rem; text-transform: uppercase; margin-bottom: 10px; }
        .stat-box p { font-size: 1.8rem; font-weight: 800; color: var(--admin-dark); }

        /* Table Design */
        .admin-table-card { background: white; border-radius: 20px; padding: 25px; box-shadow: var(--shadow-sm); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #f1f5f9; color: #64748b; font-size: 0.85rem; }
        td { padding: 15px; border-bottom: 1px solid #f8fafc; font-size: 0.9rem; vertical-align: middle; }
        .user-info { display: flex; flex-direction: column; }
        .user-info small { color: #94a3b8; }
        
        /* Action Badges */
        .btn-del { color: #ef4444; background: #fee2e2; padding: 8px; border-radius: 8px; text-decoration: none; }
        .btn-del:hover { background: #ef4444; color: white; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h2>UniMarket Admin</h2>
        <nav>
            <a href="index.php" class="nav-link active"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
            <a href="users.php" class="nav-link"><i class="fa-solid fa-users"></i> Étudiants</a>
            <a href="ads.php" class="nav-link"><i class="fa-solid fa-box"></i> Annonces</a>
            <a href="reports.php" class="nav-link"><i class="fa-solid fa-flag"></i> Signalements</a>
            <hr style="border: 0.5px solid #1e293b; margin: 20px 0;">
            <a href="../index.php" class="nav-link"><i class="fa-solid fa-globe"></i> Voir le site</a>
            <a href="../logout.php" class="nav-link" style="color: #f87171;"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </nav>
    </aside>

    <main class="main-admin">
        <header style="margin-bottom: 30px;">
            <h1 style="font-size: 1.5rem;">Tableau de bord général</h1>
        </header>

        <div class="stats-row">
            <div class="stat-box">
                <h4>Total Étudiants</h4>
                <p><?php echo $total_users; ?></p>
            </div>
            <div class="stat-box">
                <h4>Annonces Actives</h4>
                <p><?php echo $total_ads; ?></p>
            </div>
            <div class="stat-box">
                <h4>Vues Cumulées</h4>
                <p><?php echo number_format($total_views); ?></p>
            </div>
            <div class="stat-box" style="border-left: 4px solid var(--primary);">
                <h4>Annonces Boostées</h4>
                <p><?php echo $premium_ads; ?></p>
            </div>
        </div>

        <div class="admin-table-card">
            <div class="flex-between">
                <h2 style="font-size: 1.1rem;">Annonces récentes à modérer</h2>
                <a href="ads.php" class="see-all" style="font-size: 0.8rem;">Tout gérer</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Article</th>
                        <th>Vendeur</th>
                        <th>Prix</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($allAds as $ad): ?>
                    <tr>
                        <td style="display: flex; align-items: center; gap: 10px;">
                            <img src="../images/uploads/<?php echo $ad['image_path']; ?>" width="40" height="40" style="border-radius: 8px; object-fit: cover;">
                            <strong><?php echo htmlspecialchars($ad['title']); ?></strong>
                        </td>
                        <td>
                            <div class="user-info">
                                <span><?php echo htmlspecialchars($ad['fullname']); ?></span>
                                <small><i class="fa-brands fa-whatsapp"></i> <?php echo $ad['whatsapp_phone']; ?></small>
                            </div>
                        </td>
                        <td><strong><?php echo number_format($ad['price'], 0, ',', ' '); ?> F</strong></td>
                        <td><?php echo date('d/m/Y', strtotime($ad['created_at'])); ?></td>
                        <td>
                            <a href="index.php?delete_ad=<?php echo $ad['id']; ?>" class="btn-del" onclick="return confirm('Supprimer définitivement cette annonce ?')">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>