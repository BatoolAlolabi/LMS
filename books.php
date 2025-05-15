<?php
// ملف admin/dashboard.php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <?php
        $sql = "SELECT * FROM books";
        $result = $conn->query($sql);
        
        while($row = $result->fetch_assoc()){
            echo '<div class="col-md-4 mb-4">';
            echo '<div class="card">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">'.$row['title'].'</h5>';
            echo '<p class="card-text">المؤلف: '.$row['author'].'</p>';
            echo '<p class="card-text">النوع: '.$row['type'].'</p>';
            echo '<a href="borrow.php?book_id='.$row['id'].'" class="btn btn-primary">استعارة</a>';
            echo '</div></div></div>';
        }
        ?>
    </div>
</div>
<?php foreach ($books as $book): ?>
<div class="book-card">
    <h3><?= htmlspecialchars($book['title']) ?></h3>
    <p>الكمية المتاحة: <?= $book['quantity'] ?></p>
    
    <?php if ($book['quantity'] > 0): ?>
    <form method="POST" action="request.php">
        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
        <button type="submit" name="request_book" class="btn btn-primary">
            طلب الاستعارة
        </button>
    </form>
    <?php else: ?>
    <button class="btn btn-secondary" disabled>غير متوفر</button>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php
require __DIR__ . '/../includes/footer.php';
?>