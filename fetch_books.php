<?php
require __DIR__ . '/includes/config.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';

// بناء الاستعلام الأساسي
$query = "
    SELECT 
        books.*, 
        categories.category_name 
    FROM books
    INNER JOIN categories ON books.category_id = categories.category_id
    WHERE (books.type = 'physical' AND books.quantity > 0) OR books.type = 'e-book'
";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (books.title LIKE ? OR books.author LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

if ($category !== 'all') {
    $query .= " AND categories.category_id = ?";
    $params[] = $category;
    $types .= 'i';
}

$stmt = $conn->prepare($query);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['books' => $books]);