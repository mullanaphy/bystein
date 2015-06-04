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
     * Encapsulated Object class.
     *
     * @package PHY\Variable\Obj
     * @category PHY\Variable
     * @license http://www.wtfpl.net/
     * @author John Mullanaphy <john@jo.mu>
     */
    class Obj extends \PHY\Variable\AVar
    {

        protected $types = [
            'object',
            'stdClass',
            '\stdClass'
        ];

        /**
         * Start working with an Object.
         *
         * @param \stdClass $value
         */
        public function __construct($value = null)
        {
            $this->original = new \stdClass;
            $this->current = new \stdClass;
            parent::__construct($value);
        }

        /**
         * Clone an object.
         *
         * @return \PHY\Variable\Obj
         */
        public function copy()
        {
            $value = $this->get();
            return clone $value;
        }

        /**
         * {@inheritDoc}
         */
        public function chain($value = null)
        {
            if (null !== $value) {
                $this->validate($value);
                $this->set($value);
            }
            $this->chaining = true;
            $this->chain = clone $this->current;
            return $this;
        }

        /**
         * {@inheritDoc}
         */
        public function toArr()
        {
            $class = $this->get();
            $class = new \PHY\Variable\Arr((array)$class);
            foreach ($class as $key => $value) {
                if (is_object($value)) {
                    $class[$key] = (new static($value))->toArr();
                }
            }
            return $class;
        }

        /**
         * Recursively convert our object into an array.
         *
         * @return array
         */
        public function toArray()
        {
            $class = $this->get();
            $class = (array)$class;
            foreach ($class as $key => $value) {
                if (is_object($value)) {
                    $class[$key] = (new static($value))->toArray();
                }
            }
            return $class;
        }

        /**
         * {@inheritDoc}
         */
        public function toStr()
        {
            return new \PHY\Variable\Str(json_encode($this->get()));
        }

    }
