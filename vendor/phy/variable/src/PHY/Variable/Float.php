<?php

    /**
     * PHY\Variable
     * https://github.com/mullanaphy/variable
     *
     * LICENSE
     * DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
     * http://www.wtfpl.net/
     */

    namespace PHY\Variable;

    /**
     * Encapsulated Float class.
     *
     * @package PHY\Variable\Float
     * @category PHY\Variable
     * @license http://www.wtfpl.net/
     * @author John Mullanaphy <john@jo.mu>
     */
    class Float extends \PHY\Variable\AVar
    {

        protected $types = [
            'float',
            'double'
        ];
        protected $original = 0.0;
        protected $current = 0.0;

        /**
         * Set the default value and typecase it to a Float.
         *
         * @param numeric $value
         * @return \PHY\Variable\Float
         */
        public function set($value = null)
        {
            return parent::set((float)$value);
        }

    }
