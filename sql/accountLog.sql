CREATE TABLE accountLogx (
    id              integer     auto_increment primary key,
    log_timestamp   datetime    not null,
    pennkey         varchar(16),
    penn_id         varchar(16),
    log_type        varchar(16) not null,
    message         varchar(200)
);

CREATE INDEX accountLogPennkeyIndex ON accountLog(pennkey);
CREATE INDEX accountLogPennIdIndex  ON accountLog(penn_id);