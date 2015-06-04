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
     * NULL everywhere! Used for references that need to be NULL.
     *
     * @package PHY\Variable\Nil
     * @category PHY\Variable
     * @license http://www.wtfpl.net/
     * @author John Mullanaphy <john@jo.mu>
     * @ignore
     */
    class Nil extends \PHY\Variable\AVar
    {

        protected $types = [
            'null'
        ];
        protected $original = null;
        protected $current = null;

        /**
         * Always a zero.
         *
         * @return int
         */
        public function toInt()
        {
            return 0;
        }

        /**
         * Always zero as a float.
         *
         * @return float
         */
        public function toFloat()
        {
            return 0.0;
        }

        /**
         * Always an empty array.
         *
         * @return array
         */
        public function toArr()
        {
            return [];
        }

        /**
         * Always false.
         *
         * @return boolean
         */
        public function toBool()
        {
            return false;
        }

        /**
         * Always an empty object.
         *
         * @return \stdClass
         */
        public function toObj()
        {
            return new \stdClass();
        }

        /**
         * Always an empty string.
         *
         * @return string
         */
        public function toStr()
        {
            return '';
        }

    }
