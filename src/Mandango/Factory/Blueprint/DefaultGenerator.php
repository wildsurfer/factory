<?php
namespace Mandango\Factory\Blueprint;
use Mandango\Factory\Factory;
use Mandango\Factory\Blueprint;
use Faker\Generator;


final class DefaultGenerator {
    static public function integer(Factory $factory, $name, array $config) 
    {
        return function($sequence = null) use ($factory, $name, $config) {
            if ( $config['value'] === null ) $config['value'] = '###';
            return (int)$factory->getFaker()->numerify($config['value']);
        };
    }

    static public function float(Factory $factory, $name, array $config) 
    {
        return function($sequence = null) use ($factory, $name, $config) {
            if ( is_numeric($config['value']) ) return $config['value'];
            return (float)$factory->getFaker()->numerify($config['value'])/100;
        };
    }    

    static public function string(Factory $factory, $name, array $config) 
    {
        if ( $config['value'] === null ) $config['value'] = 'faker::sentence(6)';
        return function($sequence = null) use ($factory, $name, $config) {
            $string = static::generate($factory->getFaker(), $config['value']);
            return sprintf($string, $sequence);
        };
    }       
   
    static public function boolean(Factory $factory, $name, array $config) 
    {
        return function($sequence = null) use ($factory, $name, $config) {
            if ( $config['value'] !== null ) return $config['value'];
            return $factory->getFaker()->randomElement(array(true, false));
        };
    }   

    static public function date(Factory $factory, $name, array $config) 
    {
        return function($sequence = null) use ($factory, $name, $config) {
            $value = $config['value'];
            if ( !$value ) $timestamp = time();
            else if ( is_integer($value) || is_float($value) ) $timestamp = $value;
            else {
                $generated = static::generate($factory->getFaker(), $config['value']);
                if ( $generated == $config['value'] ) {
                    $timestamp = strtotime($generated);
                } else {
                    if ( !$generated instanceOf \DateTime ) {
                        throw new \InvalidArgumentException(
                            'Unexpected faker method, must return a DateTime object'
                        );
                    }
                    
                    return $generated;
                }
            }

            $date = new \DateTime();
            $date->setTimestamp($timestamp);
            return $date;
        };
    }    

    static public function embeddedsOne(Factory $factory, $name, array $config) 
    {
        return function($sequence = null) use ($factory, $name, $config) {
            return static::embedded($factory, $config['class'], $config['value']);
        };
    }    

    static public function embeddedsMany(Factory $factory, $name, array $config) 
    {
        return function($sequence = null) use ($factory, $name, $config) {
            $value = $config['value'];
            $documents = array();

            if ( !$value ) {
                $documents[] = static::embedded($factory, $config['class']);
            } else if ( is_numeric($value) ) {
                for($i=0;$i<(int)$value;$i++) {
                    $documents[] = static::embedded($factory, $config['class']);
                }
            } else if ( is_array($value) ) {
                foreach($value as $default) {
                    $documents[] = static::embedded($factory, $config['class'], $default);
                }
            } else {
                throw new \InvalidArgumentException(
                    'Unexpected default value for embeddedsMany field'
                );
            }

            return $documents;
        };
    }    

    static public function referencesOne(Factory $factory, $name, array $config) 
    {
        return function($sequence = null) use ($factory, $name, $config) {
            return static::reference($config['value']);
        };
    }    

    static public function referencesMany(Factory $factory, $name, array $config) 
    {
        return function($sequence = null) use ($factory, $name, $config) {
            $value = $config['value'];
            $ids = array();

            if ( !$value ) {
                $ids[] = static::reference();
            } else if ( is_numeric($value) ) {
                for($i=0;$i<(int)$value;$i++) $ids[] = static::reference();
            } else if ( is_array($value) ) {
                foreach($value as $id) $ids[] = static::reference($id);
            } else {
                throw new \InvalidArgumentException(
                    'Unexpected default value for referencesMany field'
                );
            }

            return $ids;
        };
    }   

    static private function embedded(Factory $factory, $class, $value = null) 
    {
        if ( !$value ) $value = array();
        else if ( $value instanceOf $class ) return $value->toArray();

        $bp = new Blueprint($factory, $class);
        return $bp->build($value);
    }

    static private function reference($value = null) 
    {
        if ( !$value ) return new \MongoId();
        else if ( $value instanceOf \MongoId ) return $value;
        else {
            return new \MongoId($value);
        }
    }

    static private function generate(Generator $faker, $string) 
    {
        preg_match('/^faker::([a-zA-Z]*)\(?([a-zA-Z0-9 ,#\?\-\:]*)\)?/', $string, $results);
        if ( count($results) == 0 ) return $string;
        else if ( count($results) == 2 ) return $faker->$results[1];
        else if ( count($results) == 3 ) {
            return call_user_func_array(
                array($faker, $results[1]), 
                explode(',', $results[2])
            );
        }
    }
}