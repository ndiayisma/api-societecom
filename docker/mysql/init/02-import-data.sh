#!/bin/bash
# Importe chaque CSV de ./data dans `mytable` (colonnes dans l'ordre de la table, 1re ligne = en-tête).
set -e

for csv in /docker-entrypoint-initdb.d/data/*.csv; do
    [ -e "$csv" ] || continue
    echo "Import de $csv"
    mysql --local-infile=1 -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" <<SQL
LOAD DATA LOCAL INFILE '$csv'
INTO TABLE mytable
CHARACTER SET utf8mb4
FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(siren, @date_creation, @prenom, @tranche, etat_administratif_unite_legale, @nom, @denomination, @activite)
SET date_creation_unite_legale = NULLIF(@date_creation, ''),
    prenom_unite_legale = NULLIF(@prenom, ''),
    tranche_effectifs_unite_legale = NULLIF(@tranche, ''),
    nom_unite_legale = NULLIF(@nom, ''),
    denomination_unite_legale = NULLIF(@denomination, ''),
    activite_principale_unite_legale = NULLIF(TRIM(TRAILING '\r' FROM @activite), '');
SQL
done
