
CREATE SEQUENCE test_id_seq;

CREATE TABLE test (
    id INTEGER NOT NULL PRIMARY KEY DEFAULT NEXTVAL('test_id_seq'::regclass),
    bar INTEGER NOT NULL,
    baz VARCHAR(16) NOT NULL DEFAULT 'fizz',
    foo VARCHAR(255) DEFAULT 'buzz'
);

CREATE INDEX ON test(bar);

ALTER TABLE test ADD CHECK (bar > 0);

CREATE FUNCTION test_before_insert() RETURNS "trigger" AS $$
BEGIN
    NEW.bar := NEW.bar + 1;
    RETURN NEW;
END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER test_before_insert BEFORE INSERT ON test FOR EACH ROW EXECUTE PROCEDURE test_before_insert();

IF NOT EXISTS (SELECT 1 FROM test WHERE id = 1) THEN
    INSERT INTO test (bar, foo) VALUES (2, 'foo');
END IF;

CREATE VIEW viewtest AS SELECT * FROM test;

CREATE SEQUENCE test2_id_seq;

CREATE TABLE test2 (
    id INTEGER NOT NULL PRIMARY KEY DEFAULT NEXTVAL('test2_id_seq'::regclass),
    test INTEGER NOT NULL
);

CREATE UNIQUE INDEX ON test2(test);
ALTER TABLE test2 ADD FOREIGN KEY (test) REFERENCES test(id) ON DELETE RESTRICT;

IF NOT EXISTS (SELECT 1 FROM test2 WHERE test = 1) THEN
    INSERT INTO test2 (test) VALUES (1);
END IF;

