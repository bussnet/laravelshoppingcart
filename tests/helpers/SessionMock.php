<?php
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 1/12/2015
 * Time: 10:23 PM
 */
class SessionMock implements SessionInterface{

	protected $session = array();

	public function has($key) {
		return isset($this->session[$key]);
	}

	/**
	 * Returns an attribute.
	 *
	 * @param string $name The attribute name
	 * @param mixed $default The default value if not found
	 *
	 * @return mixed
	 */
	public function get($name, $default = null) {
		return (isset($this->session[$name])) ? $this->session[$name] : null;
	}

	public function put($key, $value) {
		$this->session[$key] = $value;
	}
	
	/**
	 * Starts the session storage.
	 *
	 * @return bool True if session started
	 *
	 * @throws \RuntimeException If session fails to start.
	 */
	public function start() {
		// TODO: Implement start() method.
	}

	/**
	 * Returns the session ID.
	 *
	 * @return string The session ID
	 */
	public function getId() {
		// TODO: Implement getId() method.
	}

	/**
	 * Sets the session ID.
	 *
	 * @param string $id
	 */
	public function setId($id) {
		// TODO: Implement setId() method.
	}

	/**
	 * Returns the session name.
	 *
	 * @return mixed The session name
	 */
	public function getName() {
		// TODO: Implement getName() method.
	}

	/**
	 * Sets the session name.
	 *
	 * @param string $name
	 */
	public function setName($name) {
		// TODO: Implement setName() method.
	}

	/**
	 * Invalidates the current session.
	 *
	 * Clears all session attributes and flashes and regenerates the
	 * session and deletes the old session from persistence.
	 *
	 * @param int $lifetime Sets the cookie lifetime for the session cookie. A null value
	 *                      will leave the system settings unchanged, 0 sets the cookie
	 *                      to expire with browser session. Time is in seconds, and is
	 *                      not a Unix timestamp.
	 *
	 * @return bool True if session invalidated, false if error
	 */
	public function invalidate($lifetime = null) {
		// TODO: Implement invalidate() method.
	}

	/**
	 * Migrates the current session to a new session id while maintaining all
	 * session attributes.
	 *
	 * @param bool $destroy Whether to delete the old session or leave it to garbage collection
	 * @param int $lifetime Sets the cookie lifetime for the session cookie. A null value
	 *                       will leave the system settings unchanged, 0 sets the cookie
	 *                       to expire with browser session. Time is in seconds, and is
	 *                       not a Unix timestamp.
	 *
	 * @return bool True if session migrated, false if error
	 */
	public function migrate($destroy = false, $lifetime = null) {
		// TODO: Implement migrate() method.
	}

	/**
	 * Force the session to be saved and closed.
	 *
	 * This method is generally not required for real sessions as
	 * the session will be automatically saved at the end of
	 * code execution.
	 */
	public function save() {
		// TODO: Implement save() method.
	}

	/**
	 * Sets an attribute.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function set($name, $value) {
		// TODO: Implement set() method.
	}

	/**
	 * Returns attributes.
	 *
	 * @return array Attributes
	 */
	public function all() {
		// TODO: Implement all() method.
	}

	/**
	 * Sets attributes.
	 *
	 * @param array $attributes Attributes
	 */
	public function replace(array $attributes) {
		// TODO: Implement replace() method.
	}

	/**
	 * Removes an attribute.
	 *
	 * @param string $name
	 *
	 * @return mixed The removed value or null when it does not exist
	 */
	public function remove($name) {
		// TODO: Implement remove() method.
	}

	/**
	 * Clears all attributes.
	 */
	public function clear() {
		// TODO: Implement clear() method.
	}

	/**
	 * Checks if the session was started.
	 *
	 * @return bool
	 */
	public function isStarted() {
		// TODO: Implement isStarted() method.
	}

	/**
	 * Registers a SessionBagInterface with the session.
	 *
	 * @param \Symfony\Component\HttpFoundation\Session\SessionBagInterface $bag
	 */
	public function registerBag(\Symfony\Component\HttpFoundation\Session\SessionBagInterface $bag) {
		// TODO: Implement registerBag() method.
	}

	/**
	 * Gets a bag instance by name.
	 *
	 * @param string $name
	 *
	 * @return \Symfony\Component\HttpFoundation\Session\SessionBagInterface
	 */
	public function getBag($name) {
		// TODO: Implement getBag() method.
	}

	/**
	 * Gets session meta.
	 *
	 * @return \Symfony\Component\HttpFoundation\Session\Storage\MetadataBag
	 */
	public function getMetadataBag() {
		// TODO: Implement getMetadataBag() method.
	}


}