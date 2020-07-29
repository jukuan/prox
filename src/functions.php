<?php

if ( ! function_exists('dd')) {
    function dd($expression, $comment = null)
    {
        if ($comment) {
            var_dump($comment);
        }

        var_dump($expression);
        die();
    }
}
