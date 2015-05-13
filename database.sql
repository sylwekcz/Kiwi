CREATE TABLE IF NOT EXISTS konta_dane_adresy (
	adres_id INT NOT NULL AUTO_INCREMENT,

	miejscowosc VARCHAR(25) NOT NULL,
	kod_pocztowy VARCHAR(6) NOT NULL,
	ulica VARCHAR(25) NOT NULL,
	nr_mieszkania VARCHAR(10) NOT NULL,


	PRIMARY KEY (adres_id),

	UNIQUE (miejscowosc, kod_pocztowy, ulica, nr_mieszkania)
);

CREATE TABLE IF NOT EXISTS konta_dane (
	dane_id INT NOT NULL AUTO_INCREMENT,

	imie VARCHAR(25) NOT NULL,
	nazwisko VARCHAR(25) NOT NULL,
	pesel VARCHAR(11) NOT NULL,
	telefon VARCHAR(12) NOT NULL,

	adres_id INT NOT NULL,


	PRIMARY KEY (dane_id),
	FOREIGN KEY (adres_id) REFERENCES konta_dane_adresy(adres_id),

	UNIQUE (pesel),
	UNIQUE (adres_id)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS konta (
	konto_id INT NOT NULL AUTO_INCREMENT,

	login VARCHAR(10) NOT NULL,
	email VARCHAR(30) NOT NULL,

	haslo VARCHAR(32) NOT NULL,
	salt VARCHAR(8) NOT NULL,

	dane_id INT NOT NULL,


	PRIMARY KEY (konto_id),
	FOREIGN KEY (dane_id) REFERENCES konta_dane(dane_id),

	UNIQUE (login),
	UNIQUE (email),
	UNIQUE (dane_id)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS sesje (
	sesja_id INT NOT NULL AUTO_INCREMENT,
	konto_id INT NOT NULL,

	aktywnosc TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

	hash VARCHAR(32) NOT NULL,
	salt VARCHAR(8) NOT NULL,

	ip VARCHAR(11) NOT NULL,
	agent VARCHAR(128) NOT NULL,


	PRIMARY KEY (sesja_id),
	FOREIGN KEY (konto_id) REFERENCES konta(konto_id),

	UNIQUE (hash, salt)
);

CREATE TABLE IF NOT EXISTS lektorzy (
	lektor_id INT NOT NULL AUTO_INCREMENT,
	konto_id INT NOT NULL,


	PRIMARY KEY (lektor_id),
	FOREIGN KEY (konto_id) REFERENCES konta(konto_id),

	UNIQUE (konto_id)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS kursanci (
	kursant_id INT NOT NULL AUTO_INCREMENT,
	konto_id INT NOT NULL,


	PRIMARY KEY (kursant_id),
	FOREIGN KEY (konto_id) REFERENCES konta(konto_id),

	UNIQUE (konto_id)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS jezyki (
	jezyk_id INT NOT NULL AUTO_INCREMENT,

	jezyk VARCHAR(20) NOT NULL,

	poziom CHAR(2) NOT NULL,

	PRIMARY KEY (jezyk_id),

	UNIQUE (jezyk, poziom)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS sale (
	sala_id INT NOT NULL AUTO_INCREMENT,

	numer_sali VARCHAR(5) NOT NULL,
	ilosc_miejsc INT NOT NULL,


	PRIMARY KEY (sala_id),

	UNIQUE (numer_sali)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS kursy (
	kurs_id INT NOT NULL AUTO_INCREMENT,

	jezyk_id INT NOT NULL,
	lektor_id INT NOT NULL,


	PRIMARY KEY (kurs_id),
	FOREIGN KEY (jezyk_id) REFERENCES jezyki(jezyk_id),
	FOREIGN KEY (lektor_id) REFERENCES lektorzy(lektor_id),

	UNIQUE (jezyk_id, lektor_id)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS kurs_kandydaci (
	kurs_id INT NOT NULL,
	kursant_id INT NOT NULL,


	FOREIGN KEY (kurs_id) REFERENCES kursy(kurs_id),
	FOREIGN KEY (kursant_id) REFERENCES kursanci(kursant_id),

	UNIQUE (kurs_id, kursant_id)
);

CREATE TABLE IF NOT EXISTS kurs_grupy (
	grupa_id INT NOT NULL AUTO_INCREMENT,

	kurs_id INT NOT NULL,


	PRIMARY KEY (grupa_id),
	FOREIGN KEY (kurs_id) REFERENCES kursy(kurs_id)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS kurs_grupa_kursanci (
	grupa_id INT NOT NULL,
	kursant_id INT NOT NULL,


	FOREIGN KEY (grupa_id) REFERENCES kurs_grupy(grupa_id),
	FOREIGN KEY (kursant_id) REFERENCES kursanci(kursant_id),

	UNIQUE (grupa_id, kursant_id)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS kurs_grupa_terminy (
	grupa_id INT NOT NULL,
	sala_id INT NOT NULL,

	termin DATETIME NOT NULL,
	czas_trwania INT NOT NULL,


	FOREIGN KEY (grupa_id) REFERENCES kurs_grupy(grupa_id),
	FOREIGN KEY (sala_id) REFERENCES sale(sala_id)
) ENGINE=INNODB;

CREATE TABLE IF NOT EXISTS kurs_grupa_kursant_oceny (
	kursant_id INT NOT NULL,
	ocena CHAR(1) NOT NULL,


	FOREIGN KEY (kursant_id) REFERENCES kurs_grupa_kursanci(kursant_id)
) ENGINE=INNODB;