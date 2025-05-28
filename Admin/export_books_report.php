<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../includes/config.php';

// التحقق من الصلاحيات
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("الوصول مرفوض!");
}

ob_end_clean(); // تنظيف المخزن المؤقت

// جلب البيانات
$books = $conn->query("SELECT * FROM books");

// تحديد الصيغة
$format = $_GET['export'] ?? 'pdf';

// معالجة CSV
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="books_report.csv"'); // إضافة علامات الاقتباس
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    
    // العناوين العربية
    fputcsv($output, ['العنوان', 'المؤلف', 'النوع', 'الكمية', 'السعر', 'الصفحات', 'تاريخ النشر', 'ISBN']);
    
    while ($book = $books->fetch_assoc()) {
        fputcsv($output, [
            $book['title'] ?? '--',
            $book['author'] ?? '--',
            $book['material_type'] ?? '--',
            $book['quantity'] ?? 0,
            $book['price'] ?? 0,
            $book['page_count'] ?? '--',
            $book['publication_date'] ?? '--',
            $book['isbn'] ?? '--'
        ]);
    }
    
    fclose($output);
    exit();

} elseif ($format === 'pdf') {
    require_once(__DIR__ . '/../libs/tcpdf/vendor/tecnickcom/tcpdf/tcpdf.php'); // تأكد من مسار TCPDF
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetFont('aealarabiya', '', 12);
    $pdf->AddPage();
    
    // HTML للجدل
    $html = '<h1 style="text-align:center;font-family:aealarabiya;">تقرير الكتب</h1>';
    $html .= '<table border="1" cellpadding="5" style="font-family:aealarabiya;">';
    $html .= '<tr>
        <th>العنوان</th>
        <th>المؤلف</th>
        <th>النوع</th>
        <th>الكمية</th>
        <th>السعر</th>
        <th>الصفحات</th>
        <th>تاريخ النشر</th>
        <th>ISBN</th>
    </tr>';
    
    while ($book = $books->fetch_assoc()) {
        $html .= '<tr>';
        $html .= '<td>' . ($book['title'] ?? '--') . '</td>';
        $html .= '<td>' . ($book['author'] ?? '--') . '</td>';
        $html .= '<td>' . ($book['material_type'] ?? '--') . '</td>';
        $html .= '<td>' . ($book['quantity'] ?? 0) . '</td>';
        $html .= '<td>' . ($book['price'] ?? 0) . '</td>';
        $html .= '<td>' . ($book['page_count'] ?? '--') . '</td>';
        $html .= '<td>' . ($book['publication_date'] ?? '--') . '</td>';
        $html .= '<td>' . ($book['isbn'] ?? '--') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('books_report.pdf','D'); // التنزيل المباشر
    exit();
}

exit();