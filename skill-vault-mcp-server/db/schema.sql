CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    username VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(180),
    created_at TIMESTAMP NOT NULL DEFAULT now(),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL
);
