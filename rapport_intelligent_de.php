<?php

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => false,
    'cookie_samesite' => 'Strict'
]);

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

require 'connexion.php';
require 'vendor/autoload.php';
require 'intelligence_de.php';

use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('Africa/Abidjan');

$annee = $pdo->query("SELECT * FROM annees_scolaires WHERE active = 1 LIMIT 1")->fetch();
$anneeLibelle = $annee['libelle'] ?? date('Y');
$dateRapport = date('d/m/Y à H:i');

$totalPersonnel = (int)$pdo->query("SELECT COUNT(*) FROM personnels")->fetchColumn();
$totalActifs = (int)$pdo->query("SELECT COUNT(*) FROM personnels WHERE statut='actif'")->fetchColumn();
$totalSuspendus = (int)$pdo->query("SELECT COUNT(*) FROM personnels WHERE statut='suspendu'")->fetchColumn();
$totalDocuments = (int)$pdo->query("SELECT COUNT(*) FROM documents_generes")->fetchColumn();
$totalProfs = (int)$pdo->query("SELECT COUNT(*) FROM personnels WHERE fonction='PROFESSEUR'")->fetchColumn();
$totalAdmin = (int)$pdo->query("SELECT COUNT(*) FROM personnels WHERE fonction IN('DE','ADE','DAF','SECRETAIRE')")->fetchColumn();

function e($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

ob_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport intelligent DE</title>
<style>
    body{
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size:12px;
        color:#111827;
        line-height:1.45;
    }
    .header{
        text-align:center;
        border-bottom:3px solid #1d4ed8;
        padding-bottom:12px;
        margin-bottom:22px;
    }
    .header h1{
        margin:0;
        font-size:22px;
        color:#1d4ed8;
        text-transform:uppercase;
    }
    .header p{
        margin:4px 0;
        color:#4b5563;
    }
    .badge-score{
        display:inline-block;
        padding:8px 14px;
        border-radius:20px;
        background:#dbeafe;
        color:#1d4ed8;
        font-weight:bold;
        margin-top:8px;
    }
    h2{
        font-size:16px;
        color:#0f172a;
        border-left:5px solid #1d4ed8;
        padding-left:8px;
        margin-top:24px;
        margin-bottom:10px;
    }
    .stats{
        width:100%;
        border-collapse:collapse;
        margin-bottom:16px;
    }
    .stats td{
        border:1px solid #e5e7eb;
        padding:10px;
        width:33.33%;
        vertical-align:top;
    }
    .label{
        color:#64748b;
        font-size:11px;
        text-transform:uppercase;
        font-weight:bold;
    }
    .value{
        font-size:20px;
        font-weight:bold;
        color:#111827;
        margin-top:4px;
    }
    .box{
        border:1px solid #e5e7eb;
        border-radius:8px;
        padding:10px 12px;
        margin-bottom:8px;
        background:#f8fafc;
    }
    .danger{border-left:5px solid #dc2626;}
    .warning{border-left:5px solid #f59e0b;}
    .info{border-left:5px solid #2563eb;}
    .success{border-left:5px solid #16a34a;}
    .empty{
        color:#64748b;
        font-style:italic;
        background:#f8fafc;
        padding:10px;
        border:1px solid #e5e7eb;
        border-radius:8px;
    }
    .footer{
        margin-top:28px;
        padding-top:10px;
        border-top:1px solid #e5e7eb;
        font-size:10px;
        color:#64748b;
        text-align:center;
    }
</style>
</head>
<body>

<div class="header">
    <h1>Rapport intelligent du DE</h1>
    <p>CMAK Facobly - Gestion intelligente du personnel</p>
    <p>Année scolaire : <?= e($anneeLibelle) ?> | Généré le <?= e($dateRapport) ?></p>
    <div class="badge-score">Score global : <?= (int)$scoreIntelligence ?>/100 — <?= e($niveauSysteme) ?></div>
</div>

<h2>1. Synthèse générale</h2>
<table class="stats">
<tr>
    <td><div class="label">Total personnel</div><div class="value"><?= $totalPersonnel ?></div></td>
    <td><div class="label">Personnel actif</div><div class="value"><?= $totalActifs ?></div></td>
    <td><div class="label">Personnel suspendu</div><div class="value"><?= $totalSuspendus ?></div></td>
</tr>
<tr>
    <td><div class="label">Professeurs</div><div class="value"><?= $totalProfs ?></div></td>
    <td><div class="label">Administration</div><div class="value"><?= $totalAdmin ?></div></td>
    <td><div class="label">Documents générés</div><div class="value"><?= $totalDocuments ?></div></td>
</tr>
</table>

<h2>2. Analyse intelligente</h2>
<?php if(!empty($intelligence)): ?>
    <?php foreach($intelligence as $item): ?>
        <div class="box info"><?= e($item['message'] ?? '') ?></div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="empty">Aucune analyse disponible.</div>
<?php endif; ?>

<h2>3. Alertes automatiques</h2>
<?php if(!empty($alertesAuto)): ?>
    <?php foreach($alertesAuto as $alerte): ?>
        <div class="box <?= e($alerte['niveau'] ?? 'warning') ?>"><?= e($alerte['message'] ?? '') ?></div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="empty">Aucune alerte automatique détectée.</div>
<?php endif; ?>

<h2>4. Anomalies détectées</h2>
<?php if(!empty($anomalies)): ?>
    <?php foreach($anomalies as $anomalie): ?>
        <div class="box <?= e($anomalie['niveau'] ?? 'danger') ?>"><?= e($anomalie['message'] ?? '') ?></div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="box success">Aucune anomalie majeure détectée.</div>
<?php endif; ?>

<h2>5. Recommandations</h2>
<?php if(!empty($recommandations)): ?>
    <?php foreach($recommandations as $rec): ?>
        <div class="box success"><?= e($rec) ?></div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="empty">Aucune recommandation disponible.</div>
<?php endif; ?>

<div class="footer">
    Rapport généré automatiquement par le système intelligent de gestion du personnel CMAK Facobly.
</div>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$fileName = 'rapport_intelligent_de_' . date('Ymd_His') . '.pdf';
$dompdf->stream($fileName, ["Attachment" => false]);
exit();
