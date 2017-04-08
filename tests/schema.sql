
CREATE SEQUENCE IF NOT EXISTS test_id_seq;
CREATE TABLE test (
    id INTEGER NOT NULL DEFAULT NEXTVAL('test_id_seq'),
    bar INTEGER NOT NULL,
    baz VARCHAR(16) NOT NULL DEFAULT 'fizz',
    foo VARCHAR(255) DEFAULT 'buzz'
);

CREATE INDEX test_bar_idx ON test(bar);

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

