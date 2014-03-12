<?php
require 'php_html_examples.php';

require 'vendor/autoload.php';


$htmldiff = new Gathercontent\Htmldiff\Htmldiff;

echo $htmldiff->compare($text2, $text1, true);

/*
$diff = new cogpowered\FineDiff\Diff(new cogpowered\FineDiff\Granularity\Paragraph, new Gathercontent\Htmldiff\Render\CustomRenderer);
echo $diff->render($html1, $html2);*/