<!DOCTYPE html>
<html>
<head>
</head>
<body>


<?php
 
 
class krn_extendedArray extends ArrayIterator {  

private $properties = array();  
 
public function set_property( $key , $propName , $value ) {
    if ( ! isset( $this->properties[$propName] ) ) {
        $this->properties[$propName] = array();
    }    
    $propArray = &$this->properties[$propName];
    $propArray[$key] = $value;
}

public function get_property( $key , $propName )  {
    if ( ! isset( $this->properties[$propName] ) ) {
        return NULL;
    }    
    $propArray = &$this->properties[$propName];
    return isset( $propArray[$key] ) ? $propArray[$key] : null;
}

public function getIndex ( $index, $defaultValue = NULL ) {
    return parent::offsetExists ( $index ) ? parent::offsetGet ( $index ) : NULL;
}

}  // end class

$a = new  krn_extendedArray;

$a['a'] = 'A-test';
$a->set_property( 'a' , 'lower' , 'aaa');
$a->set_property( 'a' , 'upper' , 'AAA');
$a['b'] = 'B-test';
$a->set_property( 'b' , 'lower' , 'bbb');
$a['c'] = 'C-test';
$a->set_property( 'c' , 'lower' , 'ccc');
$a->set_property( 'c' , 'upper' , 'CCC');

foreach ( $a as $key=>$value ) {
   echo '<br><br>' , $key , ' - ' , $value , ' - ' , $a->get_property( $key , 'lower' ), ' - ' , $a->get_property( $key , 'upper' );
}

echo '<br><br>Count = ' , $a->count();

echo '<br><br>$[a] =' , $a->getIndex('a');
echo '<br><br>$[xx]= ' , $a->getIndex('xx');

echo '<br><br>$[a] =' , $a['a'];
echo '<br><br>$[xx]= ' , $a['xx'];

exit;

?>

</body>
</html>

