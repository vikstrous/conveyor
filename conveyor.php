<?php

    /**
     * The abstract logic templating engine.
     * @package Conveyor
     * @author Viktor Stanchev (me@viktorstanchev.com)
     * @author Lucas Thoresen
     */

    //TODO: should we do is_vector or not?
    //TODO: naming convention for function creators
    //TODO: should we pass in a function to delete elements or just let the user delete them?

    require("Mustache.php");

    class Conveyor {

        public static function trim($string, $length, $quotes=false, $link = ''){
            if (strlen($string) > $length) {
                $string = ($quotes?'"':'') . substr($string, 0, $length) . '...' . ($quotes?'"':'');
            }
            if ($link) {
                $string .= ' <a href="' . $link . '">(more)</a>';
            }
            return $string;
        }

        public static function is_vector(&$array) { 
           $next = 0; 
           foreach ( $array as $k => $v ) { 
              if ( $k !== $next ) return false;
              $next++; 
           } 
           return true; 
        }

        public static function _pattern_to_regex($pattern){
            $pieces = preg_split("/(?!=\\\\)\\//", $pattern);
            $result = '';
            foreach ($pieces as $key => $value) {
                switch ($value) {
                    case '**':
                        $result .= '\/.+';
                        break;
                    case '*':
                        $result .= '\/[^\/]+';
                        break;
                    default:
                        $result .= '\/' . preg_quote($value);
                }
            }
            if(strlen($pattern) > 0 && $pattern[0] != '/')
                $result = '/' . substr($result, 2) . '$/i';
            else
                $result = '/^' . substr($result, 2) . '$/i';
            return $result;
        }

        private static function _manipulate($logic, &$data, $path){
            if ($path == ''){
                foreach ($logic as $pattern => $function) {
                    if (preg_match(Conveyor::_pattern_to_regex($pattern), '/')) {
                        $should_keep = $function($data, '/');
                        if ($should_keep === false){
                            unset($data);
                            break;
                        }
                    }
                }
            }
            if (isset($data) && is_array($data) && count($data) > 0){
                $vector = Conveyor::is_vector($data);
                $reorder = false;
                foreach ($data as $key => &$value) {
                    $next_path = $path . '/' . str_replace('/', '\/', $key);
                    foreach ($logic as $pattern => $function) {
                        if (preg_match(Conveyor::_pattern_to_regex($pattern), $next_path)) {
                            //matched!
                            $should_keep = $function($value, $next_path);
                            if ($should_keep === false) {
                                unset($data[$key]);
                                $reorder = true;
                                break;
                            }
                        }
                    }
                    if (array_key_exists($key, $data) && is_array($data[$key])){
                        Conveyor::_manipulate($logic, $data[$key], $next_path);
                    }
                }
                if($reorder && $vector)
                    $data = array_values($data);
            }
            if(isset($data))
                return $data;
            else
                return null;
        }

        public static function make_namer($name) {
            //return a function that will name
            return function(&$data, $path) use ($name){
                $data = Conveyor::name($data, $name);
            };
        }

        public static function name($object, $name){
            return array($name => $object);
        }

        public static function make_rowifier($columns, $name = '') {
            //return a function that will rowify
            return function(&$data, $path) use ($columns, $name){
                $data = Conveyor::rowify($data, $columns, $name);
            };
        }

        public static function rowify($data, $columns, $name = ''){
            $new_data = array();
            $dataCount = count($data);
            for ($i = 0; $i < $dataCount; $i += $columns) {
                $accumulate = array();
                for ($j = 0; $j < $columns && $i + $j < $dataCount ; $j++){
                    $accumulate[] = $data[$i + $j];
                }
                if($name != ''){
                    $new_data[] = array($name => $accumulate);
                } else {
                    $new_data[] = $accumulate;
                }
            }
            return $new_data;
        }


        public static function apply($data, $logic){
            if (is_array($logic) && count($logic) > 0) {
                return Conveyor::_manipulate($logic, $data, '');
            } else {
                return Conveyor::_manipulate(array('/' => $logic), $data, '');
            }
        }

        public static function render($template, $data, $logic){
            $mustache = new Mustache();

            if(isset($logic)) $data = Conveyor::apply($data, $logic);

            return $mustache->render($template, $data);
        }
    }
