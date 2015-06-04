<?php

    /**
     * PHY\Variable
     * https://github.com/mullanaphy/variable
     *
     * LICENSE
     * DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
     * http://www.wtfpl.net/
     */

    namespace PHY;

    /**
     * Factory/Utility class for creating and converting variables.
     *
     * @package PHY\Variable
     * @category PHY\Variable
     * @license http://www.wtfpl.net/
     * @author John Mullanaphy <john@jo.mu>
     * @abstract
     */
    class Variable
    {

        /**
         * Create a Variable instance based on $variable.
         *
         * @param string $variable
         * @return \PHY\Variable\AVar
         * @throws \PHY\Variable\Exception
         */
        public static function create($variable = 'Obj', $value = null)
        {
            $class = '\\PHY\\Variable\\'.$variable;
            if (class_exists($class)) {
                return new $class($value);
            } else {
                throw new \PHY\Variable\Exception('Could not find variable type of "'.$variable.'"');
            }
        }

        /**
         * Convert a variable to a different type.
         *
         * @param \PHY\Variable\AVar $variable
         * @param string $convertTo
         * @return \PHY\Variable\AVar
         */
        public static function convertVariableTo(\PHY\Variable\AVar $variable, $convertTo = 'Obj')
        {
            $method = 'to'.$convertTo;
            if (!is_callable([$variable, $method])) {
                throw new \PHY\Variable\Exception('Could not convert "'.$variable->getType().'" to "'.$convertTo.'"');
            }
            $value = $variable->{$method}();
            return static::create($convertTo, $value);
        }

    }
