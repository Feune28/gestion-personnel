<?php

/*
|--------------------------------------------------------------------------
| SÉCURITÉ COMMUNE DE L’ESPACE PERSONNEL
|--------------------------------------------------------------------------
| Ce fichier doit être inclus au début de toutes les pages réservées
| au personnel.
|--------------------------------------------------------------------------
*/

if(session_status() === PHP_SESSION_NONE){

    session_start([

        'cookie_httponly' => true,
        'cookie_secure'   => false, // mettre true après passage en HTTPS
        'cookie_samesite' => 'Strict'

    ]);

}

require_once 'connexion.php';

date_default_timezone_set('Africa/Abidjan');

/*
|--------------------------------------------------------------------------
| VÉRIFICATION DE LA SESSION
|--------------------------------------------------------------------------
*/

if(
    !isset($_SESSION['personnel'])
    ||
    !isset($_SESSION['compte_personnel_id'])
){

    header('Location: login_personnel.php');
    exit();

}

$personnel_id = (int)($_SESSION['personnel'] ?? 0);
$compte_id    = (int)($_SESSION['compte_personnel_id'] ?? 0);

if($personnel_id <= 0 || $compte_id <= 0){

    session_unset();
    session_destroy();

    header('Location: login_personnel.php');
    exit();

}

/*
|--------------------------------------------------------------------------
| EXPIRATION DE LA SESSION APRÈS 30 MINUTES D’INACTIVITÉ
|--------------------------------------------------------------------------
*/

$dureeInactivite = 1800;

if(
    isset($_SESSION['last_activity'])
    &&
    (time() - (int)$_SESSION['last_activity']) > $dureeInactivite
){

    session_unset();
    session_destroy();

    header('Location: login_personnel.php?session_expiree=1');
    exit();

}

$_SESSION['last_activity'] = time();

/*
|--------------------------------------------------------------------------
| CONTRÔLE DU COMPTE ET DU DOSSIER PERSONNEL
|--------------------------------------------------------------------------
*/

$verification = $pdo->prepare("

    SELECT

        cp.id AS compte_id,
        cp.personnel_id,
        cp.identifiant,
        cp.email AS email_compte,
        cp.premiere_connexion,
        cp.email_verifie,
        cp.actif AS compte_actif,
        cp.role,

        p.*

    FROM comptes_personnels cp

    INNER JOIN personnels p
        ON p.id = cp.personnel_id

    WHERE cp.id = ?
      AND cp.personnel_id = ?

    LIMIT 1

");

$verification->execute([

    $compte_id,
    $personnel_id

]);

$donneesCompte = $verification->fetch(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| COMPTE OU PERSONNEL INTROUVABLE
|--------------------------------------------------------------------------
*/

if(!$donneesCompte){

    session_unset();
    session_destroy();

    header('Location: login_personnel.php?compte_invalide=1');
    exit();

}

/*
|--------------------------------------------------------------------------
| COMPTE DÉSACTIVÉ
|--------------------------------------------------------------------------
*/

if((int)($donneesCompte['compte_actif'] ?? 0) !== 1){

    session_unset();
    session_destroy();

    header('Location: login_personnel.php?suspendu=1');
    exit();

}

/*
|--------------------------------------------------------------------------
| PERSONNEL SUSPENDU
|--------------------------------------------------------------------------
*/

$statutPersonnel = strtolower(
    trim($donneesCompte['statut'] ?? '')
);

if($statutPersonnel !== 'actif'){

    session_unset();
    session_destroy();

    header('Location: login_personnel.php?suspendu=1');
    exit();

}

/*
|--------------------------------------------------------------------------
| CHANGEMENT OBLIGATOIRE DU MOT DE PASSE
|--------------------------------------------------------------------------
| On évite la redirection infinie lorsque l’utilisateur se trouve déjà
| sur la page de changement du mot de passe.
|--------------------------------------------------------------------------
*/

$pageActuelle = basename($_SERVER['PHP_SELF'] ?? '');

if(
    (int)($donneesCompte['premiere_connexion'] ?? 0) === 1
    &&
    $pageActuelle !== 'changer_mot_de_passe_personnel.php'
){

    header('Location: changer_mot_de_passe_personnel.php');
    exit();

}

/*
|--------------------------------------------------------------------------
| VARIABLES DISPONIBLES DANS LES PAGES
|--------------------------------------------------------------------------
*/

$personnel = $donneesCompte;

$comptePersonnel = [

    'id'                 => (int)$donneesCompte['compte_id'],
    'personnel_id'       => (int)$donneesCompte['personnel_id'],
    'identifiant'        => $donneesCompte['identifiant'] ?? '',
    'email'              => $donneesCompte['email_compte'] ?? '',
    'premiere_connexion' => (int)($donneesCompte['premiere_connexion'] ?? 0),
    'email_verifie'      => (int)($donneesCompte['email_verifie'] ?? 0),
    'actif'              => (int)($donneesCompte['compte_actif'] ?? 0),
    'role'               => $donneesCompte['role'] ?? 'personnel'

];