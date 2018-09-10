<?php
namespace library\helper;
/**
 * 日期函数操作类
 *
 * @author Hjj
 */
class DateTimeHelper extends HelperBasic {
	/**
	 * 获得当前毫秒数
	 */
	public static function getMSec() {
		list($s1, $s2) = explode(' ', microtime());
		return substr($s1, 2, 3);
	}

	/**
	 * 获得当前时间，精确到毫秒
	 */
	public static function getDataTime() {
		return date('Y-m-d H:i:s.') . self::getMSec();
	}

	/**
	 * 获得LINUX时间戳，精确到毫秒
	 *
	 * @return string
	 */
	public static function getTimespan() {
		return time() . self::getMSec();
	}

	/**
	 * 将时间戳转成日期
	 *
	 * @param string $timespan 当前的时间戳（支持13位[毫秒]、10位[标准]）
	 * @return string 当前日期（精确到毫秒，毫秒值默认000）
	 */
	public static function convertToDateTime($timespan) {
		if (empty ($timespan)) {
			return '';
		}

		list ($s1, $s2) = str_split($timespan, 10);
		if (!$s2) {
			$s2 = '000';
		}

		return date('Y-m-d H:i:s.', $s1) . $s2;
	}

	/**
	 * 将日期转成LINUX时间戳（精确到毫秒）
	 *
	 * @param string $datetime 当前日期（支持标准时间[yyyy-MM-dd HH:mm:ss]、精确到毫秒的时间[yyyy-MM-dd HH:mm:ss.iii]）
	 * @return string 日期转换成的时间戳（精确到毫秒、毫秒值默认000）
	 */
	public static function convertToTimespan($datetime) {
		if (empty ($datetime)) {
			return '0';
		}

		list ($s1, $s2) = explode('.', $datetime);
		if (!$s2) {
			$s2 = '000';
		}

		return strtotime($s1) . $s2;
	}

	/**
	 * 将日期转换为 时间戳（单位：秒）
	 *
	 * @param string $datetime
	 * @return int
	 */
	public static function convertToTimestamp($datetime) {
		if (empty ($datetime)) {
			return '0';
		}
		list ($s1, $s2) = explode('.', $datetime);

		return strtotime($s1);
	}

	/**
	 * 获取时间戳
	 *
	 * @param string $start_time
	 * @param string $end_time
	 * @return string
	 */
	public static function getTimespanEquation($start_time, $end_time) {
		if (empty ($start_time)) {
			return 0;
		}

		if (empty ($end_time)) {
			$end_time = time();
		}

		// 1，检查时间是日期，还是timespan 2，过滤掉毫秒 3，最终转换成timespan
		$timespan1 = self::_getTimespanByTime($start_time);
		$timespan2 = self::_getTimespanByTime($end_time);
		$t = abs($timespan2 - $timespan1);

		return $t;
	}

	/**
	 * 计算时间差
	 *
	 * @param string $start_time 开始时间（支持日期、时间戳、精确到毫秒的时间戳）
	 * @param string $end_time   结束时间（支持日期、时间戳、精确到毫秒的时间戳）
	 * @return string 时间差
	 */
	public static function getTimeEquation($start_time, $end_time) {
		if (empty ($start_time)) {
			return '00:00:00';
		}

		if (empty ($end_time)) {
			$end_time = time();
		}

		// 1，检查时间是日期，还是timespan 2，过滤掉毫秒 3，最终转换成timespan
		$timespan1 = self::_getTimespanByTime($start_time);
		$timespan2 = self::_getTimespanByTime($end_time);
		$t = abs($timespan2 - $timespan1);

		// 如果不到一天
		if ($t < 86400) {
			$format_time = gmstrftime('%H:%M:%S', $t);
		} else {
			$time = explode(' ', gmstrftime('%j %H %M %S', $t));
			$format_time = (($time [0] - 1) * 24 + $time [1]) . ':' . $time [2] . ':' . $time [3];
		}

		return $format_time;
	}

	/**
	 * 获得时间差并格式化
	 *
	 * @param string $start_time
	 * @param string $end_time
	 * @return string
	 */
	public static function getFormatTimeEquation($start_time, $end_time) {
		return self::formatTime(self::getTimeEquation($start_time, $end_time));
	}

	/**
	 * 根据时间戳格式化输出时间差
	 *
	 * @param string $timespan
	 * @return string
	 */
	public static function formatTimeForTimespan($timespan) {
		if (empty ($timespan)) {
			return '未知';
		}

		$span1 = time() - $timespan;
		$span2 = time() - strtotime(date('Y-m-d'));
		if ($span1 > 86400 || $span1 <= 0) {
			return date('m/d H:i', $timespan);
		} elseif ($span1 > 18000) {
			if ($span2 < $span1) {
				return '昨天';
			} else {
				return '今天';
			}
		} elseif ($span1 > 14400) {
			return '4小时前';
		} elseif ($span1 > 10800) {
			return '3小时前';
		} elseif ($span1 > 7200) { // 2小时
			return '2小时前';
		} elseif ($span1 > 3600) { // 一小时
			return '1小时前';
		} elseif ($span1 > 1800) { // 半小小时
			return '半小时前';
		} elseif ($span1 > 600) { // 10分钟
			return '10分钟前';
		} else {
			return '刚刚';
		}
	}

	/**
	 * 格式化时间输出
	 *
	 * @param string $time 时间（支持日期、时间戳、精确到毫秒的时间戳）
	 * @return string
	 */
	public static function formatTime($time) {
		// 1，检查时间是日期，还是timespan 2，过滤掉毫秒 3，最终转换成timespan
		$timespan = self::_getTimespanByTime($time);
		$span1 = time() - $timespan;
		$span2 = time() - strtotime(date('Y-m-d'));

		if ($span1 > 86400) {
			return date('m/d H:i', $timespan);
		} elseif ($span1 > 18000) {
			if ($span2 < $span1) {
				return '昨天';
			} else {
				return '今天';
			}
		} elseif ($span1 > 14400) {
			return '4小时前';
		} elseif ($span1 > 10800) {
			return '3小时前';
		} elseif ($span1 > 7200) { // 2小时
			return '2小时前';
		} elseif ($span1 > 3600) { // 一小时
			return '1小时前';
		} elseif ($span1 > 1800) { // 半小小时
			return '半小时前';
		} elseif ($span1 > 600) { // 10分钟
			return '10分钟前';
		} else {
			return '刚刚';
		}
	}

	/**
	 * 将任意时间转成Timespan
	 *
	 * @param int $time 时间（支持日期、时间戳、精确到毫秒的时间戳）
	 * @return int <unknown, number>
	 */
	private static function _getTimespanByTime($time) {
		if (strpos($time, '-')) {
			$explore = explode('.', $time);
			$timespan = strtotime($explore[0]);
		} else {
			if (strlen($time) == 13) {
				$timespan = substr($time, 0, 10);
			} else {
				$timespan = $time;
			}
		}

		return $timespan;
	}

	/**
	 * 将任意时间转成时间戳
	 *
	 * @param string $time 时间（支持日期、时间戳、精确到毫秒的时间戳）
	 * @return string <unknown, number>
	 */
	private static function _getDateTimeByTime($time) {
		$timespan = self::_getDateTimeByTime($time);
		return date('Y-m-d H:i:s', $timespan);
	}

	/**
	 * 计算倒计时
	 *
	 * @param string $startTime 开始时间，如 2015-10-22 10:03:59.128
	 * @return array
	 */
	public static function getCountDownTime($startTime) {
		$second = self::convertToTimestamp($startTime) - time();
		$second = $second > 0 ? $second : 0;
		if (empty($second)) {
			return array(
				'day' => 0,
				'hour' => 0,
				'minute' => 0,
				'second' => 0
			);
		}

		$minute = 0;	// 分
		$hour = 0;		// 小时
		$day = 0;		// 天

		if ($second > 60) {
			$minute = ($second / 60);
			$second = ($second % 60);
			if ($minute > 60) {
				$hour = ($minute / 60);
				$minute = ($minute % 60);
				if ($hour > 24) {
					$day = ($hour / 24);
				}
			}
		}

		$second = floor($second);
		$minute = floor($minute);
		$hour = floor($hour % 24);
		$day = floor($day);

		return array(
			'day' => $day,
			'hour' => $hour,
			'minute' => $minute,
			'second' => $second
		);
	}
}