Scripts d'initialisation MySQL (voir https://hub.docker.com/_/mysql#initializing-a-fresh-instance).

Les fichiers `*.sql`, `*.sql.gz` et `*.sh` de ce dossier sont exécutés par ordre alphabétique
au **premier** démarrage du conteneur (volume vide) :

- `01-schema.sql` : crée la table `mytable` ;
- `02-import-data.sh` : importe les CSV déposés dans `data/`.

Format CSV attendu (avec ligne d'en-tête) :
`siren,date_creation_unite_legale,prenom_unite_legale,tranche_effectifs_unite_legale,etat_administratif_unite_legale,nom_unite_legale,denomination_unite_legale,activite_principale_unite_legale`

Pour rejouer l'initialisation :

    docker compose down -v
    docker compose up -d
    php bin/console doctrine:migrations:migrate -n
