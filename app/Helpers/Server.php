<?php

namespace App\Helpers;

use Carbon\Carbon;


class Server
{

	public static function getDate()
	{
		$data = date('Y-m-d');
		return Carbon::createFromTimestamp(strtotime($data))
			->timezone(Config('app.timezone'))
			->toDateString();
	}



	public static function getDateTime()
	{
		$data = date('Y-m-d H:i:s');
		return Carbon::createFromTimestamp(strtotime($data))
			->timezone(Config('app.timezone'))
			->toDateTimeString();
	}

	public static function getTime()
	{
		$data = date('H:i:s');
		return Carbon::createFromTimestamp(strtotime($data))
			->timezone(Config('app.timezone'))
			->toTimeString();
	}

	public static function setDate($date = null)
	{
		$data = ($date == null) ? date('Y-m-d') : $date;

		$newDate = '';

		if (Config('server.date_format') == "DD MM YYYY") {
			$newDate = date("d-m-Y", strtotime($date));
		} else if (Config('server.date_format') == "MM DD YYYY") {
			$newDate = date("m-d-Y", strtotime($date));
		} else {
			$newDate = date("Y-m-d", strtotime($date));
		}

		if (Config('server.date_separator') == "/") {
			$newDate = str_replace('-', Config('server.date_separator'), $newDate);
		}

		return $newDate;
	}

	public static function setDateTime($date = null)
	{
		$data = ($date == null) ? date('Y-m-d H:i:s') : $date;

		$time = date("H:i:s", strtotime($data));

		$newDate = '';
		$newTime = '';

		if (Config('server.date_format') == "DD MM YYYY") {
			$newDate = date("d-m-Y", strtotime($date));
		} else if (Config('server.date_format') == "MM DD YYYY") {
			$newDate = date("m-d-Y", strtotime($date));
		} else {
			$newDate = date("Y-m-d", strtotime($date));
		}

		if (Config('server.date_separator') == "/") {
			$newDate = str_replace('-', Config('server.date_separator'), $newDate);
		}

		if (Config('server.time_format') == "12") {
			$newTime = date("h:i:s A", strtotime($time));
		} else {
			$newTime = date("H:i:s", strtotime($time));
		}

		return $newDate . ' ' . $newTime;
	}

	public static function setTime($date = null)
	{
		$time = ($date == null) ? date('H:i:s') : $date;

		$newTime = '';

		if (Config('server.time_format') == "12") {
			$newTime = date("h:i:s A", strtotime($time));
		} else {
			$newTime = date("H:i:s", strtotime($time));
		}

		return $newTime;
	}
}
