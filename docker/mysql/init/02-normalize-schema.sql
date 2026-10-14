-- Le dump crée `siren` en INTEGER : on repasse en CHAR(9) pour retrouver les zéros en tête.
ALTER TABLE mytable
    MODIFY siren CHAR(9) NOT NULL,
    MODIFY nom_unite_legale VARCHAR(100) DEFAULT NULL,
    MODIFY denomination_unite_legale VARCHAR(120) DEFAULT NULL,
    ADD INDEX idx_denomination (denomination_unite_legale),
    ENGINE = InnoDB,
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

UPDATE mytable SET siren = LPAD(siren, 9, '0');
