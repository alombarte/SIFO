<?php
/**
 * LICENSE
 *
 * Copyright 2010 Albert Garcia
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

namespace Sifo;

class Search
{
	static protected $instance;

	static public $search_engine;

	protected $sphinx;

	protected $sphinx_config;

	/**
	 * Initializes the class.
	 */
	protected function __construct()
	{
		$this->sphinx_config = $this->getConnectionParams();

		// Check if Sphinx is enabled by configuration:
		if ( true === $this->sphinx_config['active'] )
		{
			include_once ROOT_PATH . '/libs/'.Config::getInstance()->getLibrary( 'sphinx' ) . '/sphinxapi.php';

			self::$search_engine 	= 'Sphinx';
			$this->sphinx 			= new \SphinxClient();
			$this->sphinx->SetServer( $this->sphinx_config['server'], $this->sphinx_config['port'] );

			// If it's defined a time out connection in config file:
			if( isset( $this->sphinx_config['time_out'] ) )
			{
				$this->sphinx->SetConnectTimeout( $this->sphinx_config['time_out'] );
			}

			// Check if Sphinx is listening:
			if ( true !== $this->sphinx->Open() )
			{
				throw new \Sifo\Exception_500( 'Sphinx (' . $this->sphinx_config['server'] . ':' . $this->sphinx_config['port'] . ') is down!' );
			}
		}

		return $this->sphinx_config;
	}

	/**
	 * Singleton of config class.
	 *
	 * @param string $instance_name Instance Name, needed to determine correct paths.
	 * @return object Config
	 */
	public static function getInstance()
	{
		if ( !isset ( self::$instance ) )
		{
			if ( Domains::getInstance()->getDebugMode() !== true )
			{
				self::$instance = new Search;
			}
			else
			{
				self::$instance = new DebugSearch;
			}
		}

		return self::$instance;
	}

	/**
	 * Get Sphinx connection params from config files.
	 *
	 * @return array
	 * @throws Exception_500|Exception_Configuration
	 */
	protected function getConnectionParams()
	{
		// The domains.config file has priority, let's fetch it.
		$sphinx_config = \Sifo\Domains::getInstance()->getParam( 'sphinx' );

		if ( empty( $sphinx_config ) )
		{
			try
			{
				// If the domains.config doesn't define the params, we use the sphinx.config.
				$sphinx_config = Config::getInstance()->getConfig( 'sphinx' );
				$sphinx_config['config_file'] = 'sphinx';
			}
			catch ( Exception_Configuration $e )
			{
				throw new Exception_500( 'You must define the connection params in sphinx.config or domains.config file' );
			}
		}
		else
		{
			$sphinx_config['config_file'] = 'domains';
		}

		return $sphinx_config;
	}

	/**
	 * Delegate all calls to the proper class.
	 *
	 * @param string $method
	 * @param mixed $args
	 * @return mixed
	 */
	function __call( $method, $args )
	{
		if ( is_object( $this->sphinx ) )
		{
			return call_user_func_array( array( $this->sphinx, $method ), $args );
		}
		return null;
	}
}