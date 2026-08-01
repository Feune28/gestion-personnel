<?php

session_start();

if(!isset($_SESSION['admin'])){
    header('Location: login.php');
    exit();
}

require 'connexion.php';
require 'intelligence_daf.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;

date_default_timezone_set('Africa/Abidjan');

function argent_pdf($montant){
    return number_format((float)$montant, 0, ',', ' ') . ' FCFA';
}

$dateGeneration = date('d/m/Y à H:i');

ob_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
body{
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
    color:#111827;
}
.header{
    text-align:center;
    border-bottom:2px solid #1d4ed8;
    padding-bottom:12px;
    margin-bottom:20px;
}
h1{
    color:#1d4ed8;
    font-size:20px;
}
h2{
    color:#0f172a;
    font-size:15px;
    margin-top:22px;
    border-bottom:1px solid #e5e7eb;
    padding-bottom:6px;
}
.box{
    border:1px solid #e5e7eb;
    border-radius:8px;
    padding:10px;
    margin-bottom:10px;
}
.danger{ color:#dc2626; }
.warning{ color:#d97706; }
.success{ color:#16a34a; }
.info{ color:#1d4ed8; }
.footer{
    margin-top:35px;
    font-size:10px;
    text-align:center;
    color:#6b7280;
}
</style>
</head>
<body>

<div class="header">
    <h1>Rapport intelligent DAF</h1>
    <p>Gestion financière du personnel - CMAK Facobly</p>
    <p>Généré le <?= $dateGeneration ?></p>
</div>

<h2>Résumé financier</h2>

<div class="box">
    <strong>Masse salariale du mois :</strong>
    <?= argent_pdf($masseSalariale ?? 0) ?>
</div>

<div class="box">
    <strong>Total des avances :</strong>
    <?= argent_pdf($totalAvances ?? 0) ?>
</div>

<div class="box">
    <strong>Total des retenues :</strong>
    <?= argent_pdf($totalRetenues ?? 0) ?>
</div>

<div class="box">
    <strong>Nombre d'impayés :</strong>
    <?= (int)($totalImpayes ?? 0) ?>
</div>

<div class="box">
    <strong>Score financier intelligent :</strong>
    <?= (int)($scoreDAF ?? 0) ?>/100
    <br>
    <strong>Niveau :</strong>
    <?= htmlspecialchars($niveauDAF ?? 'Non défini') ?>
</div>

<h2>Analyse intelligente</h2>

<?php if(!empty($intelligenceDAF)): ?>
    <?php foreach($intelligenceDAF as $item): ?>
        <div class="box info">
            <?= htmlspecialchars($item['message']) ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="box">Aucune analyse disponible.</div>
<?php endif; ?>

<h2>Alertes financières</h2>

<?php if(!empty($alertesDAF)): ?>
    <?php foreach($alertesDAF as $a): ?>
        <div class="box <?= htmlspecialchars($a['niveau']) ?>">
            <?= htmlspecialchars($a['message']) ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="box success">Aucune alerte financière majeure détectée.</div>
<?php endif; ?>

<h2>Anomalies financières</h2>

<?php if(!empty($anomaliesDAF)): ?>
    <?php foreach($anomaliesDAF as $a): ?>
        <div class="box <?= htmlspecialchars($a['niveau']) ?>">
            <?= htmlspecialchars($a['message']) ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="box success">Aucune anomalie financière détectée.</div>
<?php endif; ?>

<h2>Recommandations</h2>

<?php if(!empty($recommandationsDAF)): ?>
    <?php foreach($recommandationsDAF as $r): ?>
        <div class="box">
            <?= htmlspecialchars($r) ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="box">Aucune recommandation particulière.</div>
<?php endif; ?>

<div class="footer">
    Rapport généré automatiquement par l'application intelligente de gestion du personnel.
</div>

</body>
</html>

<?php
$html = ob_get_clean();

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("rapport_intelligent_daf.pdf", [
    "Attachment" => false
]);
?>