<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();}
    error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);  
require __DIR__ . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: text/html; charset=utf-8');

if (!isset($_GET['book_id'])) die("المعرف غير صالح");

$book_id = (int)$_GET['book_id'];
$stmt = $conn->prepare("
    SELECT r.*, u.username 
    FROM book_rating r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.book_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php if ($result->num_rows > 0): ?>
    <div class="reviews-list">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="review-item mb-4 p-3 border rounded">
                <div class="d-flex justify-content-between mb-2">
                    <h6 class="fw-bold"><?= htmlspecialchars($row['username']) ?></h6>
                    <small class="text-muted"><?= date('Y/m/d', strtotime($row['created_at'])) ?></small>
                </div>
                <div class="rating-stars text-warning mb-2">
                    <?= str_repeat('★', $row['rating']) . str_repeat('☆', 5 - $row['rating']) ?>
                </div>
                <p class="mb-0"><?= nl2br(htmlspecialchars($row['comment'])) ?></p>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info">لا توجد مراجعات حتى الآن</div>
<?php endif; ?>