<?php
ob_start();

require_once('../vendor/tcpdf/tcpdf.php');
session_start();
require_once '../db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: ../index.php');
    exit();
}

// Create PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Student Election Voting System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle('Election Results Report');
$pdf->SetSubject('Election Results');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 20);

// Title
$pdf->Cell(0, 10, 'Student Election Voting Results', 0, 1, 'C');
$pdf->Ln(10);

// Get election results from db.php
$query = "SELECT c.*, COUNT(v.vote_id) as vote_count 
          FROM candidate c 
          LEFT JOIN vote v ON c.candidate_id = v.candidate_id 
          GROUP BY c.candidate_id 
          ORDER BY c.position, vote_count DESC";
$result = mysqli_query($conn, $query);

// Table header
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(60, 10, 'Candidate Name', 1, 0, 'C');
$pdf->Cell(60, 10, 'Position', 1, 0, 'C');
$pdf->Cell(30, 10, 'Votes', 1, 0, 'C');
$pdf->Cell(30, 10, 'Percentage', 1, 1, 'C');

// Table rows
$pdf->SetFont('helvetica', '', 10);
$totalVotesQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM vote");
$totalVotesRow = mysqli_fetch_assoc($totalVotesQuery);
$totalVotes = $totalVotesRow['total'];
$currentPosition = '';

while ($row = mysqli_fetch_assoc($result)) {
    if ($currentPosition != $row['position']) {
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, $row['position'], 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $currentPosition = $row['position'];
    }
    
    $percentage = $totalVotes > 0 ? ($row['vote_count'] / $totalVotes) * 100 : 0;
    
    $pdf->Cell(60, 8, $row['first_name'] . ' ' . $row['last_name'], 1, 0);
    $pdf->Cell(60, 8, $row['position'], 1, 0);
    $pdf->Cell(30, 8, $row['vote_count'], 1, 0, 'C');
    $pdf->Cell(30, 8, number_format($percentage, 1) . '%', 1, 1, 'C');
}

// Total votes
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Total Votes Cast: ' . $totalVotes, 0, 1, 'C');

// Output PDF
ob_end_clean(); // Clean any output buffer
$pdf->Output('election_results.pdf', 'D');
exit();
