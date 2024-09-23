<?php

namespace Analog\Handler;

class PDO {
	public static function init ($pdo, $table) {
		if (is_array ($pdo)) {
			$pdo = new \PDO ($pdo[0], $pdo[1], $pdo[2], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
		}
		
		$stmt = $pdo->prepare (
			'insert into `' . $table . '` (`machine`, `date`, `level`, `message`) values (:machine, :date, :level, :message)'
		);
		
		return function ($info) use ($stmt, $table) {
			$stmt->execute ($info);
		};
	}
	
	public static function createTable ($pdo, $table) {
		if (is_array ($pdo)) {
			$pdo = new \PDO ($pdo[0], $pdo[1], $pdo[2]);
		}
		
		$pdo->beginTransaction ();
		
		$pdo->prepare (
			'create table `' . $table . '` (`machine` varchar(48), `date` datetime, `level` int, `message` text)'
		)->execute ();
	
		$pdo->prepare (
			'create index `' . $table . '_message` on `' . $table . '` (`machine`)'
		)->execute ();
	
		$pdo->prepare (
			'create index `' . $table . '_date` on `' . $table . '` (`date`)'
		)->execute ();
	
		$pdo->prepare (
			'create index `' . $table . '_level` on `' . $table . '` (`level`)'
		)->execute ();
		
		$pdo->commit ();
	}
}
