-- SmileCare Dental - database schema (v2)
-- Applied automatically by database/install.php

CREATE TABLE users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role           ENUM('admin','dentist','patient') NOT NULL DEFAULT 'patient',
    name           VARCHAR(120) NOT NULL,
    email          VARCHAR(160) NOT NULL,
    phone          VARCHAR(30)  NULL,
    password_hash  VARCHAR(255) NOT NULL,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    last_login_at  DATETIME     NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patients (
    user_id            INT UNSIGNED PRIMARY KEY,
    date_of_birth      DATE         NULL,
    gender             ENUM('male','female','other') NULL,
    address            VARCHAR(255) NULL,
    blood_group        VARCHAR(5)   NULL,
    allergies          VARCHAR(255) NULL,
    medical_notes      TEXT         NULL,
    emergency_contact  VARCHAR(120) NULL,
    CONSTRAINT fk_patients_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clinics (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(120) NOT NULL,
    address       VARCHAR(255) NOT NULL,
    phone         VARCHAR(30)  NULL,
    email         VARCHAR(160) NULL,
    open_time     TIME         NOT NULL DEFAULT '09:00:00',
    close_time    TIME         NOT NULL DEFAULT '18:00:00',
    working_days  VARCHAR(20)  NOT NULL DEFAULT '1,2,3,4,5,6' COMMENT 'ISO weekdays, 1=Mon..7=Sun',
    rooms         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE dentists (
    user_id           INT UNSIGNED PRIMARY KEY,
    clinic_id         INT UNSIGNED NULL,
    specialty         VARCHAR(100) NOT NULL DEFAULT 'General Dentistry',
    bio               TEXT         NULL,
    experience_years  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    employment_type   ENUM('permanent','visiting','trainee') NOT NULL DEFAULT 'permanent',
    CONSTRAINT fk_dentists_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_dentists_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE staff (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    position    VARCHAR(80)  NOT NULL,
    phone       VARCHAR(30)  NULL,
    email       VARCHAR(160) NULL,
    address     VARCHAR(255) NULL,
    clinic_id   INT UNSIGNED NULL,
    joined_on   DATE         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_staff_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE services (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code              VARCHAR(20)  NOT NULL,
    name              VARCHAR(120) NOT NULL,
    description       VARCHAR(500) NULL,
    price             DECIMAL(10,2) NOT NULL DEFAULT 0,
    duration_minutes  SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    UNIQUE KEY uq_services_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE appointments (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id        INT UNSIGNED NOT NULL,
    dentist_id        INT UNSIGNED NOT NULL,
    service_id        INT UNSIGNED NULL,
    clinic_id         INT UNSIGNED NULL,
    appointment_date  DATE NOT NULL,
    start_time        TIME NOT NULL,
    end_time          TIME NOT NULL,
    status            ENUM('pending','confirmed','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
    patient_notes     VARCHAR(500) NULL,
    visit_notes       TEXT NULL,
    cancel_reason     VARCHAR(255) NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_appt_dentist_date (dentist_id, appointment_date),
    KEY idx_appt_patient (patient_id),
    KEY idx_appt_date_status (appointment_date, status),
    CONSTRAINT fk_appt_patient FOREIGN KEY (patient_id) REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_appt_dentist FOREIGN KEY (dentist_id) REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_appt_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    CONSTRAINT fk_appt_clinic  FOREIGN KEY (clinic_id)  REFERENCES clinics(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no      VARCHAR(20)  NOT NULL,
    patient_id      INT UNSIGNED NOT NULL,
    appointment_id  INT UNSIGNED NULL,
    subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount        DECIMAL(10,2) NOT NULL DEFAULT 0,
    total           DECIMAL(10,2) NOT NULL DEFAULT 0,
    status          ENUM('unpaid','paid','void') NOT NULL DEFAULT 'unpaid',
    payment_method  VARCHAR(30)  NULL,
    issued_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at         DATETIME     NULL,
    UNIQUE KEY uq_invoice_no (invoice_no),
    UNIQUE KEY uq_invoice_appt (appointment_id),
    KEY idx_invoice_patient (patient_id),
    CONSTRAINT fk_invoice_patient FOREIGN KEY (patient_id)     REFERENCES users(id)        ON DELETE CASCADE,
    CONSTRAINT fk_invoice_appt    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE treatments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT UNSIGNED NOT NULL,
    dentist_id      INT UNSIGNED NULL,
    appointment_id  INT UNSIGNED NULL,
    service_id      INT UNSIGNED NULL,
    invoice_id      INT UNSIGNED NULL,
    description     VARCHAR(255) NOT NULL,
    tooth           VARCHAR(20)  NULL,
    notes           TEXT         NULL,
    price           DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_treat_patient (patient_id),
    KEY idx_treat_appt (appointment_id),
    CONSTRAINT fk_treat_patient FOREIGN KEY (patient_id)     REFERENCES users(id)        ON DELETE CASCADE,
    CONSTRAINT fk_treat_dentist FOREIGN KEY (dentist_id)     REFERENCES users(id)        ON DELETE SET NULL,
    CONSTRAINT fk_treat_appt    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    CONSTRAINT fk_treat_service FOREIGN KEY (service_id)     REFERENCES services(id)     ON DELETE SET NULL,
    CONSTRAINT fk_treat_invoice FOREIGN KEY (invoice_id)     REFERENCES invoices(id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE offers (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title             VARCHAR(120) NOT NULL,
    description       VARCHAR(500) NULL,
    discount_percent  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    valid_until       DATE         NULL,
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE feedback (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id  INT UNSIGNED NOT NULL,
    rating      TINYINT UNSIGNED NOT NULL,
    message     VARCHAR(1000) NOT NULL,
    is_public   TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE closed_days (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    closed_on  DATE NOT NULL,
    reason     VARCHAR(160) NULL,
    UNIQUE KEY uq_closed_on (closed_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(160) NOT NULL,
    ip            VARCHAR(45)  NOT NULL,
    attempted_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_attempts (email, attempted_at),
    KEY idx_login_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
