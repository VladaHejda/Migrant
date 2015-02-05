<?php

namespace VladaHejda\Migrant;

interface IStorage
{

	/**
	 * @param string $var
	 * @return array
	 */
	function getList($var);


	/**
	 * @param string $var
	 * @param string $val
	 * @return void
	 */
	function listAppend($var, $val);


	/**
	 * @param string $var
	 * @return bool
	 */
	function is($var);


	/**
	 * @param string $var
	 * @return void
	 */
	function on($var);


	/**
	 * @param string $var
	 * @return void
	 */
	function off($var);

}
