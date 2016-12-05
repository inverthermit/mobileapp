<?php
namespace App\Traits;
/**
 * CamelCaseAttributes Trait
 *
 * @see http://laravelsnippets.com/snippets/camel-case-attributes
 * @see http://php.net/manual/en/function.array-change-key-case.php
 */

/**
 * to filter the fields by declaring them in $camelKeepFields
 * $camelKeepFields = [iCalUID]
 */
trait CamelCaseAttributes
{

    /**
     * Override the default functionality so we may access attributes via camelCase along with snake case. E.g.,
     *
     * echo $object->first_name;
     * echo $object->firstName;
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if(property_exists($this, 'camelKeepFields') && in_array($key, $this->camelKeepFields)){
            return parent::getAttribute($key);
        }else{
            return parent::getAttribute(snake_case($key));
        }
    }

    /**
     * Override the default functionality so we may set attributes via camelCase along with snake case. E.g.,
     *
     * $object->first_name = "Bob";
     * $object->firstName = "Bob";
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        // filter the fields in camelKeepFields
        if(property_exists($this, 'camelKeepFields') && in_array($key, $this->camelKeepFields)){
            return parent::setAttribute($key, $value);
        }else{
            return parent::setAttribute(snake_case($key), $value);
        }
    }


    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray($case = null)
    {
        $attributes = $this->attributesToArray($case);
        return array_merge($attributes, $this->relationsToArray($case));
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray($case = null)
    {
        $attributes = parent::attributesToArray();
        return $this->array_change_key_case($attributes, $case);
    }

    /**
     * Get the model's relationships in array form.
     *
     * @return array
     */
    public function relationsToArray($case = null)
    {
        $attributes = parent::relationsToArray();
        return $this->array_change_key_case($attributes, $case);
    }

    /**
     * Changes the case of all keys in an array
     *
     * @param $array The array to work on
     * @param $case UPPER|LOWER|CAMEL|SNAKE|STUDLY|UCFIRST
     * @return array $array
     */
    private function array_change_key_case(&$array, $case = null)
    {
        $case = 'CAMEL'; // use camel case here
        foreach ($array as $key => $value) {
            if(property_exists($this, 'camelKeepFields') && in_array($key, $this->camelKeepFields)){
                continue;
            }
            switch (strtoupper($case)) {
                case 'UPPER':
                    $caseKey = strtoupper($key);
                    break;
                case 'LOWER':
                    $caseKey = strtolower($key);
                    break;
                case 'CAMEL':
                    $caseKey = camel_case($key);
                    break;
                case 'SNAKE':
                    $caseKey = snake_case($key);
                    break;
                case 'STUDLY':
                    $caseKey = studly_case($key);
                    break;
                case 'UCFIRST':
                    $caseKey = ucfirst($key);
                    break;
                default:
                    $caseKey = $key;
            }

            // Change key if needed
            if ($caseKey != $key) {
                unset($array[$key]);
                $array[$caseKey] = $value;
                $key = $caseKey;
            }

            // Handle nested arrays
            if (is_array($value)) {
                $this->array_change_key_case($array[$key], $case);
            }
        }
        return $array;
    }
}