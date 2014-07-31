<?php

use GatherContent\Htmldiff\Htmldiff;

class HtmldiffTest extends PHPUnit_Framework_TestCase
{
    // Varia

    public function testEmptyStringsHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('', '');

        $expected = '';
        $this->assertEquals($expected, $result);
    }

    public function testEqualStringsHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>This is a test string. Ya know, letters.</span>', '<span>This is a test string. Ya know, letters.</span>');

        $expected = '<span>This is a test string. Ya know, letters.</span>';
        $this->assertEquals($expected, $result);
    }

    public function testEmptyAndStringHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('', '<span>This is a test string. Ya know, letters.</span>');

        $expected = '<span><ins>This is a test string. Ya know, letters.</ins></span>';
        $this->assertEquals($expected, $result);
    }

    public function testStringAndEmptyHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>This is a test string. Ya know, letters.</span>', '');

        $expected = '<span><del>This is a test string. Ya know, letters.</del></span>';
        $this->assertEquals($expected, $result);
    }

    public function testUtf8CharactersHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>’ 42°19\'46" — « sur » &amp; (asdf) &lt;hello world&gt; &lt;this-is-test&gt; - „word” » ¿Qué Tal?</span>', '<span>’ 42°19\'46" — « sur » &amp; (asdf) &lt;hello world&gt; &lt;this-is-test&gt; - „word” » ¿Qué Tal?</span>');

        $expected = '<span>’ 42°19\'46" — « sur » &amp; (asdf) &lt;hello world&gt; &lt;this-is-test&gt; - „word” » ¿Qué Tal?</span>';
        $this->assertEquals($expected, $result);
    }

    // HTML

    public function testHtmlMovingTextAround()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<ul><li>Lorem ipsum dolor sit amet</li><li>just a test</li></ul>', '<ul><li>Lorem ipsum</li><li>dolor sit amet</li><li>just a test</li></ul>');

        $expected = '<ul><li>Lorem ipsum <del>dolor sit amet</del></li><li><ins>dolor sit amet</ins></li><li>just a test</li></ul>';
        //$this->assertEquals($expected, $result);

        $this->markTestSkipped();
    }

    public function testHtmlNodeSubstitution()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<p><a href="#" class="link" style="color: #888;">Lorem ipsum dolor sit amet</a></p>', '<p><span>Lorem ipsum dolor sit amet</span></p>');

        $expected = '<p><a href="#" class="link" style="color: #888;"><del>Lorem ipsum dolor sit amet</del></a><span><ins>Lorem ipsum dolor sit amet</ins></span></p>';
        $this->assertEquals($expected, $result);
    }

    public function testHtmlNodeAddition()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<ul><li>Lorem ipsum</li></ul>', '<ul><li>Lorem ipsum</li><li>dolor sit amet</li></ul>');

        $expected = '<ul><li>Lorem ipsum</li><li><ins>dolor sit amet</ins></li></ul>';
        $this->assertEquals($expected, $result);
    }

    public function testHtmlNodeDeletion()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<ul><li>Lorem ipsum</li><li>dolor sit amet</li></ul>', '<ul><li>Lorem ipsum</li></ul>');

        $expected = '<ul><li>Lorem ipsum</li><li><del>dolor sit amet</del></li></ul>';
        $this->assertEquals($expected, $result);
    }

    public function testHtmlNewUnorderedList()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<p>Hello world</p><p>How do you do</p>', '<p>Hello world</p><ul><li>first point</li><li>second point</li></ul><p>How do you do</p>');

        $expected = '<p>Hello world</p><ul><li><ins>first point</ins></li><li><ins>second point</ins></li></ul><p>How do you do</p>';
        $this->assertEquals($expected, $result);
    }

    public function testHtmlBr()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<p>Hello world how do you do</p>', '<p>Hello world<br>how do you do</p>');

        $expected = '<p>Hello world <br> how do you do</p>';
        $this->assertEquals($expected, $result);
    }

    public function testHtmlStrong()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<p>Hello world, how do you do</p>', '<p>Hello world, how do <strong>you</strong> do</p>');

        $expected = '<p>Hello world, how do <del>you</del><strong><ins>you</ins></strong> do</p>';
        $this->assertEquals($expected, $result);
    }

    public function testHtmlImg()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<p>Hello world</p>', '<p>Hello world</p><img src="#" class="photo">');

        $expected = '<p>Hello world</p><img src="#" class="photo">';
        $this->assertEquals($expected, $result);
    }

    public function testHtmlEmptyTags()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<p>Hello world<span></span></p>', '<p>Hello world<span></span></p>');

        $expected = '<p>Hello world</p>';
        $this->assertEquals($expected, $result);
    }

    // English

    public function testEnglishSubstitutionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>This is a string</span>', '<span>This is a text</span>');

        $expected = '<span>This is a <del>string</del><ins>text</ins></span>';
        $this->assertEquals($expected, $result);
    }

    public function testEnglishAdditionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>This is a string</span>', '<span>This is a simple string</span>');

        $expected = '<span>This is a <ins>simple</ins> string</span>';
        $this->assertEquals($expected, $result);
    }

    public function testEnglishDeletionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>This is a simple string</span>', '<span>This is a string</span>');

        $expected = '<span>This is a <del>simple</del> string</span>';
        $this->assertEquals($expected, $result);
    }

    // Cyrillic

    public function testCyrillicSubstitutionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>Впервые термин Россия</span>', '<span>Впервые термин встречается</span>');

        $expected = '<span>Впервые термин <del>Россия</del><ins>встречается</ins></span>';
        $this->assertEquals($expected, $result);
    }

    public function testCyrillicAdditionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>Впервые термин Россия</span>', '<span>Впервые термин Россия встречается</span>');

        $expected = '<span>Впервые термин Россия <ins>встречается</ins></span>';
        $this->assertEquals($expected, $result);
    }

    public function testCyrillicDeletionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>Впервые термин Россия встречается</span>', '<span>Впервые термин Россия</span>');

        $expected = '<span>Впервые термин Россия <del>встречается</del></span>';
        $this->assertEquals($expected, $result);
    }

    // Polish

    public function testPolishSubstitutionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>Źródłosłów wyrazu "Polska" nie został dotąd konkretnie ustalony</span>', '<span>Źródłosłów wyrazu "Polska" nie został dotąd jednoznacznie ustalony</span>');

        $expected = '<span>Źródłosłów wyrazu "Polska" nie został dotąd <del>konkretnie</del><ins>jednoznacznie</ins> ustalony</span>';
        $this->assertEquals($expected, $result);
    }

    public function testPolishAdditionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>Źródłosłów wyrazu "Polska" nie został dotąd ustalony</span>', '<span>Źródłosłów wyrazu "Polska" nie został dotąd jednoznacznie ustalony</span>');

        $expected = '<span>Źródłosłów wyrazu "Polska" nie został dotąd <ins>jednoznacznie</ins> ustalony</span>';
        $this->assertEquals($expected, $result);
    }

    public function testPolishDeletionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>Źródłosłów wyrazu "Polska" nie został dotąd jednoznacznie ustalony</span>', '<span>Źródłosłów wyrazu "Polska" nie został dotąd ustalony</span>');

        $expected = '<span>Źródłosłów wyrazu "Polska" nie został dotąd <del>jednoznacznie</del> ustalony</span>';
        $this->assertEquals($expected, $result);
    }

    // French

    public function testFrenchSubstitutionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>La France métropolitaine est située à l’une des extrémités occidentales de l’Europe.</span>', '<span>République française métropolitaine est située à l’une des extrémités occidentales de l’Europe.</span>');

        $expected = '<span><del>La France</del><ins>République française</ins> métropolitaine est située à l’une des extrémités occidentales de l’Europe.</span>';
        $this->assertEquals($expected, $result);
    }

    public function testFrenchAdditionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>La France métropolitaine est située à l’une des extrémités occidentales de l’Europe.</span>', '<span>République française. La France métropolitaine est située à l’une des extrémités occidentales de l’Europe.</span>');

        $expected = '<span><ins>République française.</ins> La France métropolitaine est située à l’une des extrémités occidentales de l’Europe.</span>';
        $this->assertEquals($expected, $result);
    }

    public function testFrenchDeletionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>République française. La France métropolitaine est située à l’une des extrémités occidentales de l’Europe.</span>', '<span>La France métropolitaine est située à l’une des extrémités occidentales de l’Europe.</span>');

        $expected = '<span><del>République française.</del> La France métropolitaine est située à l’une des extrémités occidentales de l’Europe.</span>';
        $this->assertEquals($expected, $result);
    }

    // Arabic

    public function testArabicSubstitutionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>اللغة العربية هي أكثر اللغات تحدثاً ضمن مجموعة اللغات السامية، وإحدى أكثر اللغات انتشارًا في العالم</span>', '<span>اللغة العربية هي أكثر اللغات تحدثاً ضمن مجموعة اللغات السامية، يتحدثها أكثر من 422 مليون نسمة</span>');

        $expected = '<span>اللغة العربية هي أكثر اللغات تحدثاً ضمن مجموعة اللغات السامية، <del>وإحدى</del><ins>يتحدثها</ins> أكثر <del>اللغات انتشارًا في العالم</del><ins>من 422 مليون نسمة</ins></span>';
        $this->assertEquals($expected, $result);
    }

    public function testArabicAdditionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>اللغة العربية هي أكثر اللغات تحدثاً ضمن مجموعة اللغات السامية، يتحدثها أكثر من 422 مليون نسمة</span>', '<span>اللغة العربية هي أكثر اللغات تحدثاً ضمن مجموعة اللغات السامية، وإحدى أكثر اللغات انتشارًا في العالم، يتحدثها أكثر من 422 مليون نسمة</span>');

        $expected = '<span>اللغة العربية هي أكثر اللغات تحدثاً ضمن مجموعة اللغات السامية، <ins>وإحدى أكثر اللغات انتشارًا في العالم،</ins> يتحدثها أكثر من 422 مليون نسمة</span>';
        $this->assertEquals($expected, $result);
    }

    public function testArabicDeletionHtml()
    {
        $htmldiff = new Htmldiff;
        $result = $htmldiff->diff('<span>اللغة العربية هي أكثر اللغات تحدثاً ضمن مجموعة اللغات السامية، وإحدى أكثر اللغات انتشارًا في العالم، يتحدثها أكثر من 422 مليون نسمة</span>', '<span>اللغة العربية هي أكثر اللغات تحدثاً ضمن مجموعة اللغات السامية، يتحدثها أكثر من 422 مليون نسمة</span>');

        $expected = '<span>اللغة العربية هي أكثر اللغات تحدثاً ضمن مجموعة اللغات السامية، <del>وإحدى أكثر اللغات انتشارًا في العالم،</del> يتحدثها أكثر من 422 مليون نسمة</span>';
        $this->assertEquals($expected, $result);
    }

}
