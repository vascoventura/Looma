#keep this file in Looma/mongo-dump for Docker to copy
mongorestore

# Seed user logins: database "loomausers", collection "logins" — required for sign-in.
# defaultlogins.json is MongoDB extended JSON (one document per line), so it is
# loaded with mongoimport (matching Looma's update_database.cmd).
mongoimport --db loomausers --collection logins --file /logins/defaultlogins.json
