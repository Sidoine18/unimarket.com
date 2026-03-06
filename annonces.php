<?php
/**
 * UniMarket - Moteur de recherche d'annonces
 */
require_once 'config.php'; // Initialise session_start() et $pdo

// 1. Récupération des filtres depuis l'URL (GET)
$category_filter = isset($_GET['cat']) ? intval($_GET['cat']) : null;
$search_query = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';

// 2. Construction de la requête SQL Dynamique
$sql = "SELECT ads.*, users.fullname, categories.name as cat_name 
        FROM ads 
        JOIN users ON ads.user_id = users.id 
        JOIN categories ON ads.category_id = categories.id 
        WHERE ads.status = 'active'";

$params = [];

// Filtre par catégorie
if ($category_filter) {
    $sql .= " AND ads.category_id = ?";
    $params[] = $category_filter;
}

// Filtre par recherche textuelle
if ($search_query) {
    $sql .= " AND (ads.title LIKE ? OR ads.description LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

// Tri : Premium d'abord, puis les plus récents
$sql .= " ORDER BY ads.is_premium DESC, ads.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$annonces = $stmt->fetchAll();

// Récupérer les catégories pour la barre de filtres
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toutes les annonces | UniMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .filter-section {
            background: white;
            padding: 15px 0;
            position: sticky;
            top: 70px;
            z-index: 100;
            border-bottom: 1px solid #edf2f7;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .filter-chips {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            scrollbar-width: none;
            padding: 5px 0;
        }
        .chip {
            padding: 8px 18px;
            background: #f1f5f9;
            border-radius: 20px;
            text-decoration: none;
            color: var(--gray);
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
            transition: 0.3s;
        }
        .chip.active {
            background: var(--primary);
            color: white;
        }
        .no-results {
            text-align: center;
            padding: 100px 20px;
        }
        .no-results i { font-size: 4rem; color: #cbd5e1; margin-bottom: 20px; }
    </style>
</head>
<body>

    <?php include 'header.php'; // Astuce Expert : Inclure le header pour ne pas répéter le code ?>

    <div class="filter-section">
        <div class="container">
            <form action="annonces.php" method="GET" style="margin-bottom: 15px;">
                <div class="search-container">
                    <input type="text" name="q" placeholder="Rechercher un article..." value="<?php echo $search_query; ?>">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>

            <div class="filter-chips">
                <a href="annonces.php" class="chip <?php echo !$category_filter ? 'active' : ''; ?>">Tous</a>
                <?php foreach($categories as $cat): ?>
                    <a href="annonces.php?cat=<?php echo $cat['id']; ?>&q=<?php echo $search_query; ?>" 
                       class="chip <?php echo ($category_filter == $cat['id']) ? 'active' : ''; ?>">
                        <?php echo $cat['name']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <main class="container" style="margin-top: 30px;">
        <p style="color: var(--gray); margin-bottom: 20px;">
            <?php echo count($annonces); ?> annonces trouvées 
            <?php if($search_query) echo "pour '<strong>$search_query</strong>'"; ?>
        </p>

        <?php if(count($annonces) > 0): ?>
            <div class="grid-listings">
                <?php foreach($annonces as $ad): ?>
                    <article class="card <?php echo $ad['is_premium'] ? 'premium-card' : ''; ?>">
                        <div class="card-img">
                            <?php if($ad['is_premium']): ?>
                                <span class="premium-badge"><i class="fa-solid fa-bolt"></i> PREMIUM</span>
                            <?php endif; ?>
                            <img src="images/uploads/<?php echo $ad['image_path']; ?>" alt="Annonce">
                            <span class="price"><?php echo number_format($ad['price'], 0, ',', ' '); ?> F</span>
                        </div>
                        <div class="card-body">
                            <span class="tag"><?php echo $ad['cat_name']; ?></span>
                            <h3><?php echo htmlspecialchars($ad['title']); ?></h3>
                            <p class="seller"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($ad['fullname']); ?></p>
                            <div class="card-footer">
                                <a href="ads.php?id=<?php echo $ad['id']; ?>" class="btn-view">Voir l'offre</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fa-solid fa-box-open"></i>
                <h2>Oups ! Aucun résultat.</h2>
                <p>Essaie de modifier tes filtres ou ta recherche.</p>
                <a href="annonces.php" class="btn-cta" style="display:inline-block; margin-top:20px;">Réinitialiser</a>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'footer_mobile.php'; ?>

</body>
</html>