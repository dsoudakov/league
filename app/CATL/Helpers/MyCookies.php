<?php

namespace CATL\Helpers;

use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Carbon\Carbon;

class MyCookies {

	protected $request;
	protected $response;

	public function __construct($request, $response)
	{
		if ($request && $response) {
			$this->up($request, $response);
		}
	}

	public function up($request, $response)
	{
		$this->request = $request;
		$this->response = $response;	
	}

	public function get($name)
	{
		return FigRequestCookies::get($this->request,$name)->getValue();
	}

	public function set($name, $value, $dt)
	{
		$this->response =  FigResponseCookies::set($this->response, SetCookie::create($name)
		    ->withValue($value)
		    ->withExpires(Carbon::parse($dt)->timestamp)
		);
		return $this->response;
	}

	public function remove($name)
	{
		$this->response = FigResponseCookies::remove($this->response, $name);
		return $this->response;
	}

	public function delete($name)
	{
		$this->response = $this->set($name, '', '-1 week', $this->response);
		return $this->response;
	}

	public function res()
	{
		return $this->response;
	}

	public function req()
	{
		return $this->request;
	}

}