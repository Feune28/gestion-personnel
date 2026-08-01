document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('assistantForm');
    const questionInput = document.getElementById('assistantQuestion');
    const responseBox = document.getElementById('assistantResponse');
    const submitButton = document.getElementById('assistantSubmit');
    const profilInput = document.getElementById('assistantProfil');
    const suggestions = document.querySelectorAll('.assistant-suggestion');

    if (!form || !questionInput || !responseBox) {
        return;
    }

    let premiereQuestion = true;

    function supprimerMessageVide() {
        if (premiereQuestion) {
            responseBox.innerHTML = '';
            premiereQuestion = false;
        }
    }

    function ajouterQuestion(question) {
        supprimerMessageVide();

        const bloc = document.createElement('div');
        bloc.className = 'assistant-message assistant-message-user';

        bloc.innerHTML = `
            <div class="assistant-message-label">
                Vous
            </div>

            <div class="assistant-message-bubble">
                ${echapperHtml(question)}
            </div>
        `;

        responseBox.appendChild(bloc);
        responseBox.scrollTop = responseBox.scrollHeight;
    }

    function ajouterChargement() {
        const bloc = document.createElement('div');
        bloc.className = 'assistant-message assistant-message-ai';
        bloc.id = 'assistantLoading';

        bloc.innerHTML = `
            <div class="assistant-message-label">
                Assistant CMAK
            </div>

            <div class="assistant-message-bubble">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Analyse en cours...
            </div>
        `;

        responseBox.appendChild(bloc);
        responseBox.scrollTop = responseBox.scrollHeight;
    }

    function supprimerChargement() {
        document.getElementById('assistantLoading')?.remove();
    }

    function ajouterReponse(html) {
        supprimerChargement();

        const bloc = document.createElement('div');
        bloc.className = 'assistant-message assistant-message-ai';

        bloc.innerHTML = `
            <div class="assistant-message-label">
                Assistant CMAK
            </div>

            <div class="assistant-message-bubble">
                ${html}
            </div>
        `;

        responseBox.appendChild(bloc);
        responseBox.scrollTop = responseBox.scrollHeight;
    }

    function ajouterErreur(message) {
        supprimerChargement();

        ajouterReponse(`
            <div class="alert alert-danger mb-0">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                ${echapperHtml(message)}
            </div>
        `);
    }

    function echapperHtml(texte) {
        const element = document.createElement('div');
        element.textContent = texte;
        return element.innerHTML;
    }

    async function envoyerQuestion(question) {
        const questionNettoyee = question.trim();

        if (questionNettoyee === '') {
            return;
        }

        ajouterQuestion(questionNettoyee);
        ajouterChargement();

        questionInput.value = '';

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const formData = new FormData();

            formData.append('question', questionNettoyee);
            formData.append('profil', profilInput?.value || '');

            const response = await fetch('assistant/traitement_assistant.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const texte = await response.text();

            if (!response.ok) {
                throw new Error(texte || `Erreur HTTP ${response.status}`);
            }

            ajouterReponse(texte);

        } catch (error) {
            console.error(error);

            ajouterErreur(
                "Impossible de contacter l'assistant."
            );
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }

            questionInput.focus();
        }
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        envoyerQuestion(questionInput.value);
    });

    suggestions.forEach(function (button) {
        button.addEventListener('click', function () {
            const question =
                button.dataset.question ||
                button.textContent.trim();

            envoyerQuestion(question);
        });
    });

    questionInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });
});