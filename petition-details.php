<?php
require_once 'includes/config.php';
session_start();

$db = Database::getInstance();
$petition_id = intval($_GET['id'] ?? 0);

// Pobierz szczegóły petycji
$petition = $db->query(
    "SELECT p.*, u.name as creator_name,
            (SELECT COUNT(*) FROM signatures WHERE petition_id = p.id) as signatures_count
     FROM petitions p
     JOIN users u ON p.created_by = u.id
     WHERE p.id = ? AND p.status = 'active'",
    [$petition_id]
)->fetch();

if (!$petition) {
    header('Location: petitions.php');
    exit();
}

// Pobierz komentarze
$comments = $db->query(
    "SELECT s.*, u.name
     FROM signatures s
     JOIN users u ON s.user_id = u.id
     WHERE s.petition_id = ? AND s.public = 1 AND s.comment IS NOT NULL
     ORDER BY s.created_at DESC",
    [$petition_id]
)->fetchAll();

// Sprawdź czy użytkownik podpisał
$signed = false;
if (isset($_SESSION['user_id'])) {
    $signed = $db->query(
        "SELECT id FROM signatures WHERE petition_id = ? AND user_id = ?",
        [$petition_id, $_SESSION['user_id']]
    )->fetch();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($petition['title']); ?> - BreathTime</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .petition-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .petition-header {
            text-align: center;
            margin-bottom: 2rem;
            color: white;
        }

        .petition-content {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 200, 0, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 2rem;
        }

        .petition-title {
            color: white;
            font-size: 2em;
            margin-bottom: 1rem;
        }

        .petition-meta {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9em;
            margin-bottom: 1rem;
        }

        .petition-description {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 2rem;
            white-space: pre-line;
        }

        .petition-progress {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            height: 20px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 200, 0, 0.8), rgba(255, 180, 0, 0.8));
            transition: width 0.3s ease;
        }

        .petition-stats {
            display: flex;
            justify-content: space-between;
            color: white;
            margin-bottom: 2rem;
            font-size: 1.2em;
        }

        .comments-section {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 200, 0, 0.1);
            border-radius: 15px;
            padding: 20px;
        }

        .comments-header {
            color: white;
            margin-bottom: 1.5rem;
            font-size: 1.5em;
        }

        .comment {
            border-bottom: 1px solid rgba(255, 200, 0, 0.1);
            padding: 15px 0;
        }

        .comment:last-child {
            border-bottom: none;
        }

        .comment-author {
            color: white;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .comment-date {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8em;
        }

        .comment-text {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-top: 0.5rem;
        }

        .share-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin: 2rem 0;
        }

        .share-button {
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.3s ease;
        }

        .share-button:hover {
            transform: translateY(-2px);
        }

        .facebook {
            background: #1877f2;
        }

        .twitter {
            background: #1da1f2;
        }

        .email {
            background: #ea4335;
        }

        .sign-button {
            display: block;
            width: 200px;
            margin: 2rem auto;
            padding: 12px;
            background: linear-gradient(45deg, rgba(255, 200, 0, 0.8), rgba(255, 180, 0, 0.8));
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 1.1em;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sign-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(255, 200, 0, 0.3);
        }

        .sign-button:disabled {
            background: rgba(255, 255, 255, 0.2);
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="petition-container">
        <div class="petition-content">
            <h1 class="petition-title"><?php echo htmlspecialchars($petition['title']); ?></h1>
            
            <div class="petition-meta">
                Utworzona przez: <?php echo htmlspecialchars($petition['creator_name']); ?>
                <br>
                Data: <?php echo date('d.m.Y', strtotime($petition['created_at'])); ?>
            </div>
            
            <div class="petition-description">
                <?php echo nl2br(htmlspecialchars($petition['description'])); ?>
            </div>

            <div class="petition-progress">
                <div class="progress-bar" style="width: <?php 
                    echo min(($petition['signatures_count'] / $petition['target_signatures']) * 100, 100);
                ?>%"></div>
            </div>

            <div class="petition-stats">
                <span><?php echo number_format($petition['signatures_count']); ?> podpisów</span>
                <span>Cel: <?php echo number_format($petition['target_signatures']); ?></span>
            </div>

            <div class="share-buttons">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://breathtime.info/petition-details.php?id=' . $petition_id); ?>" 
                   class="share-button facebook" target="_blank">
                    Udostępnij na Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($petition['title'] . ' - Podpisz petycję na BreathTime!'); ?>&url=<?php echo urlencode('https://breathtime.info/petition-details.php?id=' . $petition_id); ?>" 
                   class="share-button twitter" target="_blank">
                    Udostępnij na Twitter
                </a>
                <a href="mailto:?subject=<?php echo urlencode($petition['title']); ?>&body=<?php echo urlencode("Sprawdź tę petycję na BreathTime:\n\nhttps://breathtime.info/petition-details.php?id=" . $petition_id); ?>" 
                   class="share-button email">
                    Wyślij email
                </a>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($signed): ?>
                    <button class="sign-button" disabled>Już podpisano</button>
                <?php else: ?>
                    <a href="sign-petition.php?id=<?php echo $petition_id; ?>" class="sign-button">
                        Podpisz petycję
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="sign-button">Zaloguj się aby podpisać</a>
            <?php endif; ?>
        </div>

        <div class="comments-section">
            <h2 class="comments-header">Komentarze (<?php echo count($comments); ?>)</h2>
            
            <?php if ($comments): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-author">
                            <?php echo htmlspecialchars($comment['name']); ?>
                            <span class="comment-date">
                                <?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                            </span>
                        </div>
                        <div class="comment-text">
                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: rgba(255, 255, 255, 0.6); text-align: center;">
                    Brak komentarzy. Bądź pierwszy!
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
