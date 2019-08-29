# keep published integer not boolean for seed.sql

CREATE TABLE authors (
  id SERIAL,
  author_name VARCHAR(80) NOT NULL,
  created TIMESTAMP NOT NULL,
  modified TIMESTAMP NOT NULL
);

CREATE TABLE posts (
  id SERIAL,
  title VARCHAR(80) NOT NULL,
  body TEXT,
  published SMALLINT,
  created TIMESTAMP NOT NULL,
  modified TIMESTAMP NOT NULL
);