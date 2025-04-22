# Change Log
## [1.0.20] 2025-04-22
- Fixes for Mcrypt / OpenSSL
- Fixes on PBX_BILLING & PBX_SOURCE

## [1.0.19] 2024-10-16
- Improve compatibility with Magento 2.4.6 & PHP 8.2 compatibility
- Fix order confirmation email
- Single payment method fixes
- Fixes on PBX_BILLING

## [1.0.18] 2023-02-08
Update BackOffice settings
Add PBX_SHOPPINGCART
Fix Magento 2.4.5 & PHP 8 compatibility

## [1.0.17] 2022-08-03
Wording fixes and a few minor fixes

## [1.0.16] 2021-09-24

### Bug Fixing
- Adding Country phone code for Reunion

## [1.0.15] 2020-04-03

### Bug Fixing
- xml: suppression accents des adresses

## [1.0.14] 2019-12-19

### Bug Fixing
- Compatibilité : corrections diverses (xml + urls)

## [1.0.13] 2019-11-28

### Sofinco
- First Sofinco release


## [1.0.12] 2019-03-07

### Magento 2.3
- Compatibilité : Nouvelle version du module pour Magento 2.3  https://gitlab.com/ETransactions/Magento2

## [1.0.11] 2019-02-06

### Corrections
- BO : Correction du problème d'encryption des champs hmackey et password

## [1.0.10] 2018-12-20

### Corrections
- Code : Correction d'une erreur d'exception dans onIPNError()
- Branding : Correction d'une classe nom colorisée

## [1.0.9] 2017-12-20

### Modifications
- Composer : Toutes versions de PHP 7.x
- Composer : Suppression du 'require' pour magento/framework

## [1.0.8] 2017-11-09

### Corrections
- Code : suppression des classes dans "Model/Resource/Payment" inutiles avec un namespace incorrect
- FO - Cache : gestion mise en cache des pages (problématique si page en cache est une 404)
- Code - Injection de dépendances : correction erreur de compilation et utilisation

### Modifications
- Traductions : termes manquants en anglais dans la configuration du module
- Code : nettoyage PSR-2 et Magento Extension Quality Program Coding Standard

### Ajouts
- FO - Paiement : gabarit pour informations de paiement dans le détail commande et les e-mails client

## [1.0.7] 2017-07-04

### Corrections
- Facturation : envoi de l'e-mail lors de la capture
- FO - Paiement : suppression erreur sur validation du module si un autre moyen de paiement est choisi

### Modifications
- Code : nettoyage PSR-2 et adaptations pour validation MarketPlace Magento

## [1.0.6] 2017-03-09

### Corrections
- IPN : mise en conformité des paramètres "Call number" / "Transaction"
- IPN : modification de l'enregistrements des transactions non valides (saisie de coordonnées bancaires invalides, ...) pour création de transaction vide => correction du problème d'actions Back Office qui avant cela utilisaient la 1ère transaction invalide de capture comme transaction parente
- Paiement : nettoyage du panier et de la commande en cas de paiement refusé ou annulé

### Modifications
- Code : nettoyage PSR-2 et adaptations pour validation MarketPlace Magento

## [1.0.5] 2016-11-15

### Ajouts
- Paiement : possibilité d'utiliser la page de paiement Sofinco RWD
- PayPal : paramétrage spécifique lors de l'appel à la plateforme de paiement

## [1.0.4] 2016-11-15

### Corrections
- Bloc Redirect : pas de cache et registre spécifique

## [1.0.3] 2016-11-09

### Corrections
- Observer : correction des problèmes avec "additional_data" depuis la version 2.0.1 de Magento
- JS Redirect :  modification de la méthode de redirection vers Sofinco. Redirection après orderPlaced

## [1.0.2] 2016-10-26

### Corrections
- Observer : paramètres d'appels obligatoires manquants
- ACL : déclaration BO incorrecte

## [1.0.1] 2016-10-25

### Ajouts
- Paiement : ajout du paramètre de version pour suivi des transactions par Sofinco
- Configuration : gestion du multi-devise pour le paiement avec possibilité de forcer le paiement avec la devise par défaut ou de laisser le choix au client parmi les devises disponibles

### Modifications
- Traductions

### Corrections
- FO - Paiement : correction pour fonctionnement en sous-dossier ou sous-domaine
