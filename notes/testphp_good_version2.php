<!DOCTYPE html>
<html>
<head>
</head>
<body>


<?php
 
 
class rsm_extendedList extends ArrayIterator {  

private $properties = array();  
 
public function el_set( $key ,  $value ) {
    $this[$key] = $value;
}

public function el_get ( $index, $defaultValue ) {
    return parent::offsetExists ( $index ) ? parent::offsetGet ( $index ) : $defaultValue;
}

public function el_setProperty( $propName , $key ,  $value ) {
    if ( ! isset( $this->properties[$propName] ) ) {
        $this->properties[$propName] = array();
    }    
    $propArray = &$this->properties[$propName];
    $propArray[$key] = $value;
}

public function el_getProperty( $propName, $key )  {
    if ( ! isset( $this->properties[$propName] ) ) {
        return NULL;
    }    
    $propArray = &$this->properties[$propName];
    return isset( $propArray[$key] ) ? $propArray[$key] : null;
}

public function el_createList( $listName, $key )  {  // listName is same as proerty name (but typed more precisely )
    if ( ! isset( $this->properties[$listName] ) ) {
        $this->properties[$listName] = array();
    }    
    $propArray = &$this->properties[$listName];
    if ( ! isset( $propArray[$key] ) ) {
        $propArray[$key] = new rsm_extendedList;
    }    
    return $propArray[$key];
}

}  // end class

$schoolList = new  rsm_extendedList;
$schoolList->el_set( 'IdWalker' , 'Walker School' );  // or: $schoolList['IdWalker'] = 'Walker School';

$periodList = $schoolList->el_createList( 'PeriodList' , 'IdWalker' );  // will use existing list if list already exists
$periodList->el_set( 'Id-1' , '1st Period' ); // or: - $periodList['Id-1'] = '1st Period';
$kidList = $periodList->el_createList( 'KidList', 'Id-1' );
$kidList->el_set( 'Id-Kid1' , 'Alice');      // or: $kidList['Id-Kid1'] = 'Alice';
$kidList->el_set( 'Id-Kid2' , 'Ben');        // or: $kidList['Id-Kid2'] = 'Ben';
$kidList->el_set( 'Id-Kid3' , 'Charles');    // or: $kidList['Id-Kid3'] = 'Charles';
$periodList = $schoolList->el_createList( 'PeriodList' , 'IdWalker' );  // will use existing list if list already exists
$periodList['Id-2'] = '2nd Period';
$kidList = $periodList->el_createList( 'KidList', 'Id-2'  );
$kidList->el_set( 'Id-Kid1' , 'Rob');      // or:  $kidList['Id-Kid1'] = 'Rob';
$kidList->el_set( 'Id-Kid2' , 'Sally');    // or:  $kidList['Id-Kid2'] = 'Sally';
$kidList->el_set( 'Id-Kid3' , 'Tom');      // or:  $kidList['Id-Kid3'] = 'Tom';

$schoolList['IdChalker'] = 'Chalker School';
$periodList = $schoolList->el_createList(  'PeriodList' , 'IdChalker'  );
$periodList->el_set( 'Id-1' , '1st Period' );
$periodList->el_set( 'Id-2' , '2nd Period' );

$schoolList['IdFord'] = 'Ford School';
$periodList = $schoolList->el_createList( 'PeriodList' , 'IdFord'  );
$periodList->el_set( 'Id-1' , '1st Period' );
$periodList->el_set( 'Id-2' , '2nd Period' );

//$schoolList->uksort(function($$schoolList->,$b){if ($$schoolList->==$b) return 0; else if ($$schoolList->>$b) return 1; else return 0;});

foreach ( $schoolList as $schoolItemKey => $schoolItem ) {
    echo "<br>{$schoolItem} (Key=$schoolItemKey)";
    $periodList = $schoolList->el_createList('PeriodList', $schoolItemKey );
    foreach ( $periodList as $periodKey => $periodItem ) {
        echo "<br>.....{$periodItem} (Key=$periodKey)";
        $kidList = $periodList->el_createList('KidList', $periodKey );
        if ( empty($kidList) ){
            continue;
        }     
        foreach ( $kidList as $kidItemKey => $kidItem ) {
            echo "<br>.........{$kidItem} (Key=$kidItemKey)";
        }
   }
}

exit;

?>

</body>
</html>

