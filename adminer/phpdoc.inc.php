<?php
$TABLE = $_GET["phpdoc"];
$fields = fields($TABLE);
if (!$fields) {
	$error = error();
}
$table_status = ($fields ? table_status($TABLE) : array());

page_header(($fields && is_view($table_status) ? lang('View') : lang('Table')) . ": " . h($TABLE), $error);
$adminer->selectLinks($table_status);

echo '<h2>' . lang('PHP doc') . '</h2>';

$types = array(
	'~integer|smallint~A' => 'int',
	'~character.+~A' => 'string',
	'~timestamp.+~A' => 'int',
);

echo "<pre>/**\n";
foreach($fields as $field) {
	$originalType = $field['full_type'];
	$type = null;
	foreach($types as $pattern => $patternType) {
		if(preg_match($pattern, $originalType)) {
			$type = preg_replace($pattern, $patternType, $originalType);
			break;
		}
	}
	if(!isset($type)) list($type) = preg_split('~\W~', $originalType);


	echo " * @property $type \$$field[field]\n";
}
echo " */</pre>";