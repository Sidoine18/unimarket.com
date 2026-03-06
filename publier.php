<?php
/**
 * UniMarket - Publication d'annonce (Niveau Expert)
 */
require_once 'config.php'; // Initialise session_start() et $pdo

// 1. PROTECTION : Seul un utilisateur connecté peut publier
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?msg=auth_required');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = null;

// 2. LOGIQUE DE PUBLICATION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $price = intval($_POST['price']);
    $category = intval($_POST['category']);
    $description = htmlspecialchars(trim($_POST['description']));

    // --- GESTION DE L'IMAGE (SÉCURITÉ MAXIMALE) ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            // Renommer l'image pour éviter les doublons et les caractères spéciaux
            $new_name = "IMG_" . uniqid() . "." . $ext;
            $upload_path = "images/uploads/" . $new_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                
                try {
                    // INSERTION DANS LA BASE DE DONNÉES
                    // Note : Le numéro WhatsApp est déjà lié à l'user_id dans la table 'users'
                    $sql = "INSERT INTO ads (user_id, category_id, title, description, price, image_path, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'active')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$user_id, $category, $title, $description, $price, $new_name]);

                    header('Location: dashboard.php?msg=published');
                    exit;
                } catch (PDOException $e) {
                    $error = "Erreur base de données : " . $e->getMessage();
                }
            } else {
                $error = "Échec du téléchargement de l'image.";
            }
        } else {
            $error = "Format d'image non supporté (JPG, PNG, WEBP uniquement).";
        }
    } else {
        $error = "Veuillez ajouter une image pour votre article.";
    }
}

// Récupérer les catégories pour le menu déroulant
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendre un article | UniMarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .publish-container { max-width: 600px; margin: 30px auto; padding: 20px; }
        .publish-card { background: white; padding: 30px; border-radius: 24px; box-shadow: var(--shadow-lg); }
        .image-upload-zone {
            border: 2px dashed #cbd5e1;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .image-upload-zone:hover { border-color: var(--primary); background: #f8fafc; }
        .image-upload-zone i { font-size: 2.5rem; color: var(--gray); margin-bottom: 10px; }
        .preview-img { max-width: 100%; border-radius: 10px; display: none; margin-top: 10px; }
        
        .whatsapp-notice {
            background: #dcfce7;
            color: #16a34a;
            padding: 15px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body style="background: #f8fafc;">

    <div class="publish-container">
        <div class="publish-card">
            <h2 style="margin-bottom: 10px;">Vendre un article 🚀</h2>
            <p style="color: var(--gray); margin-bottom: 20px;">Remplis les détails pour attirer des acheteurs.</p>

            <div class="whatsapp-notice">
                <i class="fa-brands fa-whatsapp" style="font-size: 1.2rem;"></i>
                <span>Les acheteurs vous contacteront directement sur le numéro WhatsApp lié à votre compte.</span>
            </div>

            <?php if ($error): ?>
                <div style="background:#fee2e2; color:#dc2626; padding:12px; border-radius:10px; margin-bottom:20px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="publier.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label style="font-weight:700;">Photo de l'article</label>
                    <div class="image-upload-zone" onclick="document.getElementById('file-input').click()">
                        <i class="fa-solid fa-camera-retro"></i>
                        <p id="upload-text">Cliquez pour ajouter une photo</p>
                        <img id="img-preview" class="preview-img">
                    </div>
                    <input type="file" id="file-input" name="image" accept="image/*" style="display:none;" onchange="previewImage(this)">
                </div>

                <div class="form-group">
                    <label>Titre de l'annonce</label>
                    <input type="text" name="title" placeholder="Ex: iPhone 12 Pro Max 128Go" required>
                </div>

                <div class="form-group">
                    <label>Prix (FCFA)</label>
                    <input type="number" name="price" placeholder="Ex: 15000" required>
                </div>

                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="category" required style="width:100%; padding:12px; border-radius:12px; border:2px solid #f1f5f9;">
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Description (État, détails, lieu de rendez-vous...)</label>
                    <textarea name="description" rows="4" placeholder="Décrivez votre article..." required style="width:100%; padding:12px; border-radius:12px; border:2px solid #f1f5f9;"></textarea>
                </div>

                <button type="submit" class="btn-auth" style="width:100%; margin-top:10px;">Publier maintenant</button>
            </form>
        </div>
    </div>

    <script>
        // Aperçu de l'image avant l'envoi
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('img-preview').src = e.target.result;
                    document.getElementById('img-preview').style.display = 'block';
                    document.getElementById('upload-text').style.display = 'none';
                    document.querySelector('.image-upload-zone i').style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

    <?php include 'footer_mobile.php'; ?>
</body>
</html>