#!/bin/bash
set -e

function create_user_and_database() {
    local database=$1
    local user=$2
    local password=$3
    echo "  Creating user '$user' and database '$database'"
    psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-EOSQL
        CREATE USER $user WITH SUPERUSER PASSWORD '$password';
        CREATE DATABASE $database;
EOSQL
}

# Create multiple users and databases
create_user_and_database "$HR_DATABASE" "$HR_USER" "$HR_PASSWORD"
create_user_and_database "$HUB_DATABASE" "$HUB_USER" "$HUB_PASSWORD"

# You can add more as needed
# create_user_and_database "another_db" "another_user"
echo "Multiple users and databases created"
