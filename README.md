# Payment Service

A Service to get users balance and change it.

## Initialization

* Copy this repository on your local computer:

`git clone git@github.com:ashandi/payment_service.git`

* Run command below to set up the Service and database (from the project directory):

`cd payment_service && make init`

## Usage

* `GET` `localhost:8080/v1/users/{id}`
to get detail information about a user with provided {id}

* `POST` `localhost:8080/v1/transactions` 
with body:
```json
{
  "amount": {amount},
  "dstUserId": {id},
}
```
to deposit user with {id} on {amount}

* `POST` `localhost:8080/v1/transactions`
  with body:
```json
{
  "srcUserId": {id1},
  "amount": {amount},
  "dstUserId": {id2},
}
```
to transfer {amount} from user with {id1} to user with {id2}

Please see more details in `/contract/swagger.yml`

## Tests

use command `docker-compose exec app make test` to run Service tests.
