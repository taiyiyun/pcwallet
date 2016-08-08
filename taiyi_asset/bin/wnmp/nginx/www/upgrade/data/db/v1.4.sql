alter table wallet add column pos  INTEGER;||
alter table mywallet add column pos  INTEGER;||
alter table mywallet add column unlock  INTEGER;||
update mywallet set pos=1 where mark='ybcoin';||
update wallet set pos=1 where mark='ybcoin';||
CREATE TABLE IF NOT EXISTS "lang" ("lang"  TEXT);||
INSERT INTO "lang" ("lang") VALUES ('cn');||
--appdata--CREATE TABLE IF NOT EXISTS "address" ("address"  TEXT,"label"  TEXT,"mark"  TEXT);