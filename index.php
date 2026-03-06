<?php
/**
 * UniMarket - Page d'accueil dynamique
 * @author Expert Dev
 */

// 1. Initialisation du système
require_once 'config.php'; // Contient session_start() et la connexion PDO

// 2. Logique de récupération des données (Modèle)
try {
    // On récupère les catégories pour les filtres
    $catStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll();

    // On récupère les annonces : Premium en premier, puis les plus récentes
    $adsStmt = $pdo->query("
        SELECT ads.*, users.fullname, users.university_id 
        FROM ads 
        JOIN users ON ads.user_id = users.id 
        WHERE ads.status = 'active' 
        ORDER BY ads.is_premium DESC, ads.created_at DESC 
        LIMIT 20
    ");
    $annonces = $adsStmt->fetchAll();

} catch (PDOException $e) {
    die("Erreur de chargement : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniMarket | Le commerce étudiant au Bénin</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Styles spécifiques à l'index pour la propreté */
        .hero-section {
            background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%);
            padding: 60px 0;
            color: white;
            text-align: center;
            border-radius: 0 0 40px 40px;
        }
        .category-scroll {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding: 20px 0;
            scrollbar-width: none;
        }
        .category-card {
            background: white;
            padding: 15px 25px;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: var(--dark);
            font-weight: 700;
            white-space: nowrap;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .category-card:hover { transform: translateY(-5px); color: var(--primary); }
    </style>
</head>
<body>

    <header class="main-header">
        <nav class="container flex-between">
            <a href="index.php" class="logo">
                <i class="fa-solid fa-graduation-cap"></i> Uni<span>Market</span>
            </a>
            
            <div class="nav-actions">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="user-menu">
                        <span class="welcome-text">Salut, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                        <a href="dashboard.php" class="btn-icon"><i class="fa-solid fa-circle-user"></i></a>
                        <a href="logout.php" class="btn-logout"><i class="fa-solid fa-power-off"></i></a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="link-login">Connexion</a>
                    <a href="register.php" class="btn-cta">S'inscrire</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <section class="hero-section">
        <div class="container">
            <h1>Trouvez tout sur votre campus.</h1>
            <p>La plateforme n°1 pour acheter, vendre et manger entre étudiants.</p>
            <div class="search-box">
                <form action="annonces.php" method="GET">
                    <input type="text" name="q" placeholder="Que recherchez-vous ?">
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>
        </div>
    </section>

    <main class="container">
        <div class="category-scroll">
            <a href="annonces.php" class="category-card">Tout</a>
            <?php foreach($categories as $cat): ?>
                <a href="annonces.php?cat=<?php echo $cat['id']; ?>" class="category-card">
                    <i class="fa-solid <?php echo $cat['icon']; ?>"></i> 
                    <?php echo $cat['name']; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <section class="listings-section">
            <div class="flex-between">
                <h2>Annonces récentes</h2>
                <a href="annonces.php" class="see-all">Tout voir <i class="fa-solid fa-arrow-right"></i></a>
            </div>

            <div class="grid-listings">
                <?php foreach($annonces as $ad): ?>
                    <article class="card <?php echo $ad['is_premium'] ? 'premium-card' : ''; ?>">
                        <div class="card-img">
                            <?php if($ad['is_premium']): ?>
                                <span class="premium-badge"><i class="fa-solid fa-fire"></i> BOOSTÉ</span>
                            <?php endif; ?>
                            <img src="images/uploads/<?php echo $ad['image_path']; ?>" alt="<?php echo htmlspecialchars($ad['title']); ?>" loading="lazy">
                            <span class="price"><?php echo number_format($ad['price'], 0, ',', ' '); ?> F</span>
                        </div>
                        <div class="card-body">
                            <h3><a href="ads.php?id=<?php echo $ad['id']; ?>"><?php echo htmlspecialchars($ad['title']); ?></a></h3>
                            <p class="seller-info"><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($ad['fullname']); ?></p>
                            <div class="card-footer">
                                <a href="ads.php?id=<?php echo $ad['id']; ?>" class="btn-view">Détails</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <nav class="mobile-nav">
        <a href="index.php" class="active"><i class="fa-solid fa-house"></i></a>
        <a href="annonces.php"><i class="fa-solid fa-magnifying-glass"></i></a>
        <a href="publier.php" class="add-btn"><i class="fa-solid fa-plus"></i></a>
        <a href="dashboard.php"><i class="fa-solid fa-heart"></i></a>
        <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'login.php'; ?>">
            <i class="fa-solid fa-user"></i>
        </a>
    </nav>

</body>
</html>