<!DOCTYPE html>
<html>
<head>
</head>
<body>


<?php
 
 
class rsm_mapArray extends ArrayIterator {  

private $properties = array();  
 
public function map_setItem( $itemKey ,  $value ) {
    $this[$itemKey] = $value;
}

public function map_getItem ( $itemKey, $defaultValue ) {
    return parent::offsetExists ( $itemKey ) ? parent::offsetGet ( $itemKey ) : $defaultValue;
}

public function map_setProperty( $propName , $itemKey ,  $value ) {
    if ( ! isset( $this->properties[$propName] ) ) {
        $this->properties[$propName] = array();
    }    
    $propArray = &$this->properties[$propName];
    $propArray[$itemKey] = $value;
}

public function map_getProperty( $propName, $itemKey )  {
    if ( ! isset( $this->properties[$propName] ) ) {
        return NULL;
    }    
    $propArray = &$this->properties[$propName];
    return isset( $propArray[$itemKey] ) ? $propArray[$itemKey] : null;
}

public function map_createMap( $mapName, $itemKey )  { 
    return $this->map_getMap( $mapName, $itemKey );
}

public function map_getMap( $mapName, $itemKey )  { 
    // mapName is same logic as property name (but typed more precisely and defaulted differently)
    // if invalid mapname empty map is created/returned
    if ( ! isset( $this->properties[$mapName] ) ) {
        $this->properties[$mapName] = array();
    }    
    $propArray = &$this->properties[$mapName];
    if ( ! isset( $propArray[$itemKey] ) ) {
        $propArray[$itemKey] = new rsm_mapArray;
    }    
    return $propArray[$itemKey];
}

}  // end class

$schoolMap = new rsm_mapArray;
$schoolMap->map_setItem( 'IdWalker' , 'Walker School' );  // or: $schoolMap['IdWalker'] = 'Walker School';

$periodMap = $schoolMap->map_getMap( 'PeriodList' , 'IdWalker' );  // will use existing list if list already exists
$periodMap->map_setItem( 'Id-1' , '1st Period' ); // or: - $periodMap['Id-1'] = '1st Period';
$kidMap = $periodMap->map_createMap( 'KidList', 'Id-1' );
$kidMap->map_setItem( 'Id-Kid1' , 'Alice');      // or: $kidMap['Id-Kid1'] = 'Alice';
$kidMap->map_setItem( 'Id-Kid2' , 'Ben');        // or: $kidMap['Id-Kid2'] = 'Ben';
$kidMap->map_setItem( 'Id-Kid3' , 'Charles');    // or: $kidMap['Id-Kid3'] = 'Charles';
$periodMap = $schoolMap->map_createMap( 'PeriodList' , 'IdWalker' );  // will use existing list if list already exists
$periodMap['Id-2'] = '2nd Period';
$kidMap = $periodMap->map_createMap( 'KidList', 'Id-2'  );
$kidMap->map_setItem( 'Id-Kid1' , 'Rob');      // or:  $kidMap['Id-Kid1'] = 'Rob';
$kidMap->map_setItem( 'Id-Kid2' , 'Sally');    // or:  $kidMap['Id-Kid2'] = 'Sally';
$kidMap->map_setItem( 'Id-Kid3' , 'Tom');      // or:  $kidMap['Id-Kid3'] = 'Tom';

$schoolMap['IdChalker'] = 'Chalker School';
$periodMap = $schoolMap->map_createMap(  'PeriodList' , 'IdChalker'  );
$periodMap->map_setItem( 'Id-1' , '1st Period' );
$periodMap->map_setItem( 'Id-2' , '2nd Period' );

$schoolMap['IdFord'] = 'Ford School';
$periodMap = $schoolMap->map_createMap( 'PeriodList' , 'IdFord'  );
$periodMap->map_setItem( 'Id-1' , '1st Period' );
$periodMap->map_setItem( 'Id-2' , '2nd Period' );

//$schoolMap->uksort(function($$schoolMap->,$b){if ($$schoolMap->==$b) return 0; else if ($$schoolMap->>$b) return 1; else return 0;});

foreach ( $schoolMap as $schoolItemKey => $schoolItem ) {
    echo "<br>{$schoolItem} (Key=$schoolItemKey)";
    $periodMap = $schoolMap->map_getMap('PeriodList', $schoolItemKey );
    foreach ( $periodMap as $periodKey => $periodItem ) {
        echo "<br>.....{$periodItem} (Key=$periodKey)";
        $kidMap = $periodMap->map_getMap('KidList', $periodKey );
        if ( empty($kidMap) ){
            continue;
        }     
        foreach ( $kidMap as $kidItemKey => $kidItem ) {
            echo "<br>.........{$kidItem} (Key=$kidItemKey)";
        }
   }
}

exit;

?>

</body>
</html>

