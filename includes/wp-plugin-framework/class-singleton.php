<?php
/**
 * Class Singleton.
 *   Only allows one instace of the class.
 *   To be used as a parent class. Ex. "class MyClass extends Singleton {}"
 */

/** Our namespace. */
namespace WPPluginFramework;

/**
 * Class Singleton.
 */
class Singleton {
	/**
	 * Current instance.
	 * Many classes can extend this class, so we store one instance for each class.
	 *
	 * @var Singleton
	 */
	protected static $instances = [];

	/**
	 * Get the current instance.
	 *
	 * @return Singleton
	 */
	public static function get_instance() {
		$klass = static::class;
		if ( ! isset( static::$instances[ $klass ] ) ) {
			static::$instances[ $klass ] = new static();
		}
		return static::$instances[ $klass ];
	}
}
