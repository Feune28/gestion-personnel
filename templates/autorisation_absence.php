!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
@page{size:A4 portrait;margin:18px;}
*{box-sizing:border-box;}
body{margin:0;padding:25px 38px 35px 38px;font-family:"Times New Roman",serif;font-size:15px;line-height:1.4;color:#000;}
.page-border{position:fixed;top:7px;right:7px;bottom:7px;left:7px;border:3px solid #ef7d18;}
.header-table{width:100%;border-collapse:collapse;table-layout:fixed;}
.header-table td{width:50%;padding:0;vertical-align:top;text-align:center;font-size:13px;line-height:1.3;}
.header-left{padding-right:18px;}
.header-right{padding-left:18px;}
.ministere{height:52px;font-weight:bold;}
.republique,.devise,.drenaet{font-weight:bold;}
.separator{width:105px;margin:7px auto;border-top:1px solid #000;}
.logo-cmak{display:block;width:92px;height:92px;margin:9px auto 0;}
.logo-armoirie{display:block;width:88px;height:88px;margin:7px auto 5px;}
.title{text-align:center;margin:20px 0 23px;}
.title span{display:inline-block;padding:7px 25px;border:2px solid #000;border-radius:4px;font-size:21px;font-weight:bold;}
.content{text-align:justify;}
.info{width:88%;margin:14px auto;}
.info-line{padding:5px 0;}
.label{display:inline-block;width:175px;font-weight:bold;}
.value{font-weight:bold;}
.signature{width:46%;margin-top:18px;margin-left:54%;text-align:center;line-height:1.5;}
.signature-name{display:inline-block;min-width:185px;padding-top:5px;font-weight:bold;}
.footer{position:fixed;right:45px;bottom:14px;left:45px;padding-top:5px;border-top:1px solid #ef7d18;text-align:center;font-size:10px;}
</style>
</head>
<body>
<?php
$cheminLogoCmak='C:/wamp64/www/app_exp/uploads/logo/cmak.png';
$cheminArmoirie='C:/wamp64/www/app_exp/uploads/logo/armoirie.png';
$logoCmak=file_exists($cheminLogoCmak)?'data:image/png;base64,'.base64_encode(file_get_contents($cheminLogoCmak)):'';
$logoArmoirie=file_exists($cheminArmoirie)?'data:image/png;base64,'.base64_encode(file_get_contents($cheminArmoirie)):'';
?>
<div class="page-border"></div>
<table class="header-table"><tr>
<td class="header-left">
<div class="ministere">MINISTÈRE DE L'ÉDUCATION NATIONALE<br>DE L'ALPHABÉTISATION ET DE<br>L'ENSEIGNEMENT TECHNIQUE</div>
<div class="separator"></div>
<div class="drenaet">DRENAET DE DUÉKOUÉ</div>
<?php if($logoCmak):?><img src="<?= $logoCmak ?>" class="logo-cmak"><?php endif;?>
</td>
<td class="header-right">
<div class="republique">RÉPUBLIQUE DE CÔTE D'IVOIRE</div>
<div class="separator"></div>
<?php if($logoArmoirie):?><img src="<?= $logoArmoirie ?>" class="logo-armoirie"><?php endif;?>
<div class="devise">Union - Discipline - Travail</div>
<div class="separator"></div>
</td>
</tr></table>

<div class="title"><span>AUTORISATION D'ABSENCE</span></div>

<div class="content">
<p>Je soussigné, <strong>Monsieur TALA RAYMOND</strong>, Directeur des Études (DE), autorise par la présente :</p>

<div class="info">
<div class="info-line"><span class="label">Nom et Prénoms :</span><span class="value"><?= htmlspecialchars($nom ?? '',ENT_QUOTES,'UTF-8'); ?></span></div>
<div class="info-line"><span class="label">Fonction :</span><span class="value"><?= htmlspecialchars($fonction ?? '',ENT_QUOTES,'UTF-8'); ?></span></div>
<div class="info-line"><span class="label">Matricule :</span><span class="value"><?= htmlspecialchars($matricule ?? '',ENT_QUOTES,'UTF-8'); ?></span></div>
</div>

<p>à s'absenter temporairement de son poste pour des raisons personnelles.</p>

<p>La présente autorisation est délivrée à l'intéressé(e) pour servir et valoir ce que de droit.</p>
</div>

<div class="signature">
Fait à <strong>Facobly</strong>, le <strong><?= htmlspecialchars($date ?? '',ENT_QUOTES,'UTF-8'); ?></strong>
<br><br>
<strong>Le Directeur des Études</strong>
<br><br><br><br><br>
<span class="signature-name">TALA RAYMOND</span>
</div>

<div class="footer">
Collège Privé Ahmadou Kourouma de Facobly — BP 06 Facobly<br>
Tél. : 07 07 35 67 46 / 05 06 67 66 33
</div>

</body>
</html>