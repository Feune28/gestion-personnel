# 🏫 Système intelligent de gestion du personnel

**Collège Privé Ahmadou Kourouma (CMAK) — Facobly, Côte d'Ivoire**

## 📌 Présentation

Le **Système intelligent de gestion du personnel** est une application web développée pour le **Collège Privé Ahmadou Kourouma (CMAK)** afin de centraliser et informatiser la gestion administrative du personnel.

L'application couvre plusieurs aspects de la gestion des ressources humaines :

* gestion du personnel ;
* présences, absences et congés ;
* emplois du temps ;
* salaires, paiements, avances et retenues ;
* documents administratifs ;
* notifications ;
* tableaux de bord ;
* rapports et analyses intelligentes.

Le système propose également des **espaces adaptés aux différents profils utilisateurs** afin de respecter les responsabilités et les niveaux d'accès de chacun.

---

## 🎯 Objectifs

* Centraliser les informations relatives au personnel.
* Informatiser les processus administratifs.
* Faciliter le suivi des employés.
* Automatiser certaines opérations de gestion.
* Suivre les présences, absences et retards.
* Gérer les congés.
* Gérer les salaires, paiements, avances et retenues.
* Générer des documents administratifs.
* Fournir des tableaux de bord adaptés aux responsables.
* Exploiter les données pour produire des indicateurs et rapports d'aide à la décision.

---

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

### 📊 Tableaux de bord et analyse intelligente

Le système propose plusieurs tableaux de bord adaptés aux responsabilités des utilisateurs.

Les modules d'analyse intelligente permettent d'exploiter les données du système afin de produire :

* des indicateurs de suivi ;
* des statistiques ;
* des rapports intelligents ;
* des informations utiles à la prise de décision.

---

## 🔐 Gestion des rôles et des accès

L'application repose sur une gestion des accès selon les responsabilités des utilisateurs.

Les principaux espaces sont notamment :

* **Administration générale**
* **Direction**
* **Direction Administrative et Financière (DAF)**
* **Personnel**
* **Superadministration**

Chaque espace dispose de fonctionnalités et de droits adaptés au rôle de l'utilisateur.

---

## 🏗️ Architecture du projet

Le projet utilise une **architecture hybride**.

La partie principale de l'application est organisée sous forme de modules PHP indépendants, tandis que le module **Superadministrateur** utilise une architecture **MVC (Model-View-Controller)** afin de séparer les différentes responsabilités de l'application.

### Structure générale

```text
gestion-personnel/
│
├── assets/                         # CSS, JavaScript, images et ressources
├── includes/                       # Fichiers communs et configurations
├── templates/                      # Modèles et ressources d'interface
├── assistant/                      # Module d'assistance
│
├── superadmin/                     # Module Superadministrateur
│   ├── config/                     # Configuration
│   ├── controllers/                # Contrôleurs
│   ├── models/                     # Modèles
│   ├── views/                      # Vues
│   └── ...
│
├── documents/                      # Documents générés et administratifs
│   └── attestations/
│
├── personnel.php                   # Gestion du personnel
├── ajouter.php                     # Ajout d'un membre du personnel
├── modifier.php                    # Modification du personnel
├── voir.php                        # Consultation d'un profil
│
├── presences.php                   # Gestion des présences
├── presences_personnel.php         # Espace présence du personnel
│
├── salaires.php                    # Gestion des salaires
├── paiements.php                   # Gestion des paiements
├── avances.php                     # Gestion des avances
├── retenues.php                    # Gestion des retenues
├── impayes.php                     # Suivi des impayés
├── fiches_paie.php                 # Gestion des fiches de paie
│
├── emplois_temps.php               # Gestion des emplois du temps
├── emploi_personnel.php            # Emploi du temps du personnel
│
├── dashboard.php                   # Tableau de bord principal
├── dashboard_daf.php               # Tableau de bord DAF
│
├── intelligence_de.php             # Analyse intelligente pour la DE
├── intelligence_daf.php            # Analyse intelligente pour la DAF
├── rapport_intelligent_de.php      # Rapport intelligent DE
├── rapport_intelligent_daf.php     # Rapport intelligent DAF
│
├── documents.php                   # Gestion documentaire
├── notifications.php               # Notifications
├── export_excel.php                # Export des données
│
└── ...
```

### 🧩 Architecture MVC du module Superadministrateur

Le module `superadmin/` applique le principe **MVC** :

```text
superadmin/
│
├── config/
│   └── database.php
│
├── controllers/
│   └── ...
│
├── models/
│   └── ...
│
├── views/
│   └── ...
│
└── ...
```

Cette organisation permet de séparer :

**Model**
Gestion des données et interactions avec la base de données.

**View**
Présentation et interfaces utilisateur.

**Controller**
Traitement des requêtes et coordination entre les modèles et les vues.

---

## 🛠️ Technologies utilisées

### Backend

* **PHP**
* **MySQL**
* **Composer**

### Frontend

* **HTML5**
* **CSS3**
* **JavaScript**
* **Bootstrap**
* **Bootstrap Icons**

### Outils

* **Node.js / npm**
* **Git**
* **GitHub**

---

## 🔒 Sécurité

Le système intègre notamment :

* authentification des utilisateurs ;
* gestion des sessions ;
* contrôle des accès selon les rôles ;
* hachage des mots de passe avec `password_hash()` ;
* requêtes préparées PDO ;
* protection des informations sensibles par configuration externe ;
* gestion des comptes et mots de passe.

Les fichiers contenant des informations sensibles sont exclus du dépôt à l'aide du `.gitignore`.

---

## 📈 Vision du projet

L'objectif du système est de permettre à l'établissement de passer d'une gestion administrative dispersée à une **gestion centralisée, sécurisée et orientée données**.

L'architecture actuelle constitue également une base permettant de faire évoluer progressivement le système vers :

* davantage d'automatisation ;
* des analyses statistiques avancées ;
* des outils d'aide à la décision ;
* l'intégration de fonctionnalités d'intelligence artificielle ;
* une architecture logicielle encore plus modulaire.

---

## 👨‍💻 Auteur

**Tala Talom Feune Jean-Jacques**

Projet académique réalisé dans le cadre d'une formation en **Sciences Informatiques**.
