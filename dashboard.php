<?php
/**
 * UniMarket - Tableau de Bord Étudiant
 */
require_once 'config.php'; // Initialise session_start() et $pdo

// 1. Sécurité : Si non connecté, redirection immédiate (Le Gardien)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?msg=auth_required');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// 2. Logique de suppression d'annonce
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Sécurité : On vérifie que l'annonce appartient bien à l'utilisateur connecté
    $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $user_id]);
    header('Location: dashboard.php?msg=deleted');
    exit;
}

// 3. Logique pour marquer comme "Vendu"
if (isset($_GET['sold_id'])) {
    $sold_id = intval($_GET['sold_id']);
    $stmt = $pdo->prepare("UPDATE ads SET status = 'sold' WHERE id = ? AND user_id = ?");
    $stmt->execute([$sold_id, $user_id]);
    header('Location: dashboard.php?msg=marked_sold');
    exit;
}

// 4. Récupération des statistiques de l'étudiant
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_ads,
        SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold_ads,
        SUM(views_count) as total_views
    FROM ads WHERE user_id = ?
");
$statsStmt->execute([$user_id]);
$stats = $statsStmt->fetch();

// 5. Récupération des annonces de l'utilisateur
$adsStmt = $pdo->prepare("SELECT * FROM ads WHERE user_id = ? ORDER BY created_at DESC");
$adsStmt->execute([$user_id]);
$my_ads = $adsStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Tableau de Bord | UniMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dashboard-container { max-width: 1000px; margin: 40px auto; padding: 20px; }
        .welcome-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 25px; border-radius: 20px; box-shadow: var(--shadow-sm); text-align: center; border-bottom: 4px solid var(--primary); }
        .stat-card h3 { font-size: 2rem; color: var(--dark); margin-bottom: 5px; }
        .stat-card p { color: var(--gray); font-weight: 600; font-size: 0.9rem; }

        /* Ads List */
        .ad-item { background: white; border-radius: 20px; padding: 15px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; box-shadow: var(--shadow-sm); transition: 0.3s; }
        .ad-item:hover { transform: scale(1.01); }
        .ad-item img { width: 100px; height: 100px; border-radius: 15px; object-fit: cover; }
        .ad-details { flex-grow: 1; }
        .ad-details h4 { font-size: 1.1rem; margin-bottom: 5px; }
        .ad-status { font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; font-weight: 800; }
        .status-active { background: #dcfce7; color: #16a34a; }
        .status-sold { background: #f1f5f9; color: #64748b; }

        /* Buttons Group */
        .action-btns { display: flex; gap: 10px; }
        .btn-action { padding: 10px; border-radius: 10px; border: none; cursor: pointer; text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
        .btn-sold { background: #f1f5f9; color: var(--dark); }
        .btn-edit { background: var(--primary-soft); color: var(--primary); }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-logout-top { background: #334155; color: white; padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem; }

        @media (max-width: 600px) {
            .ad-item { flex-direction: column; text-align: center; }
            .action-btns { justify-content: center; width: 100%; }
        }
    </style>
</head>
<body style="background: #f8fafc;">

    <div class="dashboard-container">
        
        <div class="welcome-header">
            <div>
                <h1>Salut, <?php echo htmlspecialchars($user_name); ?> 👋</h1>
                <p style="color: var(--gray);">Gère tes annonces et tes ventes en un clin d'œil.</p>
            </div>
            <a href="logout.php" class="btn-logout-top"><i class="fa-solid fa-power-off"></i> Déconnexion</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_ads'] ?? 0; ?></h3>
                <p>Annonces postées</p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($stats['total_views'] ?? 0); ?></h3>
                <p>Vues totales</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['sold_ads'] ?? 0; ?></h3>
                <p>Ventes réussies</p>
            </div>
        </div>

        <div class="flex-between" style="margin-bottom: 20px;">
            <h2>Mes annonces</h2>
            <a href="publier.php" class="btn-cta">+ Publier nouveau</a>
        </div>

        <?php if (count($my_ads) > 0): ?>
            <?php foreach ($my_ads as $ad): ?>
                <div class="ad-item">
                    <img src="images/uploads/<?php echo $ad['image_path']; ?>" alt="Produit">
                    <div class="ad-details">
                        <span class="ad-status <?php echo ($ad['status'] == 'active') ? 'status-active' : 'status-sold'; ?>">
                            <?php echo ($ad['status'] == 'active') ? 'EN LIGNE' : 'VENDU'; ?>
                        </span>
                        <h4><?php echo htmlspecialchars($ad['title']); ?></h4>
                        <p style="font-weight: 800; color: var(--primary);"><?php echo number_format($ad['price'], 0, ',', ' '); ?> F</p>
                        <p style="font-size: 0.8rem; color: var(--gray);"><i class="fa-solid fa-eye"></i> <?php echo $ad['views_count']; ?> vues</p>
                    </div>
                    
                    <div class="action-btns">
                        <?php if ($ad['status'] == 'active'): ?>
                            <a href="dashboard.php?sold_id=<?php echo $ad['id']; ?>" class="btn-action btn-sold" title="Marquer comme vendu"><i class="fa-solid fa-check"></i></a>
                        <?php endif; ?>
                        
                        <a href="modifier_annonce.php?id=<?php echo $ad['id']; ?>" class="btn-action btn-edit" title="Modifier"><i class="fa-solid fa-pen-to-square"></i></a>
                        
                        <a href="dashboard.php?delete_id=<?php echo $ad['id']; ?>" class="btn-action btn-delete" 
                           onclick="return confirm('Es-tu sûr de vouloir supprimer cette annonce ?')" title="Supprimer">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 50px; background: white; border-radius: 20px;">
                <i class="fa-solid fa-box-open" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
                <p>Tu n'as pas encore d'annonces en ligne.</p>
                <a href="publier.php" style="color: var(--primary); font-weight: 700;">Commence à vendre maintenant !</a>
            </div>
        <?php endif; ?>

    </div>

    <?php include 'footer_mobile.php'; ?>

</body>
</html>