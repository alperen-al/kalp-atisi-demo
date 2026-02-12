<?php
require_once('tcpdf/tcpdf.php');
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ❗ Daha önce sertifika almış mı kontrol et
$stmt = $conn->prepare("SELECT COUNT(*) FROM certificates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($existing_cert_count);
$stmt->fetch();
$stmt->close();

if ($existing_cert_count > 0) {
    echo "<script>
        alert('❗ Zaten bir sertifikanız var. Yeni bir sertifika isteyemezsiniz.');
        window.location.href = 'profil.php';
    </script>";
    exit;
}

// Satın alma kontrolü
$stmt = $conn->prepare("SELECT COUNT(*) FROM purchases WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($purchase_count);
$stmt->fetch();
$stmt->close();

if ($purchase_count < 1) {
    die("Sertifika almak için en az bir satın alma yapmalısınız.");
}

// Kullanıcı tipi ve isim
$stmt = $conn->prepare("SELECT account_type, full_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($account_type, $full_name);
$stmt->fetch();
$stmt->close();

if ($account_type === 'kurumsal') {
    $stmt = $conn->prepare("SELECT company_name FROM companies WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($full_name);
    $stmt->fetch();
    $stmt->close();
}

// PDF ayarları
$cert_title = "Katılım Sertifikası";
$cert_file = "sertifika_" . time() . ".pdf";
$pdf_path = "certificates/" . $cert_file;
if (!file_exists('certificates')) {
    mkdir('certificates', 0777, true);
}

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Kalp Atışı');
$pdf->SetTitle($cert_title);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

// Arka plan
$bg_image = 'sertifika_arka_plan.png';
$pdf->Image($bg_image, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0);

// Başlık
$pdf->SetFont('dejavusans', 'B', 30);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetXY(0, 28);
$pdf->Cell(297, 15, "KATILIM SERTİFİKASI", 0, 1, 'C');

// İsim
$pdf->SetFont('dejavusans', 'B', 24);
$pdf->SetXY(0, 70);
$pdf->Cell(297, 10, $full_name, 0, 1, 'C');

// Açıklama paragrafı
$pdf->SetFont('dejavusans', '', 16);
$pdf->SetXY(40, 95);
$pdf->MultiCell(217, 10, "Bu belge, Dünya’nın Kalbi Atışı projesine değerli\nkatkılarınız nedeniyle verilmiştir.", 0, 'C', false);

// Alt imza kısmı
$pdf->SetFont('dejavusans', '', 15);
$pdf->SetXY(40, 135);
$pdf->MultiCell(217, 9, "Dünya’nın Kalbi Atışı\nProje Koordinatörü\n" . date("Y"), 0, 'C', false);

// Alt onay yazısı
$pdf->SetFont('dejavusans', 'I', 14);
$pdf->SetXY(0, 200);
$pdf->Cell(297, 10, "Bu sertifika Kalp Atışı Platformu tarafından onaylanmıştır.", 0, 1, 'C');

// PDF'i kaydet
$pdf->Output(__DIR__ . '/' . $pdf_path, 'F');

// Veritabanına kayıt
$stmt = $conn->prepare("INSERT INTO certificates (user_id, cert_title, cert_file, upload_date) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iss", $user_id, $cert_title, $cert_file);
$stmt->execute();
$stmt->close();

// Yeni sekmede aç
echo "<script>
    window.open('$pdf_path', '_blank');
    setTimeout(function() {
        window.location.href = 'profil.php';
    }, 1000);
</script>";
?>