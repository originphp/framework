CREATE TABLE "posts" (
  "id" SERIAL,
  "title" VARCHAR(255) NOT NULL,
  "body" TEXT,
  "published" INTEGER DEFAULT 0,
  "created" TIMESTAMP,
  "modified" TIMESTAMP,
  PRIMARY KEY (id)
);