Migrant
=======

1. Installation
---------------

Add to your `composer.json`:

```json
"require-dev": {
	"vladahejda/migrant": "dev-master"
}
```

2. Configuration
----------------

Append `[migrant]` section into [dg/ftp-deployment](https://github.com/dg/ftp-deployment/) INI configuration file:

```ini
[migrant]

; database DSN
; do not forget that this DSN must have all database rights to execute migration SQLs
dsn = <driver>://<username>:<password>@<host>/<database>

; production URL including protocol
siteUrl = http://example.com

; comma separated list of IP addresses that can trigger migrations via HTTP
allowedIps = 

; mail to notify failure of migrations
reportingMail = 

; remote directory for control files, defaults to root
;storageDir = /

; logs migrations success or failure, defaults to /log/migrant.log
;log = /log/migrant.log

; Directory with the migrations, defaults to /migrations
;migrationsDir = /migrations

; todo
;secretDir = /.secret
```

3. Make migrations
------------------

Place some SQL or PHP migrations into `migrationsDir` directory. Make for example `.sql` file with some table creation.

4. Trigger deployment
---------------------

Run from CLI `php <project-dir>/vendor/vladahejda/migrant/src/migrate <config-file>`.
