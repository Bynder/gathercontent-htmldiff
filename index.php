<?php
require 'php_html_examples.php';

require 'vendor/autoload.php';


$diff = new cogpowered\FineDiff\Diff(new cogpowered\FineDiff\Granularity\Paragraph, new Gathercontent\Htmldiff\Render\CustomRenderer);
echo $diff->render($html1, $html2);