<?php
function test()
{
    if ( $a == 42 )
    {
        echo "The argument is 42\n";
    }
    else
    {
        throw new Exception( "Not 42!" );
        echo "nope\n";
    }
}

try
{
    test( 42 );
}
catch ( Exception $e )
{
    echo "Do nothing!\n";
}
?>