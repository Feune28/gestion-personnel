<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/*
|--------------------------------------------------------------------------
| PROFIL DE L'ASSISTANT
|--------------------------------------------------------------------------
| La page qui inclut ce fichier doit définir :
| $assistantProfil = 'DE';
| ou
| $assistantProfil = 'DAF';
*/

$assistantProfil = strtoupper(trim($assistantProfil ?? ''));

if(!in_array($assistantProfil, ['DE', 'DAF'], true)){
    $assistantProfil = 'DE';
}

$assistantRole = $assistantProfil === 'DAF'
    ? 'Directeur Administratif et Financier'
    : 'Directeur des Études';

?>

<link rel="stylesheet" href="assistant/assistant.css">

<button
    type="button"
    id="assistantFloatingBtn"
    class="assistant-floating-btn"
    data-bs-toggle="offcanvas"
    data-bs-target="#assistantOffcanvas"
    aria-controls="assistantOffcanvas"
    aria-label="Ouvrir l’assistant intelligent"
    title="Assistant intelligent"
>
    <i class="bi bi-cpu-fill"></i>
    <span>IA</span>
</button>

<div
    class="offcanvas offcanvas-end assistant-offcanvas"
    tabindex="-1"
    id="assistantOffcanvas"
    aria-labelledby="assistantOffcanvasLabel"
>
    <div class="offcanvas-header assistant-header">

        <div class="assistant-title-wrap">

            <div class="assistant-logo">
                <i class="bi bi-cpu-fill"></i>
            </div>

            <div>
                <h5 class="offcanvas-title" id="assistantOffcanvasLabel">
                    Assistant intelligent
                </h5>

                <small>
                    CMAK Facobly · Espace <?= htmlspecialchars($assistantProfil, ENT_QUOTES, 'UTF-8') ?>
                </small>
            </div>

        </div>

        <button
            type="button"
            class="btn-close btn-close-white"
            data-bs-dismiss="offcanvas"
            aria-label="Fermer"
        ></button>

    </div>

    <div class="offcanvas-body assistant-body">

        <div class="assistant-welcome">

            <div class="assistant-welcome-icon">
                <i class="bi bi-stars"></i>
            </div>

            <div>
                <strong>
                    Bonjour <?= htmlspecialchars($assistantRole, ENT_QUOTES, 'UTF-8') ?>
                </strong>

                <p>
                    <?php if($assistantProfil === 'DAF'): ?>
                        Posez une question sur les salaires, paiements, avances,
                        retenues, impayés ou autres données financières.
                    <?php else: ?>
                        Posez une question sur le personnel, les présences,
                        contrats, documents ou emplois du temps.
                    <?php endif; ?>
                </p>
            </div>

        </div>

        <form id="assistantForm" class="assistant-form">

            <input
                type="hidden"
                id="assistantProfil"
                name="profil"
                value="<?= htmlspecialchars($assistantProfil, ENT_QUOTES, 'UTF-8') ?>"
            >

            <label for="assistantQuestion" class="assistant-label">
                Votre question
            </label>

            <div class="assistant-input-group">

                <textarea
                    id="assistantQuestion"
                    name="question"
                    rows="3"
                    placeholder="<?= $assistantProfil === 'DAF'
                        ? "Exemple : Qui n'a pas été payé ce mois-ci ?"
                        : "Exemple : Qui est absent aujourd'hui ?" ?>"
                    required
                ></textarea>

                <button
                    type="submit"
                    id="assistantSubmit"
                    title="Envoyer la question"
                >
                    <i class="bi bi-send-fill"></i>
                </button>

            </div>

        </form>

        <div class="assistant-suggestions">

            <div class="assistant-section-title">
                <i class="bi bi-lightbulb-fill"></i>
                Suggestions
            </div>

            <div class="assistant-suggestion-list">

                <?php if($assistantProfil === 'DE'): ?>

                    <button
                        type="button"
                        class="assistant-suggestion"
                        data-question="Qui est absent aujourd'hui ?"
                    >
                        Qui est absent aujourd'hui ?
                    </button>

                    <button
                        type="button"
                        class="assistant-suggestion"
                        data-question="Quels contrats expirent ce mois-ci ?"
                    >
                        Quels contrats expirent ce mois ?
                    </button>

                    <button
                        type="button"
                        class="assistant-suggestion"
                        data-question="Qui n'a pas de diplôme ?"
                    >
                        Qui n'a pas de diplôme ?
                    </button>

                    <button
                        type="button"
                        class="assistant-suggestion"
                        data-question="Qui n'a pas de contrat ?"
                    >
                        Qui n'a pas de contrat ?
                    </button>

                    <button
                        type="button"
                        class="assistant-suggestion"
                        data-question="Quels professeurs n'ont pas d'emploi du temps ?"
                    >
                        Professeurs sans emploi du temps
                    </button>

                <?php else: ?>

                    <button
                        type="button"
                        class="assistant-suggestion"
                        data-question="Qui n'a pas été payé ce mois-ci ?"
                    >
                        Qui n'a pas été payé ?
                    </button>

                    <button
                        type="button"
                        class="assistant-suggestion"
                        data-question="Quelle est la masse salariale du mois ?"
                    >
                        Quelle est la masse salariale ?
                    </button>

                    <button
                        type="button"
                        class="assistant-suggestion"
                        data-question="Qui a reçu une avance ce mois-ci ?"
                    >
                        Qui a reçu une avance ?
                    </button>

                    <button
                        type="button"
                        class="assistant-suggestion"
                        data-question="Qui a une retenue ce mois-ci ?"
                    >
                        Qui a une retenue ?
                    </button>

                    <button
                        type="button"
                        class="assistant-suggestion"
                        data-question="Quels salaires sont partiellement payés ?"
                    >
                        Paiements partiels
                    </button>

                <?php endif; ?>

            </div>

        </div>

        <div class="assistant-response-section">

            <div class="assistant-section-title">
                <i class="bi bi-chat-left-text-fill"></i>
                Conversation
            </div>

            <div id="assistantResponse" class="assistant-response">

                <div class="assistant-empty-response">
                    <i class="bi bi-chat-square-dots"></i>

                    <p>
                        Posez une question pour commencer la discussion.
                    </p>
                </div>

            </div>

        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const assistantButton = document.getElementById('assistantFloatingBtn');
    const assistantPanel = document.getElementById('assistantOffcanvas');

    if(!assistantButton || !assistantPanel){
        return;
    }

    assistantButton.addEventListener('click', function () {
        if(typeof bootstrap === 'undefined'){
            console.error('Bootstrap JavaScript n’est pas chargé.');
            return;
        }

        const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(assistantPanel);
        offcanvas.show();
    });
});
</script>

<script src="assistant/assistant.js"></script>