Scripts d'initialisation MySQL (voir https://hub.docker.com/_/mysql#initializing-a-fresh-instance).

Les fichiers `*.sql`, `*.sql.gz` et `*.sh` de ce dossier sont exécutés par ordre alphabétique
au **premier** démarrage du conteneur (volume vide) :

- `01-StockUniteLegale_100000_utf8.sql` : dump SIRENE (crée `mytable` + 100 000 unités légales) ;
- `02-normalize-schema.sql` : `siren` en CHAR(9) avec zéros en tête, colonnes élargies, index.

Pour rejouer l'initialisation :

    docker compose down -v
    docker compose up -d
    php bin/console doctrine:migrations:migrate -n
