# Contributing Guidelines

* Fork the project.
* Make your feature addition or bug fix.
* Add tests for it. This is important so I don't break it in a future version unintentionally.
* Ensure your code is nicely formatted in the [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
  style and that all tests pass.
* Send the pull request.
* Check that the Travis CI build passed. If not, rinse and repeat.

### Running tests

#### Mysql
mysql -e "create database IF NOT EXISTS spot_test;" -uroot;

#### Postgres
psql -c 'create database spot_test;' -U postgres;

#### phpunit
script: phpunit --configuration phpunit_mysql.xml --coverage-text