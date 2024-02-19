# mymeta

[![Release](https://img.shields.io/github/v/release/franck-paul/mymeta)](https://github.com/franck-paul/mymeta/releases)
[![Date](https://img.shields.io/github/release-date/franck-paul/mymeta)](https://github.com/franck-paul/mymeta/releases)
[![Issues](https://img.shields.io/github/issues/franck-paul/mymeta)](https://github.com/franck-paul/mymeta/issues)
[![Dotaddict](https://img.shields.io/badge/dotaddict-official-green.svg)](https://plugins.dotaddict.org/dc2/details/mymeta)
[![License](https://img.shields.io/github/license/franck-paul/mymeta)](https://github.com/franck-paul/mymeta/blob/master/LICENSE)

(Doc récupéré sur morefnu.org via WaybackMachine.     
Cette doc doit être revérifié et mise à jour, elle date de la version 0.4.1.(Mais c'est mieux que rien))

Ce plugin permet d'ajouter dans l'interface de création/édition de billets un certain nombre de métadonnées spécialisées, dont le type est choisi par l'utilisateur. De la même manière que les tags, il est possible d'ajouter des métadonnées spécifiques à un blog particulier, et de les éditer facilement.

Le plugin se compose de 2 parties :

* Une partie "Métadonnées personnalisées", accessible dans la colonne de gauche, qui permet de définir les métadonnées voulues (avec le meta_type que l'on veut). 2 types de métadonnées sont possibles : chaîne de caractères, et liste de valeurs.
* Une partie dans le formulaire d'édition des billets, qui permet de définir les métadonnées voulues pour le billet voulu.
* Des balises de template.
* Des Widgets.

## Administration
* Il est possible d'ordonner les mymeta, et de les placer dans des sections particulières
* Deux nouveaux types de mymeta voient le jour : Date (enfin!) et checkbox. Le nouveau modèle plus souple permet d'ailleurs de définir de nouveaux types de mymeta sans casser la structure existante. Un plugin externe peut ainsi enregistrer ses propres types de mymeta
* Pour chaque mymeta, il est désormais possible de définir :
  * Si une page publique est définie pour afficher les valeurs du mymeta, ou la liste des billets correspondant à une valeur donnée
  * les fichiers de template à utiliser pour ces pages publiques (par défaut mymetas.html et mymeta.html)
  * les types de billets concernés par ce mymeta. Avec l'arrivée du plugin muppet, cela prend tout son sens.
* Il est possible d'appliquer des mymeta sur plusieurs billets, via la liste des billets
* Il est possible de renommer une valeur mymeta pour tous les billets

## Widgets
* la liste de MyMeta, affichant les mymeta définis pour le blog.
* La liste de valeurs MyMeta (similaire au widget Tags), affichant l'ensemble des valeurs prises par un mymeta donné.

## Les balises de template

### MyMetaData
    <tpl:MyMetaData>...</tpl:MyMetaData>
#### Contextes d'utilisation
* Dans un fichier de template de type mymetas.html

#### Paramètres
* les mêmes que pour tpl:Tags (anciennement tpl:MetaData)

#### Description
Récupère l'ensemble des valeurs de mymeta pour le mymeta en cours

### MyMetaIf
    <tpl:MyMetaIf>...</tpl:MyMetaIf>
#### Contextes d'utilisation
* Au sein de blocs où le contexte "posts" est défini (tpl:Entries par exemple)    
* Dans le template post.html

#### Paramètres
* type (obligatoire): ID du mymeta à tester    
* defined="true" : affiche le contenu du bloc si le mymeta est défini    
* value : affiche le contenu du bloc si le mymeta a bien la valeur renseignée

#### Description
Teste la valeur ou l'existence d'une valeur de mymeta pour le billet en cours

### MetaType
    {{tpl:MetaType}}
#### Contextes d'utilisation
* Dans une boucle tpl:MyMetaData ou similaire (tpl:Tags, ou tpl:EntryTags par exemple)     
* Dans un fichier de template de type mymeta.html

#### Paramètres
* aucun

#### Description
Affiche le type de métadonnée (qui est l'ID du mymeta, et correspond au meta_type en base).

### MyMetaTypePrompt
    {{tpl:MyMetaTypePrompt}}

#### Contextes d'utilisation
* Si le paramètre id est défini, n'importe où
* Sinon :
  * dans une boucle tpl:MyMetaData
  * dans les templates mymetas.html et mymeta.html

#### Paramètres
* id (facultatif) : identifiant du mymeta
* type : identique à type, déprécié

#### Description
Affiche l'invite d'un mymeta (s'il est activé):
* Si id est spécifié, affiche l'invite du mymeta correspondant
* Sinon, affiche l'invite du mymeta dans le contexte de la page ou de la boucle tpl:MyMetaData

### MyMetaValue
    {{tpl:MyMetaValue}}

#### Contextes d'utilisation
* dans une boucle tpl:MyMetaData
* dans les templates mymetas.html et mymeta.html

#### Paramètres
* id (facultatif) : identifiant du mymeta
* type : identique à type, déprécié

#### Description
Affiche la valeur d'un mymeta (s'il est activé):
* Si id est spécifié, affiche la valeur du mymeta correspondant
* Sinon, affiche la valeut du mymeta dans le contexte de la page ou de la boucle tpl:MyMetaData

**Note**: cette balise a un comportement différent dans MyMeta 0.3. Dans un contexte de billet, en 0.4, il faut utiliser tpl:EntryMyMetaValue

### MyMetaURL
    {{tpl:MyMetaURL}}

#### Contextes d'utilisation
* dans une boucle tpl:MyMetaData
* dans les templates mymetas.html et mymeta.html

#### Paramètres
* id (facultatif) : identifiant du mymeta
* type : identique à type, déprécié

#### Description
Affiche l'URL d'un mymeta (s'il est activé):
* Si id est spécifié, affiche l'URL du mymeta correspondant
* Sinon, affiche la valeut du mymeta dans le contexte de la page ou de la boucle tpl:MyMetaData
  
### EntryMyMetaValue
    {{tpl:EntryMyMetaValue}}

#### Contextes d'utilisation
* Au sein de blocs où le contexte "posts" est défini (tpl:Entries par exemple)
* Dans le template post.html

#### Paramètres
* id (obligatoire): identifiant du mymeta
* type : identique à type, déprécié

#### Description
Affiche la valeur d'un mymeta (s'il est activé) correspondant à l'ID donné pour le billet en cours (dans la boucle, ou le billet courant si dans le template post.html par exemple)


## Cas concret

### Définir une couleur de ses billets
    <div class="post {{tpl:MyMetaValue type="couleur"}}">

    <div class="post beige">
### Ne pas afficher un billet dans la liste des billets
    <tpl:Entries>
      <tpl:MyMetaIf type="hide" defined="false" value="true" operator="||"/>
        ...
      </tpl:MyMetaIf>
    </tpl:Entries>
  

