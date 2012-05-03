<?php
$TABLE = $_GET["phpdoc"];
$fields = fields($TABLE);
if (!$fields) {
	$error = error();
}
$table_status = ($fields ? table_status($TABLE) : array());

page_header(lang('PHPdoc') . ": " . h($TABLE), $error);
$adminer->selectLinks($table_status);

$types = array(
	'~integer|smallint~A' => 'int',
	'~(text|varchar.+|character.+)~A' => 'string',
	'~timestamp.+~A' => 'int',
	'~datetime|date|time~' => '\DateTime',
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
