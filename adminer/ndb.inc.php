<?php
// Create connection
@ini_set('date.timezone', 'Europe/Prague');
define('NETTE_DIR', __DIR__ . '/../nette/Nette/');
include __DIR__ . '/../nette/Nette/common/exceptions.php';
include __DIR__ . '/../nette/Nette/common/Object.php';
include __DIR__ . '/../nette/Nette/Utils/LimitedScope.php';
include __DIR__ . '/../nette/Nette/Loaders/AutoLoader.php';
include __DIR__ . '/../nette/Nette/Loaders/NetteLoader.php';

Nette\Loaders\NetteLoader::getInstance()->register();

$cred = $adminer->credentials();
$db = new \Nette\Database\Connection("sqlsrv:server=$cred[0];Database=" . DB, $cred[1], $cred[2]);
$db->setDatabaseReflection(new \Nette\Database\Reflection\DiscoveredReflection());


if (!$error && $_POST["export"]) {
	dump_headers("sql");
	$adminer->dumpTable("", "");
	$adminer->dumpData("", "table", $_POST["query"]);
	exit;
}

restart_session();
$history_all = &get_session("nettedb-queries");
$history = &$history_all[DB];
if (!$error && $_POST["clear"]) {
	$history = array();
	redirect(remove_from_uri("history"));
}

$codemirror_path = "../externals/CodeMirror2";
$codemirror_mode = ($jush == "sql" ? "mysql" : "plsql");

page_header(lang('Nette Database query'), $error, array(), "", "<link rel='stylesheet' href='$codemirror_path/lib/codemirror.css'>");

if (!$error && $_POST) {
	// $fp = false;
	$query = $_POST["query"];
	// if ($query) {
		$query = eval("return ${query}->getSql();");
		// if (function_exists('memory_get_usage')) {
		// 	@ini_set("memory_limit", max(ini_bytes("memory_limit"), 2 * strlen($query) + memory_get_usage() + 8e6)); // @ - may be disabled, 2 - substr and trim, 8e6 - other variables
		// }
//		if ($query != "" && strlen($query) < 1e6) { // don't add big queries
//			$q = $query . (ereg(";[ \t\r\n]*\$", $query) ? "" : ";"); //! doesn't work with DELIMITER |
//			if (!$history || reset(end($history)) != $q) { // no repeated queries
//				$history[] = array($q, time());
//			}
//		}
		$space = "(?:\\s|/\\*.*\\*/|(?:#|-- )[^\n]*\n|--\n)";
		if (!ini_bool("session.use_cookies")) {
			session_write_close();
		}
//		$delimiter = ";";
		$offset = 0;
		$empty = true;
		// $connection2 = connect(); // connection for exploring indexes and EXPLAIN (to not replace FOUND_ROWS()) //! PDO - silent error
		// if (is_object($connection2) && DB != "") {
		// 	$connection2->select_db(DB);
		// }
//		$commands = 0;
		$errors = array();
		$error_lines = array();
//		$line = 0;
//		$parse = '[\'"' . ($jush == "sql" ? '`#' : ($jush == "sqlite" ? '`[' : ($jush == "mssql" ? '[' : ''))) . ']|/\\*|-- |$' . ($jush == "pgsql" ? '|\\$[^$]*\\$' : '');
		$total_start = microtime();
		parse_str($_COOKIE["adminer_export"], $adminer_export);
		$dump_format = $adminer->dumpFormat();
		unset($dump_format["sql"]);
//		while ($query != "") {
		do {
//			if (!$offset && preg_match("~^$space*DELIMITER\\s+(.+)~i", $query, $match)) {
//				$delimiter = $match[1];
//				$query = substr($query, strlen($match[0]));
//			} else
			{
//				preg_match('(' . preg_quote($delimiter) . "\\s*|$parse)", $query, $match, PREG_OFFSET_CAPTURE, $offset); // should always match
//				list($found, $pos) = $match[0];
//				if (!$found && $fp && !feof($fp)) {
//					$query .= fread($fp, 1e5);
//				} else
				{
//					$offset = $pos + strlen($found);
//					if (!$found && rtrim($query) == "") {
//						break;
//					}
//					if ($found && rtrim($found) != $delimiter) { // find matching quote or comment end
//						while (preg_match('(' . ($found == '/*' ? '\\*/' : ($found == '[' ? ']' : (ereg('^-- |^#', $found) ? "\n" : preg_quote($found) . "|\\\\."))) . '|$)s', $query, $match, PREG_OFFSET_CAPTURE, $offset)) { //! respect sql_mode NO_BACKSLASH_ESCAPES
//							$s = $match[0][0];
//							if (!$s && $fp && !feof($fp)) {
//								$query .= fread($fp, 1e5);
//							} else {
//								$offset = $match[0][1] + strlen($s);
//								if ($s[0] != "\\") {
//									break;
//								}
//							}
//						}
//					} else
					{ // end of a query
						$empty = false;
						$q = $query; // substr($query, 0, $pos);
//						$commands++;
						var_dump(1);
						$print = "<pre id='sql'><code class='jush-$jush'>" . shorten_utf8(trim($q), 1000) . "</code></pre>\n";
//						if (!$_POST["only_errors"]) {
							echo $print;
							ob_flush();
							flush(); // can take a long time - show the running query
//						}
						$start = microtime(); // microtime(true) is available since PHP 5
						//! don't allow changing of character_set_results, convert encoding of displayed query
						if ($connection->multi_query($q) && is_object($connection2) && preg_match("~^$space*USE\\b~isU", $q)) {
							// $connection2->query($q);
						}
						do {
							$result = $connection->store_result();
							$end = microtime();
							$time = format_time($start, $end) . (strlen($q) < 1000 ? " <a href='" . h(ME) . "sql=" . urlencode(trim($q)) . "'>" . lang('Edit') . "</a>" : ""); // 1000 - maximum length of encoded URL in IE is 2083 characters
							if ($connection->error) {
								echo ($_POST["only_errors"] ? $print : "");
								echo "<p class='error'>" . lang('Error in query') . ": " . error() . "\n";
								$error_lines[] = $line + (function_exists('error_line') ? error_line() : 0);
								$errors[] = " <a href='#sql-$commands'>$commands</a>";
								if ($_POST["error_stops"]) {
									break 2;
								}
							} elseif (is_object($result)) {
								//$orgtables = select($result, $connection2);
								if (!$_POST["only_errors"]) {
									echo "<form action='' method='post'>\n";
									echo "<p>" . ($result->num_rows ? lang('%d row(s)', $result->num_rows) : "") . $time;
									$id = "export";
									$export = ", <a href='#$id' onclick=\"return !toggle('$id');\">" . lang('Export') . "</a><span id='$id' class='hidden'>: "
										. html_select("output", $adminer->dumpOutput(), $adminer_export["output"]) . " "
										. html_select("format", $dump_format, $adminer_export["format"])
										. "<input type='hidden' name='query' value='" . h($q) . "'>"
										. " <input type='submit' name='export' value='" . lang('Export') . "' onclick='eventStop(event);'><input type='hidden' name='token' value='$token'></span>\n"
									;
									// if ($connection2 && preg_match("~^($space|\\()*SELECT\\b~isU", $q) && ($explain = explain($connection2, $q))) {
									// 	$id = "explain";
									// 	echo ", <a href='#$id' onclick=\"return !toggle('$id');\">EXPLAIN</a>$export";
									// 	echo "<div id='$id' class='hidden'>\n";
									// 	select($explain, $connection2, ($jush == "sql" ? "http://dev.mysql.com/doc/refman/" . substr($connection->server_info, 0, 3) . "/en/explain-output.html#explain_" : ""), $orgtables);
									// 	echo "</div>\n";
									// } else {
									// 	echo $export;
									// }
									echo "</form>\n";
								}
							} else {
								if (preg_match("~^$space*(CREATE|DROP|ALTER)$space+(DATABASE|SCHEMA)\\b~isU", $q)) {
									restart_session();
									set_session("dbs", null); // clear cache
									session_write_close();
								}
								if (!$_POST["only_errors"]) {
									echo "<p class='message' title='" . h($connection->info) . "'>" . lang('Query executed OK, %d row(s) affected.', $connection->affected_rows) . "$time\n";
								}
							}
							$start = $end;
						} while ($connection->next_result());
						$line += substr_count($q.$found, "\n");
						$query = substr($query, $offset);
						$offset = 0;
					}
				}
			}
//			break;
		} while(false);
		if ($empty) {
			echo "<p class='message'>" . lang('No commands to execute.') . "\n";
		} elseif ($_POST["only_errors"]) {
			echo "<p class='message'>" . lang('%d query(s) executed OK.', $commands - count($errors)) . format_time($total_start, microtime()) . "\n";
		} elseif ($errors && $commands > 1) {
			echo "<p class='error'>" . lang('Error in query') . ": " . implode("", $errors) . "\n";
		}
		//! MS SQL - SET SHOWPLAN_ALL OFF
	// } else {
	// 	echo "<p class='error'>" . upload_error($query) . "\n";
	// }
}
?>

<form action="" method="post" id="form">
<p><?php
$q = $_GET["sql"]; // overwrite $q from if ($_POST) to save memory
if ($_POST) {
	$q = $_POST["query"];
} elseif ($_GET["history"] == "all") {
	$q = $history;
} elseif ($_GET["history"] != "") {
	$q = $history[$_GET["history"]][0];
}
textarea("query", $q ?: '$db->table()', 20, 80, "query");
echo ($_POST ? "" : "<script type='text/javascript'>document.getElementById('query').focus();</script>\n");

?>
<p>
<input type="submit" value="<?php echo lang('Execute'); ?>" title="Ctrl+Enter">
<input type="hidden" name="token" value="<?php echo $token; ?>">
<?php


if ($history) {
	print_fieldset("history", lang('History'), $_GET["history"] != "");
	foreach ($history as $key => $val) {
		list($q, $time) = $val;
		echo '<a href="' . h(ME . "sql=&history=$key") . '">' . lang('Edit') . "</a> <span class='time'>" . @date("H:i:s", $time) . "</span> <code class='jush-$jush'>" . shorten_utf8(ltrim(str_replace("\n", " ", str_replace("\r", "", preg_replace('~^(#|-- ).*~m', '', $q)))), 80, "</code>") . "<br>\n"; // @ - time zone may be not set
	}
	echo "<input type='submit' name='clear' value='" . lang('Clear') . "'>\n";
	echo "<a href='" . h(ME . "sql=&history=all") . "'>" . lang('Edit all') . "</a>\n";
	echo "</div></fieldset>\n";
}
?>

</form>
