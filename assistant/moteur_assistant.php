<?php

function nettoyerQuestionAssistant(string $question): string
{
    $question = mb_strtolower(trim($question), 'UTF-8');

    $remplacements = [
        'à' => 'a',
        'â' => 'a',
        'ä' => 'a',
        'á' => 'a',
        'ã' => 'a',
        'ç' => 'c',
        'é' => 'e',
        'è' => 'e',
        'ê' => 'e',
        'ë' => 'e',
        'î' => 'i',
        'ï' => 'i',
        'ô' => 'o',
        'ö' => 'o',
        'ù' => 'u',
        'û' => 'u',
        'ü' => 'u',
        'œ' => 'oe',
        '’' => "'"
    ];

    $question = strtr($question, $remplacements);
    $question = preg_replace('/\s+/', ' ', $question);

    return $question;
}

function contientExpressionAssistant(string $question, array $expressions): bool
{
    foreach($expressions as $expression){
        if(str_contains($question, nettoyerQuestionAssistant($expression))){
            return true;
        }
    }

    return false;
}

function echapperAssistant($valeur): string
{
    return htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8');
}

function montantAssistant($montant): string
{
    return number_format((float)$montant, 0, ',', ' ') . ' FCFA';
}

function dateAssistant($date): string
{
    if(empty($date) || $date === '0000-00-00'){
        return '-';
    }

    return date('d/m/Y', strtotime($date));
}

function carteReponseAssistant(
    string $titre,
    string $contenu,
    string $icone = 'bi-info-circle-fill',
    string $couleur = 'primary'
): string {
    return '
        <div class="assistant-result-card">
            <div class="assistant-result-title text-' . echapperAssistant($couleur) . '">
                <i class="bi ' . echapperAssistant($icone) . ' me-2"></i>
                ' . echapperAssistant($titre) . '
            </div>

            <div class="assistant-result-content mt-3">
                ' . $contenu . '
            </div>
        </div>
    ';
}

function listePersonnelAssistant(array $personnels, string $messageVide): string
{
    if(empty($personnels)){
        return '
            <div class="alert alert-success mb-0">
                <i class="bi bi-check-circle-fill me-2"></i>
                ' . echapperAssistant($messageVide) . '
            </div>
        ';
    }

    $html = '<div class="list-group list-group-flush">';

    foreach($personnels as $personnel){
        $nom = trim(
            ($personnel['nom'] ?? '') . ' ' .
            ($personnel['prenom'] ?? '')
        );

        $fonction = $personnel['fonction'] ?? '';
        $matricule = $personnel['matricule'] ?? '';

        $html .= '
            <div class="list-group-item px-0">
                <div class="fw-bold">
                    <i class="bi bi-person-fill me-2 text-primary"></i>
                    ' . echapperAssistant($nom) . '
                </div>
        ';

        if($fonction !== ''){
            $html .= '
                <small class="text-muted">
                    ' . echapperAssistant($fonction) . '
                </small>
            ';
        }

        if($matricule !== ''){
            $html .= '
                <small class="text-muted ms-2">
                    · Matricule : ' . echapperAssistant($matricule) . '
                </small>
            ';
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    $html .= '
        <div class="mt-3 fw-bold text-primary">
            Total : ' . count($personnels) . '
        </div>
    ';

    return $html;
}

function analyserQuestionAssistant(PDO $pdo, string $question): string
{
    $q = nettoyerQuestionAssistant($question);

    $moisActuel = (int)date('m');
    $anneeActuelle = (int)date('Y');
    $aujourdhui = date('Y-m-d');

    /*
    |--------------------------------------------------------------------------
    | SALAIRES NON PAYÉS
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "qui n'a pas ete paye",
        "qui n'est pas paye",
        "salaires non payes",
        "salaire non paye",
        "pas encore paye",
        "non paye"
    ])){
        $req = $pdo->prepare("
            SELECT
                p.nom,
                p.prenom,
                p.fonction,
                p.matricule,
                s.salaire_net,
                s.statut
            FROM salaires s
            INNER JOIN personnels p
                ON p.id = s.personnel_id
            WHERE s.mois = ?
            AND s.annee = ?
            AND s.statut IN ('non_paye', 'partiellement_paye')
            ORDER BY p.nom ASC, p.prenom ASC
        ");

        $req->execute([$moisActuel, $anneeActuelle]);
        $resultats = $req->fetchAll();

        if(empty($resultats)){
            return carteReponseAssistant(
                'Salaires du mois',
                '
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Aucun salaire enregistré n’est en attente de paiement.
                    </div>
                ',
                'bi-cash-coin',
                'success'
            );
        }

        $html = '<div class="list-group list-group-flush">';

        foreach($resultats as $r){
            $statut = $r['statut'] === 'partiellement_paye'
                ? 'Partiellement payé'
                : 'Non payé';

            $html .= '
                <div class="list-group-item px-0">
                    <div class="fw-bold">
                        ' . echapperAssistant(
                            trim(($r['nom'] ?? '') . ' ' . ($r['prenom'] ?? ''))
                        ) . '
                    </div>

                    <div class="small text-muted mt-1">
                        ' . echapperAssistant($statut) . '
                        · ' . montantAssistant($r['salaire_net'] ?? 0) . '
                    </div>
                </div>
            ';
        }

        $html .= '</div>';

        $html .= '
            <div class="mt-3 fw-bold text-danger">
                Total concerné : ' . count($resultats) . '
            </div>
        ';

        return carteReponseAssistant(
            'Salaires en attente',
            $html,
            'bi-exclamation-circle-fill',
            'danger'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ABSENTS AUJOURD'HUI
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "qui est absent aujourd'hui",
        "qui sont absents aujourd'hui",
        "absents aujourd'hui",
        "absence aujourd'hui"
    ])){
        $req = $pdo->prepare("
            SELECT
                p.nom,
                p.prenom,
                p.fonction,
                p.matricule
            FROM personnels p
            LEFT JOIN presences pr
                ON pr.personnel_id = p.id
                AND pr.date_presence = ?
            WHERE p.statut = 'actif'
            AND p.fonction <> 'PROFESSEUR'
            AND (
                pr.id IS NULL
                OR LOWER(pr.statut) = 'absent'
            )
            ORDER BY p.nom ASC, p.prenom ASC
        ");

        $req->execute([$aujourdhui]);
        $personnels = $req->fetchAll();

        $contenu = listePersonnelAssistant(
            $personnels,
            "Aucun personnel administratif absent aujourd'hui."
        );

        return carteReponseAssistant(
            "Absences du " . dateAssistant($aujourdhui),
            $contenu,
            'bi-calendar-x-fill',
            empty($personnels) ? 'success' : 'danger'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERSONNELS SANS DIPLÔME
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "qui n'a pas de diplome",
        "sans diplome",
        "diplomes manquants",
        "personnel sans diplome"
    ])){
        $req = $pdo->query("
            SELECT nom, prenom, fonction, matricule
            FROM personnels
            WHERE statut = 'actif'
            AND (
                diplome_pdf IS NULL
                OR diplome_pdf = ''
            )
            ORDER BY nom ASC, prenom ASC
        ");

        $personnels = $req->fetchAll();

        return carteReponseAssistant(
            'Diplômes manquants',
            listePersonnelAssistant(
                $personnels,
                'Tous les personnels actifs ont un diplôme PDF enregistré.'
            ),
            'bi-mortarboard-fill',
            empty($personnels) ? 'success' : 'warning'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERSONNELS SANS CONTRAT
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "qui n'a pas de contrat",
        "sans contrat",
        "contrats manquants",
        "personnel sans contrat"
    ])){
        $req = $pdo->query("
            SELECT nom, prenom, fonction, matricule
            FROM personnels
            WHERE statut = 'actif'
            AND (
                contrat_pdf IS NULL
                OR contrat_pdf = ''
            )
            ORDER BY nom ASC, prenom ASC
        ");

        $personnels = $req->fetchAll();

        return carteReponseAssistant(
            'Contrats manquants',
            listePersonnelAssistant(
                $personnels,
                'Tous les personnels actifs ont un contrat PDF enregistré.'
            ),
            'bi-file-earmark-x-fill',
            empty($personnels) ? 'success' : 'warning'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERSONNELS SANS PHOTO
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "qui n'a pas de photo",
        "sans photo",
        "photos manquantes",
        "personnel sans photo"
    ])){
        $req = $pdo->query("
            SELECT nom, prenom, fonction, matricule
            FROM personnels
            WHERE statut = 'actif'
            AND (
                photo IS NULL
                OR photo = ''
            )
            ORDER BY nom ASC, prenom ASC
        ");

        $personnels = $req->fetchAll();

        return carteReponseAssistant(
            'Photos manquantes',
            listePersonnelAssistant(
                $personnels,
                'Tous les personnels actifs ont une photo enregistrée.'
            ),
            'bi-person-bounding-box',
            empty($personnels) ? 'success' : 'warning'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONTRATS QUI EXPIRENT CE MOIS
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "contrats expirent ce mois",
        "contrats qui expirent",
        "contrats bientot expires",
        "contrats a renouveler",
        "expiration des contrats"
    ])){
        $req = $pdo->prepare("
            SELECT
                nom,
                prenom,
                fonction,
                matricule,
                date_fin_contrat
            FROM personnels
            WHERE statut = 'actif'
            AND date_fin_contrat IS NOT NULL
            AND date_fin_contrat != '0000-00-00'
            AND MONTH(date_fin_contrat) = ?
            AND YEAR(date_fin_contrat) = ?
            ORDER BY date_fin_contrat ASC
        ");

        $req->execute([$moisActuel, $anneeActuelle]);
        $resultats = $req->fetchAll();

        if(empty($resultats)){
            return carteReponseAssistant(
                'Contrats du mois',
                '
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        Aucun contrat actif n’expire ce mois-ci.
                    </div>
                ',
                'bi-calendar-check-fill',
                'success'
            );
        }

        $html = '<div class="list-group list-group-flush">';

        foreach($resultats as $r){
            $html .= '
                <div class="list-group-item px-0">
                    <div class="fw-bold">
                        ' . echapperAssistant(
                            trim(($r['nom'] ?? '') . ' ' . ($r['prenom'] ?? ''))
                        ) . '
                    </div>

                    <div class="small text-muted mt-1">
                        Expiration : ' .
                        echapperAssistant(dateAssistant($r['date_fin_contrat'])) .
                        '
                    </div>
                </div>
            ';
        }

        $html .= '</div>';

        return carteReponseAssistant(
            'Contrats à renouveler',
            $html,
            'bi-calendar-event-fill',
            'warning'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONTRATS DÉJÀ EXPIRÉS
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "contrats expires",
        "contrat expire",
        "qui a un contrat expire"
    ])){
        $req = $pdo->query("
            SELECT
                nom,
                prenom,
                fonction,
                matricule,
                date_fin_contrat
            FROM personnels
            WHERE statut = 'actif'
            AND date_fin_contrat IS NOT NULL
            AND date_fin_contrat != '0000-00-00'
            AND date_fin_contrat < CURDATE()
            ORDER BY date_fin_contrat ASC
        ");

        $resultats = $req->fetchAll();

        if(empty($resultats)){
            return carteReponseAssistant(
                'Contrats expirés',
                '
                    <div class="alert alert-success mb-0">
                        Aucun personnel actif ne possède un contrat expiré.
                    </div>
                ',
                'bi-check-circle-fill',
                'success'
            );
        }

        $html = '<div class="list-group list-group-flush">';

        foreach($resultats as $r){
            $html .= '
                <div class="list-group-item px-0">
                    <strong>
                        ' . echapperAssistant(
                            trim(($r['nom'] ?? '') . ' ' . ($r['prenom'] ?? ''))
                        ) . '
                    </strong>

                    <div class="small text-danger mt-1">
                        Expiré depuis le ' .
                        echapperAssistant(dateAssistant($r['date_fin_contrat'])) .
                        '
                    </div>
                </div>
            ';
        }

        $html .= '</div>';

        return carteReponseAssistant(
            'Contrats expirés',
            $html,
            'bi-exclamation-octagon-fill',
            'danger'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERSONNELS SUSPENDUS
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "qui est suspendu",
        "personnels suspendus",
        "liste des suspendus",
        "combien de suspendus"
    ])){
        $req = $pdo->query("
            SELECT nom, prenom, fonction, matricule
            FROM personnels
            WHERE statut = 'suspendu'
            ORDER BY nom ASC, prenom ASC
        ");

        $personnels = $req->fetchAll();

        return carteReponseAssistant(
            'Personnels suspendus',
            listePersonnelAssistant(
                $personnels,
                'Aucun personnel suspendu.'
            ),
            'bi-person-x-fill',
            empty($personnels) ? 'success' : 'danger'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERSONNELS ACTIFS
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "combien de personnels actifs",
        "nombre de personnels actifs",
        "combien d'actifs",
        "effectif actif"
    ])){
        $total = (int)$pdo->query("
            SELECT COUNT(*)
            FROM personnels
            WHERE statut = 'actif'
        ")->fetchColumn();

        return carteReponseAssistant(
            'Personnel actif',
            '
                <div class="display-6 fw-bold text-primary">
                    ' . $total . '
                </div>

                <div class="text-muted mt-2">
                    personnel(s) actif(s) enregistré(s).
                </div>
            ',
            'bi-people-fill',
            'primary'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MASSE SALARIALE
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "masse salariale",
        "total des salaires",
        "montant des salaires",
        "combien coutent les salaires"
    ])){
        $req = $pdo->prepare("
            SELECT COALESCE(SUM(salaire_net), 0)
            FROM salaires
            WHERE mois = ?
            AND annee = ?
        ");

        $req->execute([$moisActuel, $anneeActuelle]);
        $total = (float)$req->fetchColumn();

        return carteReponseAssistant(
            'Masse salariale du mois',
            '
                <div class="display-6 fw-bold text-primary">
                    ' . montantAssistant($total) . '
                </div>

                <div class="text-muted mt-2">
                    Montant total des salaires enregistrés ce mois-ci.
                </div>
            ',
            'bi-cash-stack',
            'primary'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AVANCES DU MOIS
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "qui a recu une avance",
        "avances du mois",
        "liste des avances",
        "personnels avec avance"
    ])){
        $req = $pdo->prepare("
            SELECT
                p.nom,
                p.prenom,
                p.fonction,
                p.matricule,
                a.montant,
                a.date_avance,
                a.statut
            FROM avances a
            INNER JOIN personnels p
                ON p.id = a.personnel_id
            WHERE MONTH(a.date_avance) = ?
            AND YEAR(a.date_avance) = ?
            ORDER BY a.date_avance DESC
        ");

        $req->execute([$moisActuel, $anneeActuelle]);
        $resultats = $req->fetchAll();

        if(empty($resultats)){
            return carteReponseAssistant(
                'Avances du mois',
                '
                    <div class="alert alert-success mb-0">
                        Aucune avance enregistrée ce mois-ci.
                    </div>
                ',
                'bi-wallet2',
                'success'
            );
        }

        $html = '<div class="list-group list-group-flush">';

        foreach($resultats as $r){
            $html .= '
                <div class="list-group-item px-0">
                    <strong>
                        ' . echapperAssistant(
                            trim(($r['nom'] ?? '') . ' ' . ($r['prenom'] ?? ''))
                        ) . '
                    </strong>

                    <div class="small text-muted mt-1">
                        ' . montantAssistant($r['montant'] ?? 0) . '
                        · ' . echapperAssistant(dateAssistant($r['date_avance'])) . '
                    </div>
                </div>
            ';
        }

        $html .= '</div>';

        return carteReponseAssistant(
            'Avances du mois',
            $html,
            'bi-wallet2',
            'primary'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RETENUES DU MOIS
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "qui a une retenue",
        "retenues du mois",
        "liste des retenues",
        "personnels avec retenue"
    ])){
        $req = $pdo->prepare("
            SELECT
                p.nom,
                p.prenom,
                p.fonction,
                p.matricule,
                r.montant,
                r.date_retenue,
                r.motif
            FROM retenues r
            INNER JOIN personnels p
                ON p.id = r.personnel_id
            WHERE MONTH(r.date_retenue) = ?
            AND YEAR(r.date_retenue) = ?
            ORDER BY r.date_retenue DESC
        ");

        $req->execute([$moisActuel, $anneeActuelle]);
        $resultats = $req->fetchAll();

        if(empty($resultats)){
            return carteReponseAssistant(
                'Retenues du mois',
                '
                    <div class="alert alert-success mb-0">
                        Aucune retenue enregistrée ce mois-ci.
                    </div>
                ',
                'bi-dash-circle-fill',
                'success'
            );
        }

        $html = '<div class="list-group list-group-flush">';

        foreach($resultats as $r){
            $html .= '
                <div class="list-group-item px-0">
                    <strong>
                        ' . echapperAssistant(
                            trim(($r['nom'] ?? '') . ' ' . ($r['prenom'] ?? ''))
                        ) . '
                    </strong>

                    <div class="small text-muted mt-1">
                        ' . montantAssistant($r['montant'] ?? 0) . '
                        · ' . echapperAssistant($r['motif'] ?? 'Sans motif') . '
                    </div>
                </div>
            ';
        }

        $html .= '</div>';

        return carteReponseAssistant(
            'Retenues du mois',
            $html,
            'bi-dash-circle-fill',
            'warning'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DOCUMENTS GÉNÉRÉS AUJOURD'HUI
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "documents generes aujourd'hui",
        "quels documents ont ete generes",
        "documents du jour",
        "documents generes"
    ])){
        $req = $pdo->prepare("
            SELECT
                dg.type_document,
                dg.date_generation,
                p.nom,
                p.prenom
            FROM documents_generes dg
            INNER JOIN personnels p
                ON p.id = dg.personnel_id
            WHERE DATE(dg.date_generation) = ?
            ORDER BY dg.date_generation DESC
        ");

        $req->execute([$aujourdhui]);
        $documents = $req->fetchAll();

        if(empty($documents)){
            return carteReponseAssistant(
                'Documents générés',
                '
                    <div class="alert alert-info mb-0">
                        Aucun document n’a été généré aujourd’hui.
                    </div>
                ',
                'bi-file-earmark-text-fill',
                'primary'
            );
        }

        $html = '<div class="list-group list-group-flush">';

        foreach($documents as $doc){
            $html .= '
                <div class="list-group-item px-0">
                    <strong>
                        ' . echapperAssistant($doc['type_document'] ?? '') . '
                    </strong>

                    <div class="small text-muted mt-1">
                        ' . echapperAssistant(
                            trim(($doc['nom'] ?? '') . ' ' . ($doc['prenom'] ?? ''))
                        ) . '
                    </div>
                </div>
            ';
        }

        $html .= '</div>';

        return carteReponseAssistant(
            'Documents générés aujourd’hui',
            $html,
            'bi-file-earmark-pdf-fill',
            'primary'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PROFESSEURS SANS EMPLOI DU TEMPS
    |--------------------------------------------------------------------------
    */

    if(contientExpressionAssistant($q, [
        "professeur sans emploi du temps",
        "professeurs sans emploi du temps",
        "qui n'a pas d'emploi du temps"
    ])){
        $req = $pdo->query("
            SELECT
                p.nom,
                p.prenom,
                p.fonction,
                p.matricule
            FROM personnels p
            LEFT JOIN emplois_temps_professeurs e
                ON e.personnel_id = p.id
            WHERE p.statut = 'actif'
            AND p.fonction = 'PROFESSEUR'
            GROUP BY p.id, p.nom, p.prenom, p.fonction, p.matricule
            HAVING COUNT(e.id) = 0
            ORDER BY p.nom ASC
        ");

        $personnels = $req->fetchAll();

        return carteReponseAssistant(
            'Professeurs sans emploi du temps',
            listePersonnelAssistant(
                $personnels,
                'Tous les professeurs actifs ont un emploi du temps.'
            ),
            'bi-calendar2-x-fill',
            empty($personnels) ? 'success' : 'warning'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUESTION NON RECONNUE
    |--------------------------------------------------------------------------
    */

    return carteReponseAssistant(
        'Question non reconnue',
        '
            <div class="alert alert-info mb-3">
                Je n’ai pas encore compris cette formulation.
            </div>

            <div class="fw-bold mb-2">
                Essayez par exemple :
            </div>

            <ul class="mb-0 ps-3">
                <li>Qui n’a pas été payé ce mois-ci ?</li>
                <li>Qui est absent aujourd’hui ?</li>
                <li>Quels contrats expirent ce mois-ci ?</li>
                <li>Qui n’a pas de diplôme ?</li>
                <li>Quelle est la masse salariale du mois ?</li>
                <li>Qui a reçu une avance ?</li>
            </ul>
        ',
        'bi-question-circle-fill',
        'primary'
    );
}