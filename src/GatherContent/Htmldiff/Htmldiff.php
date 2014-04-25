<?php

namespace GatherContent\Htmldiff;

class Htmldiff {

    const DIFF_DELETE = -1;
    const DIFF_INSERT = 1;
    const DIFF_EQUAL  = 0;

    private $attributes = array();

    private $old = '';

    private $new = '';

    private $output = '';

    private $diff_string = '';

    private $open_elements = array();

    private $deleting = false;

    private $adding = false;

    private $whole_tag = false;

    public $match = array(
        'nonAlphaNumericRegex' => '/[^a-zA-Z0-9]/',
        'whitespaceRegex' => '/\s/',
        'linebreakRegex' => '/[\r\n]/',
        'blanklineEndRegex' => '/\n\r?\n$/',
        'blanklineStartRegex' => '/^\r?\n\r?\n/'
    );

    private function clean_html($html) {
        $html = trim($html);
        $config = array(
            'indent' => true,
            'wrap' => false,
            'show-body-only' => true,
            'output-xhtml' => true,
        );
        $tidy = tidy_parse_string($html, $config, 'utf8');
        $tidy->cleanRepair();

        return (string) $tidy;
    }

    private function recursive_get_nodes(array $return, \DOMElement $parent, $depth = 1, $prefix='') {

        $last_element = '';

        foreach($parent->childNodes as $node) {

            if($node instanceof \DomElement) {

                $new_prefix = $prefix.$node->localName;

                if(count($node->attributes) > 0) {

                    $attrs = array();

                    foreach($node->attributes as $attrName => $attrNode) {
                        $attrs[$attrName] = $attrNode->nodeValue;
                    }

                    if(count($attrs) > 0) {

                        $attrid = md5(serialize($attrs));

                        $this->attributes[md5(serialize($attrs))] = $attrs;

                        $new_prefix .= '['.$attrid.']';
                    }

                }

                $new_prefix .= '/';

                if(substr(end($return), 0, strlen($new_prefix)) == $new_prefix) {
                    $return[] = $new_prefix.'------';
                }
                else {
                    $return[] = $new_prefix;
                }
            }
            elseif($node instanceof \DomText) {
                $trimmed = trim($node->wholeText);
                if(empty($trimmed)) {
                    continue;
                }
                $text = str_split($node->wholeText);

                $word = '';

                $last_was_space = false;

                foreach($text as $text_item) {

                    if($text_item != '' && $text_item != ' ') {
                        if($last_was_space) {
                            $last_was_space = false;
                            $return[] = $prefix.'"'.$word.'"';
                            $word = $text_item;
                        }
                        else {
                            $word .= $text_item;
                        }
                    }
                    else {
                        $word .= $text_item;
                        $last_was_space = true;
                    }
                }

                if(!empty($word)) {
                    $return[] = $prefix.'"'.$word.'"';
                }
            }
            if($node->hasChildNodes()) {
                $return = $this->recursive_get_nodes($return, $node, $depth+1, $new_prefix);
            }
        }

        return $return;
    }

    private function html_to_array($html) {

        $array = array();

        if(empty($html)) {
            return $array;
        }

        $html = str_replace(array("\r\n","\n"),'',$this->clean_html($html));

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML($html);
        $html = $doc->getElementsByTagName('body')->item(0);

        $array = $this->recursive_get_nodes($array, $html);
        array_unshift($array, 'start/start');
        array_push($array, 'end/end');

        return $array;
    }

    public function compare($old, $new, $plain_text=false) {

        $this->output = '';

        $this->diff_string = '';

        if($plain_text) {

            $this->old = $old;
            $this->new = $new;

            $granularity = new \cogpowered\FineDiff\Granularity\Word;
            $renderer    = new \GatherContent\Htmldiff\Render\CustomRendererPlain;

            $diff = new \cogpowered\FineDiff\Diff($granularity, $renderer);
            return $diff->render($this->old, $this->new);
        }
        else {

            $this->old = implode("\n", $this->html_to_array($old));
            $this->new = implode("\n", $this->html_to_array($new));

            $granularity = new \cogpowered\FineDiff\Granularity\Paragraph;
            $renderer    = new \GatherContent\Htmldiff\Render\CustomRendererHtml;

            $diff = new \cogpowered\FineDiff\Diff($granularity, $renderer);
            $this->diff_string = $diff->render($this->old, $this->new);

            $this->generate_output();

            return $this->clean_html($this->output);
        }
    }

    private function _tags_array($tags=array()) {
        if(!is_array($tags)) {
            $tags = array($tags);
        }
        $tags = array_filter($tags);
        if(!count($tags)) {
            return false;
        }
        return $tags;
    }

    private function _close_tag($tag) {


        if($tag == 'del.diff') {
            $this->whole_tag = $this->deleting = false;
            $this->output .= '</del>';
        }
        elseif($tag == 'ins.diff') {
            $this->whole_tag = $this->adding = false;
            $this->output .= '</ins>';
        }
        elseif($tag != 'br') {
            $tag = preg_replace('/\[(.*?)\]$/', '', $tag);
            $this->output .= '</'.$tag.'>';
        }
    }

    private function close_to_tag($tag=null) {

        reset($this->open_elements);

        foreach($this->open_elements as $open) {

            $this->_close_tag($open);
            array_shift($this->open_elements);

            if($tag == $open) {
                return true;
            }

        }

        return false;
    }

    private function close_to_array($tags=array()) {

        if($tags = $this->_tags_array($tags)) {

            $open_tags = $this->open_elements;

            $new_open = array();

            $new_tags = array();

            foreach($tags as $tag) {

                if(end($open_tags) == $tag) {
                    array_unshift($new_open, array_pop($open_tags));
                }
                else {
                    $new_tags[] = $tag;
                }
            }

            $this->open_elements = $new_open;

            foreach($open_tags as $open) {
                $this->_close_tag($open);
            }

            return $new_tags;
        }
    }

    private function open_new_elements($tags=array()) {

        if($tags = $this->_tags_array($tags)) {
            foreach($tags as $tag) {

                if($tag == 'del.diff') {
                    $this->deleting = true;
                    $this->output .= '<del class="html-diff-del">';
                    array_unshift($this->open_elements, 'del.diff');
                }
                elseif($tag == 'ins.diff') {
                    $this->adding = true;
                    $this->output .= '<ins class="html-diff-ins">';
                    array_unshift($this->open_elements, 'ins.diff');
                }
                elseif(preg_match('/\[(.*?)\]$/', $tag, $attr) && isset($this->attributes[$attr[1]])) {
                    $attrs = '';
                    foreach($this->attributes[$attr[1]] as $attr_key => $attr_val) {
                        $attrs .= ' '.$attr_key.'="'.$attr_val.'"';
                    }
                    $tag = str_replace('['.$attr[1].']','', $tag);
                    array_unshift($this->open_elements, $tag);
                    $this->output .= '<'.$tag.$attrs.'>';
                }
                else {
                    $this->output .= '<'.$tag.'>';
                    array_unshift($this->open_elements, $tag);
                }
            }
        }
    }

    private function generate_output() {

        $regex = '/^([\s|\-|\+]?+)([a-z|0-9|\/|\[\]]*)\/(.*)/m';
        $match_count = preg_match_all($regex, $this->diff_string, $matches);
        $last_element = '';

        $cleanup = array();

        for($i = 0; $i < $match_count; $i++) {

            if($matches[2][$i] == 'start' || $matches[2][$i] == 'end' || empty($matches[0][$i])) {
                continue;
            }

            $ins = $del = false;

            switch($matches[1][$i]) {
                case '-':
                    $del = true;
                    break;
                case '+':
                    $ins = true;
                    break;
            }

            $last2 = substr($matches[2][$i], -2);

            if($last2 == '/"') {
                $matches[3][$i] = "/";
                $matches[2][$i] = substr($matches[2][$i], 0, -2);
            }

            $matches[2][$i] = rtrim($matches[2][$i], '/');

            if($matches[3][$i] == '------') {
                $tag = end(array_filter(explode('/', rtrim($matches[2][$i],'/'))));
                $cleanup = $this->add_output_text($cleanup);
                $this->close_to_tag($tag);
                $this->open_new_elements($tag);
                $last_element = $matches[2][$i];
                $matches[3][$i] = '';
            }
            elseif($last_element != $matches[2][$i]) {
                $tags = array_filter(explode('/', $matches[2][$i]));
                $cleanup = $this->add_output_text($cleanup);
                $new_tags = $this->close_to_array($tags);
                $this->open_new_elements($new_tags);
                $last_element = $matches[2][$i];
            }

            if($ins) {
                $adding_val = self::DIFF_INSERT;
            }
            elseif($del) {
                $adding_val = self::DIFF_DELETE;
            }
            else {
                $adding_val = self::DIFF_EQUAL;
            }

            if($matches[3][$i] == '-'){
                $cleanup[] = array($adding_val, ' ');
                $matches[3][$i] = ' ';
            } elseif($matches[3][$i] != '') {
                $matches[3][$i] = substr($matches[3][$i], 1, -1);
                $cleanup[] = array($adding_val, $matches[3][$i]);
            }

        }

        $this->add_output_text($cleanup);
        $this->close_to_tag();
    }

    private function add_output_text($cleanup) {
        
        $cleanup = $this->diff_cleanupSemantic($cleanup);

        foreach($cleanup as $clean) {

            switch($clean[0]) {
                case self::DIFF_DELETE:
                    if($this->adding) {
                        $this->close_to_tag('ins.diff');
                    }
                    if(!$this->deleting) {
                        $this->open_new_elements('del.diff');
                    }
                    break;
                case self::DIFF_INSERT:
                    if($this->deleting) {
                        $this->close_to_tag('del.diff');
                    }
                    if(!$this->adding) {
                        $this->open_new_elements('ins.diff');
                    }
                    break;
                case self::DIFF_EQUAL:
                    if($this->deleting) {
                        $this->close_to_tag('del.diff');
                    }
                    if($this->adding) {
                        $this->close_to_tag('ins.diff');
                    }
                    break;

            }

            $this->output .= $clean[1];
        }

        return array();

    }

    private function diff_cleanupSemantic ($diffs) {

        $changes = false;
        $equalities = array();
        $equalitiesLength = 0;

        $lastequality = null;

        $pointer = 0;

        $length_insertions1 = 0;
        $length_deletions1 = 0;

        $length_insertions2 = 0;
        $length_deletions2 = 0;

        while($pointer < count($diffs)) {

            if($diffs[$pointer][0] == self::DIFF_EQUAL) {
                $equalities[$equalitiesLength++] = $pointer;
                $length_insertions1 = $length_insertions2;
                $length_deletions1 = $length_deletions2;
                $length_insertions2 = 0;
                $length_deletions2 = 0;
                $lastequality = $diffs[$pointer][1];
            }
            else {

                if($diffs[$pointer][0] == self::DIFF_INSERT) {
                    $length_insertions2 += strlen($diffs[$pointer][1]);
                }
                else {
                    $length_deletions2 += strlen($diffs[$pointer][1]);
                }

                if($lastequality && (strlen($lastequality) <= max($length_insertions1, $length_deletions1)) &&
                    (strlen($lastequality) <= max($length_insertions2, $length_deletions2))) {
                    array_splice($diffs, $equalities[$equalitiesLength - 1], 0, array(array(self::DIFF_DELETE, $lastequality)));
                    $diffs[$equalities[$equalitiesLength - 1] + 1][0] = self::DIFF_INSERT;
                    $equalitiesLength--;
                    $equalitiesLength--;
                    $pointer = $equalitiesLength > 0 ? $equalities[$equalitiesLength - 1] : -1;
                    $length_insertions1 = $length_insertions2 = $length_deletions1 = $length_deletions2 = 0;
                    $lastequality = null;
                    $changes = true;
                }
            }
            $pointer++;
        }

        if($changes) {
            $diffs = $this->diff_cleanupMerge($diffs);
        }

        $diffs = $this->diff_cleanupSemanticLossless($diffs);

        $pointer = 1;
        while($pointer < count($diffs)){

            if($diffs[$pointer - 1][0] == self::DIFF_DELETE &&
                $diffs[$pointer][0] == self::DIFF_INSERT) {
                $deletion = $diffs[$pointer - 1][1];
                $insertion = $diffs[$pointer][1];
                $overlap_length1 = $this->diff_commonOverlap_($deletion, $insertion);
                $overlap_length2 = $this->diff_commonOverlap_($insertion, $deletion);

                if($overlap_length1 >= $overlap_length2) {
                    if($overlap_length1 >= strlen($deletion) / 2 ||
                        $overlap_length1 >= strlen($insertion) / 2) {
                        array_splice($diffs, $pointer, 0, array(array(self::DIFF_EQUAL, substr($insertion, 0, $overlap_length1))));
                        $diffs[$pointer - 1][1] = substr($deletion, 0, strlen($deletion) - $overlap_length1);
                        $diffs[$pointer + 1][1] = substr($insertion, $overlap_length1);
                        $pointer++;
                    }
                }
                else {
                    if($overlap_length2 >= strlen($deletion) / 2 || 
                        $overlap_length2 >= strlen($insertion) / 2) {
                        array_splice($diffs, $pointer, 0, array(array(self::DIFF_EQUAL, substr($deletion, 0, $overlap_length2))));
                        $diffs[$pointer - 1][0] = self::DIFF_INSERT;
                        $diffs[$pointer - 1][1] = substr($insertion, 0, strlen($insertion) - $overlap_length2);
                        $diffs[$pointer + 1][0] = self::DIFF_DELETE;
                        $diffs[$pointer + 1][1] = substr($deletion, $overlap_length2);
                        $pointer++;
                    }
                }
                $pointer++;
            }
            $pointer++;
        }
        return $diffs;
    }

    private function diff_cleanupSemanticLossless ($diffs) {

        $pointer = 1;

        while($pointer < count($diffs) -1) {
            if($diffs[$pointer -1][0] == self::DIFF_EQUAL && $diffs[$pointer + 1][0] == self::DIFF_EQUAL) {
                $equality1 = $diffs[$pointer - 1][1];
                $edit = $diffs[$pointer][1];
                $equality2 = $diffs[$pointer + 1][1];

                $commonOffset = $this->diff_commonSuffix($equality1, $edit);
                if($commonOffset) {
                    $commonString = substr($edit, strlen($edit) - $commonOffset);
                    $equality1 = substr($equality1, 0, strlen($equality1) - $commonOffset);
                    $edit = $commonString.substr($edit, 0, strlen($edit) - $commonOffset);
                    $equality2 = $commonString.$equality2;
                }

                $bestEquality1 = $equality1;
                $bestEdit = $edit;
                $bestEquality2 = $equality2;
                $bestScore = $this->diff_cleanupSemanticScore_($equality1, $edit) + $this->diff_cleanupSemanticScore_($edit, $equality2);

                while($edit[0] === $equality2[0]) {
                    $equality1 .= $edit[0];
                    $edit = substr($edit, 1).$equality2[0];
                    $equality2 = substr($equality2, 1);
                    $score = $this->diff_cleanupSemanticScore_($equality1, $edit) + $this->diff_cleanupSemanticScore_($edit, $equality2);

                    if($score >= $bestScore) {
                        $bestScore = $score;
                        $bestEquality1 = $equality1;
                        $bestEdit = $edit;
                        $bestEquality2 = $equality2;
                    }
                }

                if($diffs[$pointer - 1][1] != $bestEquality1) {
                    if($bestEquality1) {
                        $diffs[$pointer - 1][1] = $bestEquality1;
                    }
                    else {
                        array_splice($diffs, $pointer - 1, 1);
                        $pointer--;
                    }
                    $diffs[$pointer][1] = $bestEdit;
                    if($bestEquality2) {
                        $diffs[$pointer + 1][1] = $bestEquality2;
                    }
                    else {
                        array_splice($diffs, $pointer + 1, 1);
                        $pointer--;
                    }
                }
            }
            $pointer++;
        }
        return $diffs;
    }

    private function diff_cleanupSemanticScore_ ($one, $two) {

        if(!$one || !$two) {
            return 6;
        }

        $char1 = $one[strlen($one) - 1];
        $char2 = $two[0];
        $nonAlphaNumeric1 = preg_match($this->match['nonAlphaNumericRegex'], $char1);
        $nonAlphaNumeric2 = preg_match($this->match['nonAlphaNumericRegex'], $char2);
        $whitespace1 = $nonAlphaNumeric1 && preg_match($this->match['whitespaceRegex'], $char1);
        $whitespace2 = $nonAlphaNumeric2 && preg_match($this->match['whitespaceRegex'], $char2);
        $lineBreak1 = $whitespace1 && preg_match($this->match['linebreakRegex'], $char1);
        $lineBreak2 = $whitespace2 && preg_match($this->match['linebreakRegex'], $char2);
        $blankLine1 = $lineBreak1 && preg_match($this->match['blanklineEndRegex'], $one);
        $blankLine2 = $lineBreak2 && preg_match($this->match['blanklineStartRegex'], $two);

        if($blankLine1 || $blankLine2) {
            return 5;
        }
        elseif($lineBreak1 || $lineBreak2) {
            return 4;
        }
        elseif($nonAlphaNumeric1 && !$whitespace1 && $whitespace2) {
            return 3;
        }
        elseif($whitespace1 || $whitespace2) {
            return 2;
        }
        elseif($nonAlphaNumeric1 || $nonAlphaNumeric2) {
            return 1;
        }
        return 0;
    }

    private function diff_cleanupMerge ($diffs) {

        array_push($diffs, array(self::DIFF_EQUAL, ''));
        $pointer = 0;
        $count_delete = 0;
        $count_insert = 0;
        $text_delete = '';
        $text_insert = '';
        $commonlength = 0;

        while($pointer < count($diffs)) {
            switch($diffs[$pointer][0]) {
                case self::DIFF_INSERT:
                    $count_insert++;
                    $text_insert .= $diffs[$pointer][1];
                    $pointer++;
                    break;
                case self::DIFF_DELETE:
                    $count_delete++;
                    $text_delete .= $diffs[$pointer][1];
                    $pointer++;
                    break;
                case self::DIFF_EQUAL:
                    if($count_delete + $count_insert > 1) {
                        if($count_delete !== 0 && $count_insert !== 0) {
                            $commonlength = $this->diff_commonPrefix($text_insert, $text_delete);
                            if($commonlength !== 0) {
                                if(($pointer - $count_delete - $count_insert) > 0 &&
                                    $diffs[$pointer - $count_delete - $count_insert - 1][0] == self::DIFF_EQUAL) {
                                    $diffs[$pointer - $count_delete - $count_insert][1] .= substr($text_insert, 0, $commonlength);
                                }
                                else {
                                    array_splice($diffs, 0, 0, array(array(self::DIFF_EQUAL, substr($text_insert, 0, $commonlength))));
                                    $pointer++;
                                }
                                $text_insert = substr($text_insert, $commonlength);
                                $text_delete = substr($text_delete, $commonlength);
                            }

                            $commonlength = $this->diff_commonSuffix($text_insert, $text_delete);
                            if($commonlength !== 0) {
                                $diffs[$pointer][1] = substr($text_insert, strlen($text_insert) - $commonlength).$diffs[$pointer][1];
                                $text_insert = substr($text_insert, 0, strlen($text_insert) - $commonlength);
                                $text_delete = substr($text_delete, 0, strlen($text_delete) - $commonlength);
                            }
                        }

                        if($count_delete === 0) {
                            array_splice($diffs, $pointer - $count_insert, $count_delete + $count_insert, array(array(self::DIFF_INSERT, $text_insert)));
                        }
                        elseif($count_insert === 0) {
                            array_splice($diffs, $pointer - $count_delete, $count_delete + $count_insert, array(array(self::DIFF_DELETE, $text_delete)));
                        }
                        else {
                            array_splice($diffs, $pointer - $count_delete - $count_insert, $count_delete + $count_insert, array(array(self::DIFF_DELETE, $text_delete), array(self::DIFF_INSERT, $text_insert)));
                        }
                        $pointer = $pointer - $count_delete - $count_insert + ($count_delete ? 1 : 0) + ($count_insert ? 1 : 0) + 1;
                    }
                    elseif($pointer !== 0 && $diffs[$pointer - 1][0] == self::DIFF_EQUAL) {
                        $diffs[$pointer - 1][1] .= $diffs[$pointer][1];
                        array_splice($diffs, $pointer, 1);
                    }
                    else {
                        $pointer++;
                    }
                    $count_insert = 0;
                    $count_delete = 0;
                    $text_delete = '';
                    $text_insert = '';
                    break;
            }
        }

        if($diffs[count($diffs) - 1][1] === '') {
            array_pop($diffs);
        }

        $changes = false;
        $pointer = 1;

        while($pointer < count($diffs) - 1) {
            if($diffs[$pointer -1][0] == self::DIFF_EQUAL && $diffs[$pointer + 1][0] == self::DIFF_EQUAL) {
                if(substr($diffs[$pointer][1], strlen($diffs[$pointer][1]) - strlen($diffs[$pointer - 1][1])) == $diffs[$pointer - 1][1]) {
                    $diffs[$pointer][1] = $diffs[$pointer - 1][1].substr($diffs[$pointer][1], 0, strlen($diffs[$pointer][1]) - strlen($diffs[$pointer - 1][1]));
                    $diffs[$pointer + 1][1] = $diffs[$pointer - 1][1].$diffs[$pointer + 1][1];
                    array_splice($diffs, $pointer - 1, 1);
                    $changes = true;
                }
                elseif(substr($diffs[$pointer][1], 0, strlen($diffs[$pointer + 1][1])) == $diffs[$pointer + 1][1]) {
                    $diffs[$pointer - 1][1] .= $diffs[$pointer + 1][1];
                    $diffs[$pointer][1] = substr($diffs[$pointer][1], strlen($diffs[$pointer + 1][1])).$diffs[$pointer + 1][1];
                    array_splice($diffs, $pointer + 1, 1);
                    $changes = true;
                }
            }
            $pointer++;
        }
        if($changes) {
            $diffs = $this->diff_cleanupMerge($diffs);
        }

        return $diffs;
    }

    private function diff_commonPrefix ($text1, $text2) {

        if(!$text1 || !$text2 || $text1[0] != $text2[0]) {
            return 0;
        }

        $pointermin = 0;
        $pointermax = min(strlen($text1), strlen($text2));
        $pointermid = $pointermax;
        $pointerstart = 0;
        while($pointermin < $pointermid) {
            if(substr($text1, $pointerstart, $pointermid) == substr($text2, $pointerstart, $pointermid)) {
                $pointermin = $pointermid;
                $pointerstart = $pointermin;
            }
            else {
                $pointermax = $pointermid;
            }
            $pointermid = floor(($pointermax - $pointermin) / 2 + $pointermin);
        }

        return $pointermid;
    }

    private function diff_commonSuffix ($text1, $text2) {

        if(!$text1 || !$text2 || $text1[strlen($text1) - 1] != $text2[strlen($text2) - 1]) {
            return 0;
        }

        $pointermin = 0;
        $pointermax = min(strlen($text1), strlen($text2));
        $pointermid = $pointermax;
        $pointerend = 0;
        while($pointermin < $pointermid) {
            if(substr($text1, strlen($text1) - $pointermid, strlen($text1) - $pointerend) == substr($text2, strlen($text2) - $pointermid, strlen($text2) - $pointerend)) {
                $pointermin = $pointermid;
                $pointerend = $pointermin;
            }
            else {
                $pointermax = $pointermid;
            }
            $pointermid = floor(($pointermax - $pointermin) / 2 + $pointermin);
        }
        return $pointermid;
    }

    private function diff_commonOverlap_ ($text1, $text2) {

        $text1_length = strlen($text1);
        $text2_length = strlen($text2);

        if($text1_length == 0 || $text2_length == 0) {
            return 0;
        }

        if($text1_length > $text2_length) {
            $text1 = substr($text1, $text1_length - $text2_length);
        }
        elseif($text1_length < $text2_length) {
            $text2 = substr($text2, 0, $text1_length);
        }

        $text_length = min($text1_length, $text2_length);

        if($text1 == $text2) {
            return $text_length;
        }

        $best = 0;
        $length = 1;
        while(true) {
            $pattern = substr($text1, $text_length - $length);
            $found = strpos($text2, $pattern);
            if($found === false) {
                return $best;
            }
            $length += $found;
            if($found == 0 || substr($text1, $text_length - $length) == substr($text2, 0, $length)) {
                $best = $length;
                $length++;
            }
        }
    }
}
