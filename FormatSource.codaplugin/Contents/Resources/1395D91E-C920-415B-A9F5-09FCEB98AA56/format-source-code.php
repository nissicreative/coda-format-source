#!/opt/local/bin/php
<?php
/*
 * A plugin for Coda to format source code
 *
 * @author Mike Folsom <mike@nissicreative.com>
 * @since 12/9/15
 * @requires MacPorts version of PHP compiled with tidy
 */


// Plugin receives a selection or an entire document
$html = file_get_contents('php://stdin');


// If we are processing an entire document, we want to preserve (unaffected) everything up through closing <head> tag
$head_regex = '~</head>~m';

if (preg_match($head_regex, $html)) {
	list($head) = preg_split('~</head>~m', $html);
}


// Capture <body> opening tag in case it has an id, class, etc.
$body_tag = '';

if (preg_match('~<body[^>]*>~', $html, $matches)) {
	$body_tag = $matches[0];
}


// Remove all line indentations (let Tidy start with a clean slate!)
$html = preg_replace('~^\s+~m', '', $html);


// Preserve <title> element if it is not inside <head> (i.e. used in a Blade template)
if (empty($head)) {
    $html = str_replace(['<title>', '</title>'], ['[title]', '[/title]'], $html);
}



//! Tidy markup
// ================================================== //
$config = array(
	'doctype'              => 'auto',
	'drop-empty-paras'     => 'no',
	'drop-empty-elements'  => 'no', // for instance, <i class="fa fa-something"></i>
	'fix-uri'              => 'no',
	'indent'               => 2, // Must use '2' instead of 'auto' (bug?)
	'indent-spaces'        => 4,
	'literal-attributes'   => 'yes', // Otherwise, PHP tags are converted to urlencoded entities
	'merge-divs'           => 'no',
	'merge-spans'          => 'no',
	'new-blocklevel-tags'  => 'article,aside,command,canvas,dialog,details,figcaption,figure,footer,header,hgroup,menu,nav,section,summary,meter',
	'new-inline-tags'      => 'video,audio,canvas,ruby,rt,rp,time,meter,progress,datalist,keygen,mark,output,source,wbr',
	'output-xhtml'         => 'yes',
	'quote-ampersand'      => 'no',
	'show-body-only'       => 1,
	'tab-size'             => 4,
	'tidy-mark'            => 'no',
	'vertical-space'       => 'yes',
	'wrap-php'             => 'no',
	'wrap-sections'        => 'no',
	'wrap'                 => 0,
);

$tidy = new tidy;
$tidy->parseString($html, $config, 'utf8');
$tidy->cleanRepair();



//! Additional post-Tidy cleanup
// ================================================== //

for ($i = 0; $i < 3; $i++) {

	// Add line breaks before a few other starting tags...
	$tidy = preg_replace('~^(\s*)(\S+.*)(<(iframe|input|select|script|!--).*)~m', "$1$2\n$1$3", $tidy);


	// Line break before PHP block, unless it is an echo statement
	$tidy = preg_replace('~(^\s*)(\S+.*)(<\?php(?!.*echo))~m', "$1$2\n$1$3", $tidy);


	// Line break before Blade directives
	$tidy = preg_replace('~(^\s*)(\S+.*)(@(section|stop|show|parent|include|if|else|endsection|unless|for))~m', "$1$2\n$1$3", $tidy);
	$tidy = preg_replace('~(^\s*)(\S+.*)({!! Form:)~m', "$1$2\n$1$3", $tidy);


	// Remove line breaks on <script> tags with a 'src' attribute
	$tidy = preg_replace('~(<script src[^>]*>)(\s*)(</script>)~m', "$1$3", $tidy);

	
	// Put </td> directly after content
	$tidy = preg_replace('~\s*(</td>)~m', "$1", $tidy);
}
	
// Remove whitespace inside <textarea> tags
$tidy = preg_replace('~>(\s*)(<\?php(.*)\?>)?\s*(</textarea>)~m', ">$2$4", $tidy);


// Four spaces to tab
// $tidy = str_replace('    ', "\t", $tidy);


// Indent 'init()' JS (A personal thing)
$tidy = preg_replace('~^init\(\);~m', "\tinit();", $tidy);


// Extra line breaks between Blade @section delimiters
$tidy = preg_replace('~^(@section)~m', "\n\n$1", $tidy);


// Blade Templating - Preserve operators
$tidy = str_replace('=&gt;', '=>', $tidy);
$tidy = str_replace('-&gt;', '->', $tidy);


// Restore <title> tag
$tidy = str_replace(['[title]', '[/title]'], ["\n<title>", '</title>'], $tidy);


//! Output results
// ================================================== //

if (!empty($head)) {
	// Update revision date
	date_default_timezone_set('America/Chicago');
	$head = preg_replace('~(?:Rev\. )\d+/\d+/\d+~', 'Rev. ' . date('m/d/Y'), $head);
	
	echo $head . "</head>\n";
	echo "$body_tag\n";
}

// ($tidy will contain only what's inside the <body> tag of a complete document)
echo $tidy;

if (!empty($head)) {
	echo "\n</body>";
	echo "\n</html>";
}