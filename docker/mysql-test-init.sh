#!/bin/sh
# Grant the app DB user rights to create/drop paratest's per-process test
# databases (temari_testing_test_1, _2, ...) so `pest --parallel` works
# locally the way it does in CI (which runs as root). mysql_test is tmpfs, so
# this re-applies on every container start.
set -e

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
GRANT ALL PRIVILEGES ON \`temari_testing%\`.* TO '${MYSQL_USER}'@'%';
FLUSH PRIVILEGES;
SQL
