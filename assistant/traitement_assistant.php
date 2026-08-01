<?php

session_start();

header('Content-Type: text/html; charset=UTF-8');

if(!isset($_SESSION['admin'])){
    http_response_code(401);

    echo '
        <div class="alert alert-danger mb-0">
            <i class="bi bi-shield-lock-fill me-2"></i>
            Votre session a expiré. Veuillez vous reconnecter.
        </div>
    ';

    exit();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);

    echo '
        <div class="alert alert-warning mb-0">
            Méthode non autorisée.
        </div>
    ';

    exit();
}

$question = trim($_POST['question'] ?? '');

if($question === ''){
    http_response_code(422);

    echo '
        <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            Veuillez saisir une question.
        </div>
    ';

    exit();
}

require dirname(__DIR__) . '/connexion.php';
require __DIR__ . '/moteur_assistant.php';

try{
    $reponse = analyserQuestionAssistant($pdo, $question);

    echo $reponse;
}catch(Throwable $e){
    http_response_code(500);

    echo '
        <div class="alert alert-danger mb-0">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Une erreur est survenue pendant l’analyse de la question.
        </div>
    ';
}