<?php
namespace SmallPHP\Files;

function is_valid_file($file): bool
{
    return ($file["size"] > 0);
}
