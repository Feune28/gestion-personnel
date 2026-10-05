# 🏫 Système intelligent de gestion du personnel

**Collège Privé Ahmadou Kourouma (CMAK) — Facobly, Côte d'Ivoire**

## 📌 Présentation

Le **Système intelligent de gestion du personnel** est une application web développée pour le **Collège Privé Ahmadou Kourouma (CMAK)** afin de centraliser et informatiser la gestion administrative du personnel.

L'application permet notamment de gérer le personnel, les présences, les absences, les congés, les salaires, les paiements, les documents administratifs ainsi que les emplois du temps.

Elle intègre également des **tableaux de bord décisionnels et des fonctionnalités d'analyse intelligente** destinés à faciliter le suivi du personnel par les responsables de l'établissement.

## 🎯 Objectifs

* Centraliser les informations relatives au personnel.
* Faciliter le suivi administratif des employés.
* Automatiser certaines opérations de gestion.
* Suivre les présences, absences et congés.
* Gérer les salaires, paiements, avances et retenues.
* Générer des documents administratifs.
* Fournir des tableaux de bord adaptés aux différents profils.
* Exploiter les données du personnel pour produire des indicateurs et rapports intelligents.

## ⚙️ Fonctionnalités principales

### 👥 Gestion du personnel

* Ajout et modification du personnel
* Consultation des profils
* Gestion des informations administratives
* Gestion des documents du personnel
* Gestion des comptes utilisateurs
* Suspension et restauration des comptes

### 🕐 Présences, absences et congés

* Enregistrement des présences
* Consultation des historiques
* Suivi des absences
* Gestion des congés
* Suivi des retards
* Génération de documents liés aux absences

### 💰 Gestion salariale

* Gestion des salaires
* Historique des paiements
* Gestion des avances
* Gestion des retenues
* Suivi des impayés
* Génération des fiches de paie

### 📄 Gestion documentaire

* Gestion des documents administratifs
* Génération d'attestations
* Génération de documents de stage
* Gestion des documents associés au personnel

### 📅 Gestion des emplois du temps

* Création des emplois du temps
* Modification des horaires
* Consultation des emplois du temps du personnel

### 📊 Tableaux de bord et intelligence

Le système propose des tableaux de bord adaptés aux différents profils de l'établissement.

Les modules d'intelligence permettent notamment d'exploiter les données disponibles afin de produire des indicateurs, rapports et informations utiles à la prise de décision.

## 🔐 Gestion des rôles

L'application repose sur une gestion des accès selon les responsabilités des utilisateurs.

Les différents espaces permettent notamment de distinguer :

* Administration générale
* Direction
* Direction administrative et financière
* Personnel
* Superadministration

Chaque profil dispose d'un niveau d'accès adapté à ses responsabilités.

## 🗂️ Architecture du projet

```text
gestion-personnel/
│
├── assets/                    # Ressources CSS, JavaScript, images...
├── includes/                  # Fichiers communs et configurations
├── templates/                 # Modèles de documents et interfaces
├── assistant/                 # Module d'assistance
├── superadmin/                # Espace superadministrateur
├── documents/                 # Documents générés et administratifs
│   └── attestations/
│
├── personnel.php              # Gestion du personnel
├── presences.php              # Gestion des présences
├── salaires.php               # Gestion des salaires
├── paiements.php              # Gestion des paiements
├── fiches_paie.php            # Fiches de paie
├── avances.php                # Gestion des avances
├── retenues.php               # Gestion des retenues
├── impayes.php                # Suivi des impayés
├── emplois_temps.php          # Gestion des emplois du temps
├── dashboard.php              # Tableau de bord principal
├── dashboard_daf.php          # Tableau de bord DAF
├── intelligence_de.php        # Intelligence / analyse pour la DE
├── intelligence_daf.php       # Intelligence / analyse pour la DAF
├── rapport_intelligent_de.php # Rapports intelligents DE
├── rapport_intelligent_daf.php# Rapports intelligents DAF
├── documents.php              # Gestion documentaire
├── notifications.php          # Notifications
├── export_excel.php           # Export des données
└── ...
```

## 🛠️ Technologies utilisées

* **PHP**
* **MySQL**
* **HTML5**
* **CSS3**
* **JavaScript**
* **Bootstrap**
* **Composer**
* **Node.js / npm**
* **Git / GitHub**

## 📈 Vision du projet

L'objectif est de disposer d'un système permettant à l'établissement de passer d'une gestion administrative dispersée à une **gestion centralisée, sécurisée et orientée données**.

Le système peut ainsi servir de base à une évolution future vers des fonctionnalités plus avancées d'analyse des données, d'automatisation et d'intelligence artificielle appliquées à la gestion du personnel.

## 👨‍💻 Auteur

**Tala Talom Feune Jean-Jacques**

Projet académique réalisé dans le cadre de la formation en **Sciences Informatiques**.
