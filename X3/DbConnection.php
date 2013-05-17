<?php

namespace X3 {

    abstract class DbConnection {

        abstract public function connect($config = array());

        abstract public function validateQuery($query = null);

        abstract public function query();

        abstract public function count();

        abstract public function fetch();

        abstract public function fetchAll();

        abstract public function fetchFields($entity);

        abstract public function fetchEntities();

        abstract public function entityExists($modelName);

        abstract public function getErrors();
    }

}