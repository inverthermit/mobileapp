<?php


/**
 * 
 */
function humanFilesize($bytes, $decimals = 2){
    $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB'];
    $factor = floor((strlen($bytes) - 1) / 3);

    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) .@$size[$factor];
}

/**
 * 
 */
function isImage($mimeType){
    return starts_with($mimeType, 'image/');
}

/**
 * snake style eloquent list to camel style list
 * @param $class, e.g. Message::class
 * @param $msgList
 */
function snakeToCamelList($class, $itemList){
    $newItemList = [];
    foreach($itemList as $item){
        $newItem = new $class;
        foreach($item as $key => $value){
            $newItem->$key = $value;
        }
        array_push($newItemList, $newItem);
    }
    return $newItemList;
}

