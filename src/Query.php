<?php
namespace PedanticQuerent;

interface Query {
    function getSQL();
    function getBindings();
}
