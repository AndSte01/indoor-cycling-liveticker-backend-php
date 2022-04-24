#!/bin/bash

# 0 adding config to shell
URL="http://localhost/liveticker/dev/user.php"


# 1 trying to add users
URL_ADD="${URL}?method=add"

printf "\033[1mTesting for adding users\033[0m"

## 1.1 adding some users to the database
printf "\n\n1.1.1: expecting SUCCESS\n"
curl -X POST -H 'Content-Type: application/json' -d '{"name":"Jaffe", "password":"Y)CGA)DY8+md.-]a"}' ${URL_ADD}

printf "\n\n1.1.2: expecting SUCCESS\n"
curl -X POST -H 'Content-Type: application/json' -d '{"name":"Chizoba",  "password":"sM3FV.vD5+=jtMx"}' ${URL_ADD}

## 1.2 testing for invalid json
printf "\n\n1.2: expecting INVALID_JSON\n"
curl -X POST -H 'Content-Type: application/json' -d '{"name":"Chizoba",,  "password":"sM3FV.vD5+=jtMx"}' ${URL_ADD}

## 1.3 testing for missing information
printf "\n\n1.3.1: expecting MISSING_INFORMATION\n"
curl -X POST -H 'Content-Type: application/json' -d '{"name":"Chizoba"}' ${URL_ADD}

printf "\n\n1.3.2: expecting MISSING_INFORMATION\n"
curl -X POST -H 'Content-Type: application/json' -d '{"password":"sM3FV.vD5+=jtMx"}' ${URL_ADD}

## 1.4 testing for already exiting usernames
printf "\n\n1.4: expecting ALREADY_EXISTS\n"
curl -X POST -H 'Content-Type: application/json' -d '{"name":"Chizoba",  "password":"sM3FV.vD5+=jtMx"}' ${URL_ADD}


# 2 Testing authentication
printf "\n\n\n\033[1mTesting for authentication\033[0m"

# 2.1 no authentication information
printf "\n\n2.1: expecting AUTHENTICATION_REQUIRED\n"
curl -X GET ${URL}

# 2.2 wrong username
printf "\n\n2.2: expecting NOT_EXISTING\n"
curl -X GET -u username:password --digest ${URL}

# 2.3 wrong password
printf "\n\n2.3: expecting ACCESS_DENIED\n"
curl -X GET -u Jaffe:password --digest ${URL}

# 2.4 successful login
printf "\n\n2.4: expecting SUCESS\n"
curl -X GET -u Jaffe:'Y)CGA)DY8+md.-]a' --digest ${URL}


# 3 trying to edit users
URL_EDIT="${URL}?method=edit"

printf "\n\n\n\033[1mTesting for editing user\033[0m"

## 3.1 testing for missing information
printf "\n\n3.1.1: expecting MISSING_INFORMATION\n"
curl -X POST -u Jaffe:'Y)CGA)DY8+md.-]a' --digest -H 'Content-Type: application/json' -d '{"name":"Chizoba"}' ${URL_EDIT}

printf "\n\n3.1.2: expecting MISSING_INFORMATION\n"
curl -X POST -u Jaffe:'Y)CGA)DY8+md.-]a' --digest -H 'Content-Type: application/json' -d '{"password":"sM3FV.vD5+=jtMx"}' ${URL_EDIT}

## 3.2 testing for invalid JSON
printf "\n\n3.2: expecting INVALID_JSON\n"
curl -X POST -u Jaffe:'Y)CGA)DY8+md.-]a' --digest -H 'Content-Type: application/json' -d '{"name":"Chizoba",,  "password":"sM3FV.vD5+=jtMx"}' ${URL_EDIT}

## 3.3 no error
printf "\n\n3.3: expecting SUCCESS\n"
curl -X POST -u Jaffe:'Y)CGA)DY8+md.-]a' --digest -H 'Content-Type: application/json' -d '{"name":"Jaffe2", "password":"Y)CGA)DY8+md.-]a2"}' ${URL_EDIT}


# 4 trying to remove users
URL_REMOVE="${URL}?method=remove"

printf "\n\n\n\033[1mTesting for removing users\033[0m"

## 4.1 removing
printf "\n\n4.1: expecting SUCESS\n"
curl -X GET -u Jaffe2:'Y)CGA)DY8+md.-]a2' --digest ${URL_REMOVE}

## 5 cleaning task
printf "\n\n\n\033[1mCleaning Task\033[0m\n"

curl -X GET -u Chizoba:'sM3FV.vD5+=jtMx' --digest ${URL_REMOVE}
printf "\n"

# add a last newline for better terminal usability
printf "\n"