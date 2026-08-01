<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">

<style>

@page{
    size: A4 portrait;
    margin: 18px;
}

*{
    box-sizing: border-box;
}

body{
    margin: 0;
    padding: 25px 38px 35px 38px;
    font-family: "Times New Roman", serif;
    font-size: 15px;
    line-height: 1.4;
    color: #000;
}

/* Bordure orange */
.page-border{
    position: fixed;
    top: 7px;
    right: 7px;
    bottom: 7px;
    left: 7px;
    border: 3px solid #ef7d18;
}

/* En-tête stable pour Dompdf */
.header-table{
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.header-table td{
    width: 50%;
    padding: 0;
    vertical-align: top;
    text-align: center;
    font-size: 13px;
    line-height: 1.3;
}

.header-left{
    padding-right: 18px;
}

.header-right{
    padding-left: 18px;
}

.ministere{
    height: 52px;
    font-weight: bold;
}

.republique{
    margin-top: 2px;
    font-weight: bold;
}

.separator{
    width: 105px;
    margin: 7px auto;
    border-top: 1px solid #000;
}

.drenaet{
    margin-top: 5px;
    font-weight: bold;
}

/* Logo CMAK placé sous DRENAET */
.logo-cmak{
    display: block;
    width: 92px;
    height: 92px;
    margin: 9px auto 0 auto;
    object-fit: contain;
}

/* Armoiries placées entre République et devise */
.logo-armoirie{
    display: block;
    width: 88px;
    height: 88px;
    margin: 7px auto 5px auto;
    object-fit: contain;
}

.devise{
    font-weight: bold;
    margin-top: 2px;
}

/* Titre */
.title{
    margin-top: 20px;
    margin-bottom: 23px;
    text-align: center;
}

.title span{
    display: inline-block;
    padding: 7px 25px;
    border: 2px solid #000;
    border-radius: 4px;
    font-size: 21px;
    font-weight: bold;
    letter-spacing: 0.8px;
}

/* Contenu */
.content{
    width: 100%;
    text-align: justify;
}

.content p{
    margin: 8px 0;
}

/* Informations du personnel */
.info{
    width: 88%;
    margin: 14px auto;
}

.info-line{
    margin-bottom: 8px;
}

.label{
    display: inline-block;
    width: 175px;
    font-weight: bold;
}

.value{
    font-weight: bold;
}

/* Signature */
.signature{
    width: 46%;
    margin-top: 14px;
    margin-left: 54%;
    text-align: center;
    line-height: 1.5;
}

.signature-name{
    display: inline-block;
    min-width: 185px;
    padding-top: 5px;
    font-weight: bold;
}

/* Pied de page */
.footer{
    position: fixed;
    right: 45px;
    bottom: 14px;
    left: 45px;
    padding-top: 5px;
    border-top: 1px solid #ef7d18;
    text-align: center;
    font-size: 10px;
    line-height: 1.25;
}

</style>

</head>

<body>

<?php

/* Chemins exacts des logos */
$cheminLogoCmak = 'C:/wamp64/www/app_exp/uploads/logo/cmak.png';
$cheminArmoirie = 'C:/wamp64/www/app_exp/uploads/logo/armoirie.png';

/* Valeurs vides par défaut */
$logoCmak = '';
$logoArmoirie = '';

/* Conversion du logo CMAK en Base64 */
if (file_exists($cheminLogoCmak) && is_readable($cheminLogoCmak)) {
    $logoCmak = 'data:image/png;base64,' .
        base64_encode(file_get_contents($cheminLogoCmak));
}

/* Conversion des armoiries en Base64 */
if (file_exists($cheminArmoirie) && is_readable($cheminArmoirie)) {
    $logoArmoirie = 'data:image/png;base64,' .
        base64_encode(file_get_contents($cheminArmoirie));
}

?>

<div class="page-border"></div>

<table class="header-table">

    <tr>

        <td class="header-left">

            <div class="ministere">
                MINISTÈRE DE L'ÉDUCATION NATIONALE<br>
                DE L'ALPHABÉTISATION ET DE<br>
                L'ENSEIGNEMENT TECHNIQUE
            </div>

            <div class="separator"></div>

            <div class="drenaet">
                DRENAET DE DUÉKOUÉ
            </div>

            <?php if (!empty($logoCmak)) : ?>
                <img
                    src="<?php echo $logoCmak; ?>"
                    class="logo-cmak"
                    alt="Logo CMAK"
                >
            <?php endif; ?>

        </td>

        <td class="header-right">

            <div class="republique">
                RÉPUBLIQUE DE CÔTE D'IVOIRE
            </div>

            <div class="separator"></div>

            <?php if (!empty($logoArmoirie)) : ?>
                <img
                    src="<?php echo $logoArmoirie; ?>"
                    class="logo-armoirie"
                    alt="Armoiries de la Côte d'Ivoire"
                >
            <?php endif; ?>

            <div class="devise">
                Union - Discipline - Travail
            </div>

            <div class="separator"></div>

        </td>

    </tr>

</table>

<br><br>

<div class="title">
    <span>ATTESTATION DE TRAVAIL</span>
</div>

<br><br>

<div class="content">

    <p>
        Je soussigné,
        <strong>Monsieur TALA RAYMOND</strong>,
        Directeur des Études (DE), certifie par la présente que :
    </p>

    <div class="info">

        <div class="info-line">
            <span class="label">M./Mme/Mlle :</span>

            <span class="value">
                <?php
                echo htmlspecialchars(
                    $nom ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </span>
        </div>

        <div class="info-line">
            <span class="label">De nationalité :</span>

            <span class="value">
                <?php
                echo htmlspecialchars(
                    $nationalite ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </span>
        </div>

        <div class="info-line">
            <span class="label">N° CNI :</span>

            <span class="value">
                <?php
                echo htmlspecialchars(
                    $cni ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </span>
        </div>

        <div class="info-line">
            <span class="label">Fonction :</span>

            <span class="value">
                <?php
                echo htmlspecialchars(
                    $fonction ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </span>
        </div>

        <div class="info-line">
            <span class="label">Matricule :</span>

            <span class="value">
                <?php
                echo htmlspecialchars(
                    $matricule ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </span>
        </div>

        <div class="info-line">
            <span class="label">Type de contrat :</span>

            <span class="value">
                <?php
                echo htmlspecialchars(
                    $contrat ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </span>
        </div>

        <div class="info-line">
            <span class="label">Date d'embauche :</span>

            <span class="value">
                <?php
                if (!empty($date_embauche)) {
                    echo date(
                        'd/m/Y',
                        strtotime($date_embauche)
                    );
                }
                ?>
            </span>
        </div>

    </div>

    <br>

    <p>
        Exerce effectivement ses fonctions au sein de notre
        établissement depuis cette date.
    </p>

    <br>

    <p>
                  En foi de quoi, la présente attestation lui est délivrée pour servir
        et valoir ce que de droit.
    </p>

</div>

<br>

<div class="signature">

    Fait à <strong>Facobly</strong>, le

    <strong>
        <?php
        echo htmlspecialchars(
            $date ?? '',
            ENT_QUOTES,
            'UTF-8'
        );
        ?>
    </strong>

    <br><br>

    <strong>Le Directeur des Études</strong>

    <br><br><br><br><br>

    <span class="signature-name">
        TALA RAYMOND
    </span>

</div>

<div class="footer">
    Collège Privé Ahmadou Kourouma de Facobly —
    BP 06 Facobly<br>
    Tél. : 07 07 35 67 46 / 05 06 67 66 33
</div>

</body>

</html>