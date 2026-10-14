-- Unités légales (extrait du stock SIRENE)
CREATE TABLE IF NOT EXISTS mytable (
    siren CHAR(9) NOT NULL,
    date_creation_unite_legale DATE DEFAULT NULL,
    prenom_unite_legale VARCHAR(20) DEFAULT NULL,
    tranche_effectifs_unite_legale VARCHAR(2) DEFAULT NULL,
    etat_administratif_unite_legale CHAR(1) NOT NULL,
    nom_unite_legale VARCHAR(100) DEFAULT NULL,
    denomination_unite_legale VARCHAR(120) DEFAULT NULL,
    activite_principale_unite_legale VARCHAR(6) DEFAULT NULL,
    PRIMARY KEY (siren),
    INDEX idx_denomination (denomination_unite_legale)
) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
