FROM mongo:5.0.27-focal

COPY ./dump/ /dump
COPY ./logins/ /logins
COPY mongorestore.sh /docker-entrypoint-initdb.d/mongorestore.sh
