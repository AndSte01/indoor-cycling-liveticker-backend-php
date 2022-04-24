#!/bin/bash

# 0 adding config to shell
URL="http://localhost/liveticker/dev/discipline.php"
URL_USER="http://localhost/liveticker/dev/user.php"


# 2 prepare user to work with
printf "\n\n2.1: expecting SUCCESS\n"
curl -X POST -H 'Content-Type: application/json' -d '{"name":"temp_test_user", "password":"temp_test_password"}' "${URL_USER}?method=add"

printf "\n\n2.1: expecting SUCCESS\n"
curl -X POST -H 'Content-Type: application/json' -d '{"name":"temp_test_user1", "password":"temp_test_password1"}' "${URL_USER}?method=add"


# 3 trying to add competitions
URL_ADD="${URL}?method=add"

printf "\n\n\n\033[1mTesting for adding competitions\033[0m"

## 3.1 adding some competitions to the database
printf "\n\n3.1.1: expecting no error (discipline as json in return)\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"date":"2021-11-01", "name":"Demo Competition 1", "location":"Demo Location", "areas":0, "feature_set":4, "live":true}' ${URL_ADD}

printf "\n\n3.1.2: expecting no error (competition as json in return)\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"date":"2021-11-02", "name":"Demo Competition 2", "location":"Demo Location", "live":true}' ${URL_ADD}

printf "\n\n3.1.3: expecting no error (competition as json in return)\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"date":"2021-11-03", "name":"Demo Competition 3", "location":"Demo Location 2", "areas":3, "feature_set":4, "live":false}' ${URL_ADD}

## 3.2 checking if already existing competition is recognized correctly
printf "\n\n3.2.1: expecting ALREADY_EXISTS (all fields)\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"date":"2021-11-03", "name":"Demo Competition 3", "location":"Demo Location 2", "areas":3, "feature_set":4, "live":false}' ${URL_ADD}

printf "\n\n3.2.2: expecting ALREADY_EXISTS (everything but location)\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"date":"2021-11-03", "name":"Demo Competition 3", "areas":3, "feature_set":4, "live":false}' ${URL_ADD}

printf "\n\n3.2.3: expecting ALREADY_EXISTS (everything but name)\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"date":"2021-11-03", "location":"Demo Location 2", "areas":3, "feature_set":4, "live":false}' ${URL_ADD}

printf "\n\n3.2.4: expecting no error (same name/location but different date)\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"name":"Demo Competition 3", "location":"Demo Location 2", "areas":3, "feature_set":4, "live":false}' ${URL_ADD}

## 3.3 testing for invalid json
printf "\n\n3.3: expecting INVALID_JSON\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '' ${URL_ADD}


# 4 trying to edit competitions
URL_EDIT="${URL}?method=edit"

printf "\n\n\n\033[1mTesting for editing competitions\033[0m"

## 4.1 testing ids
printf "\n\n4.1.1: expecting SUCCESS\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"id":1, "date":"2021-11-05", "name":"Demo Competition 1 (updated)", "location":"Demo Location (updated)", "areas":1, "feature_set":6, "live":false}' ${URL_EDIT}

printf "\n\n4.1.2: expecting MISSING_INFORMATION\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"date":"2021-11-05", "name":"Demo Competition 1 (updated)", "location":"Demo Location (updated)", "areas":1, "feature_set":6, "live":false}' ${URL_EDIT}

printf "\n\n4.1.3: expecting NOT_EXISTING\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"id":100000, "date":"2021-11-05", "name":"Demo Competition 1 (updated)", "location":"Demo Location (updated)", "areas":1, "feature_set":6, "live":false}' ${URL_EDIT}

printf "\n\n4.1.4: expecting NOT_EXISTING (id < 1)\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"id":-1, "date":"2021-11-05", "name":"Demo Competition 1 (updated)", "location":"Demo Location (updated)", "areas":1, "feature_set":6, "live":false}' ${URL_EDIT}

## 4.2 testing for credentials
printf "\n\n4.2: expecting ACCESS_DENIED (wrong credential)\n"
curl -X POST -u temp_test_user1:temp_test_password1 --digest -H 'Content-Type: application/json' -d '{"id":1, "date":"2021-11-05", "name":"Demo Competition 1 (updated)", "location":"Demo Location (updated)", "areas":1, "feature_set":6, "live":false}' ${URL_EDIT}

## 4.3 testing for invalid JSON
printf "\n\n4.3: expecting INVALID_JSON\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '' ${URL_EDIT}

# 5 trying to get competitions

printf "\n\n\n\033[1mTesting for getting competitions\033[0m"

printf "\n\n5.1: expecting 4 competitions\n"
curl -X GET ${URL}

printf "\n\n5.2: expecting competition with id 4\n"
curl -X GET ${URL}?days=2

printf "\n\n5.2: expecting empty set\n"
curl -X GET ${URL}?limit=0

printf "\n\n5.3: expecting competition with id 4\n"
curl -X GET ${URL}?days=5\&limit=10

printf "\n\n5.4: expecting competition with id 2\n"
curl -X GET ${URL}?id=2


# 6 trying to remove competitions
URL_REMOVE="${URL}?method=remove"

printf "\n\n\n\033[1mTesting for removing competitions\033[0m"

## 6.1 testing ids

printf "\n\n6.1.1: expecting no error\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"id":1}' ${URL_REMOVE}

printf "\n\n6.1.2: expecting MISSING_INFORMATION\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{}' ${URL_REMOVE}

printf "\n\n6.1.3: expecting NOT_EXISTING\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"id":1}' ${URL_REMOVE}


## 6.2 testing for credentials

printf "\n\n6.2: expecting ACCESS_DENIED (wrong credential)\n"
curl -X POST -u temp_test_user1:temp_test_password1 --digest -H 'Content-Type: application/json' -d '{"id":2}' ${URL_REMOVE}

## 6.3 testing for invalid JSON

printf "\n\n6.3: expecting INVALID_JSON\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '' ${URL_REMOVE}

## 7 cleaning task
printf "\n\n\n\033[1mCleaning Task\033[0m\n"

curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"id":2}' ${URL_REMOVE}
printf "\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"id":3}' ${URL_REMOVE}
printf "\n"
curl -X POST -u temp_test_user:temp_test_password --digest -H 'Content-Type: application/json' -d '{"id":4}' ${URL_REMOVE}
printf "\n"
curl -X GET -u temp_test_user:temp_test_password --digest "${URL_USER}?method=remove"
printf "\n"
curl -X GET -u temp_test_user1:temp_test_password1 --digest "${URL_USER}?method=remove"
printf "\n"

# add a last newline for better terminal usability
printf "\n"