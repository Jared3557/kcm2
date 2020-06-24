<!DOCTYPE html>
<html>
<head>
</head>
<body>


<?php
 
 
class rsm_extendedArray extends ArrayIterator {  

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

public function get_property_extendedArray( $key , $propName )  {
    if ( ! isset( $this->properties[$propName] ) ) {
        $this->properties[$propName] = array();
    }    
    $propArray = &$this->properties[$propName];
    if ( ! isset( $propArray[$key] ) ) {
        $propArray[$key] = new rsm_extendedArray;
    }    
    return $propArray[$key];
}

}  // end class


class appArray extends rsm_extendedArray {
    
function read() {
    $this['d'] = 'ddd';
    $this['f'] = 'fff';
    $this['g'] = 'ggg';
    $this->set_property('g','lower','g');
    $this->set_property('g','upper','G');
}
    
}

$programs = new  rsm_extendedArray;

$programs['IdWalker'] = 'Walker School';
$periods = $programs->get_property_extendedArray( 'IdWalker' , 'Periods' );
$periods['Id-1'] = '1st Period';
$kids = $periods->get_property_extendedArray( 'Id-1' , 'Kids' );
$kids['Id-Kid1'] = 'Alice';
$kids['Id-Kid2'] = 'Ben';
$kids['Id-Kid3'] = 'Charles';
$periods = $programs->get_property_extendedArray( 'IdWalker' , 'Periods' );  // not necessary here
$periods['Id-2'] = '2nd Period';
$kids = $periods->get_property_extendedArray( 'Id-2' , 'Kids' );
$kids['Id-Kid1'] = 'Rob';
$kids['Id-Kid2'] = 'Sally';
$kids['Id-Kid3'] = 'Tom';

$programs['IdChalker'] = 'Chalker School';
$periods = $programs->get_property_extendedArray( 'IdChalker' , 'Periods' );
$periods['Id-1'] = '1st Period';
$periods['Id-2'] = '2nd Period';

$programs['IdFord'] = 'Ford School';
$periods = $programs->get_property_extendedArray( 'IdFord' , 'Periods' );
$periods['Id-1'] = '1st Period';
$periods['Id-2'] = '2nd Period';

//$programs->uksort(function($$programs->,$b){if ($$programs->==$b) return 0; else if ($$programs->>$b) return 1; else return 0;});

foreach ( $programs as $progKey => $programRecord ) {
    echo "<br>{$programRecord} (Key=$progKey)";
    $periods = $programs->get_property($progKey,'Periods');
    foreach ( $periods as $periodKey => $periodRecord ) {
        echo "<br>.....{$periodRecord} (Key=$periodKey)";
        $kids = $periods->get_property($periodKey,'Kids');
        if ( empty($kids) ){
            continue;
        }     
        foreach ( $kids as $kidKey => $kidRecord ) {
            echo "<br>.........{$kidRecord} (Key=$kidKey)";
        }
   }
}

exit;

?>

</body>
</html>

