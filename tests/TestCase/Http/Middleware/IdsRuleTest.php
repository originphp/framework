<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types=1);
namespace App\Test\Http\Middleware;

/**
 * I have decied to move REGEX pattern tests for core rules here for development and testing.
 */
class IdsRuleTest extends \PHPUnit\Framework\TestCase
{
    public function testSqlInjectionParanoid()
    {
        ///(\%27)|(\')|(?<!=)=(?!=)|(\%23)|(\#)|(\-\-)|(\%2D)/i
        $pattern = '/(\')|(\%27)|(\#)|(\%23)|(\-\-)|(((\/|(\%2F))(\*|(\%2A))))/i';

        # Test Matches
        $this->assertRegExp($pattern, "This is a test %27 string");
        $this->assertRegExp($pattern, "This is a test ' string");
        $this->assertRegExp($pattern, "This is a test %23 string");
        $this->assertRegExp($pattern, "This is a test # string");
        $this->assertRegExp($pattern, "This is a test -- string");
        $this->assertRegExp($pattern, "This is a test /* string");
        $this->assertRegExp($pattern, "This is a test %2f* string");
        $this->assertRegExp($pattern, "This is a test /%2a string");

        # Test NonMatches
        $this->assertNotRegExp($pattern, "This is a test - string"); //(?<!=)=(?!=)
        $this->assertNotRegExp($pattern, "This is a test / string");
        $this->assertNotRegExp($pattern, "This is a test * string");
    }

    /**
     * Tests Quote and Comment
     *
     * @return void
     */
    public function testSqlInjectionRuleQuoteAndComment()
    {
        $pattern = '/\w*((\%27)|(\'))(?:((\%20)|\s+))?((\-\-)|(\%23)|(\#)|(\%3b)|;|((\/|(\%2F))(\*|(\%2A))))/i';
        
        # Test Matches

        $this->assertRegExp($pattern, "admin'%20#");
        $this->assertRegExp($pattern, "admin'#");
        $this->assertRegExp($pattern, "admin'  #");
        $this->assertRegExp($pattern, "admin' #");
        $this->assertRegExp($pattern, "admin%27%20#");
        $this->assertRegExp($pattern, "admin'--");
        $this->assertRegExp($pattern, "admin' --");
        $this->assertRegExp($pattern, "admin'  --");
        $this->assertRegExp($pattern, "admin' /* inline comment ");
        $this->assertRegExp($pattern, "admin' %2F* inline comment ");
        $this->assertRegExp($pattern, "admin' /%2A inline comment ");
    
        # Test Non Matches
        $this->assertNotRegExp($pattern, "admin'");
        $this->assertNotRegExp($pattern, "admin' ");
        $this->assertNotRegExp($pattern, "#'");
        $this->assertNotRegExp($pattern, "admin'  -");
    }

    public function testSqlInjectionOr()
    {
        // ([(\%20)|\s]+)
        $pattern = '/((\%27)|(\'))([(\%20)|\s]+)((\%6F)|o|(\%4F))((\%72)|r|(\%52))([(\%20)|\s]+)/i';
        $this->assertRegExp($pattern, "admin' or 1>0");
        $this->assertRegExp($pattern, "admin'  or 1>0");
        $this->assertRegExp($pattern, "admin'  or 1>0");
        $this->assertRegExp($pattern, "admin' %6Fr 1>0");
        $this->assertRegExp($pattern, "admin'%20%6Fr 1>0");
        $this->assertRegExp($pattern, "admin'%20 or 1>0");
    }

    public function testSqlInjectionAnd()
    {
        //((((\%41)|a|(\%61))(n)(d))([(\%20)|\s]+)
        $pattern = '/((\%27)|(\'))([(\%20)|\s]+)(((\%41)|a|(\%61))(n)(d))/i';
        $this->assertRegExp($pattern, "admin' AND 1=0");
        $this->assertRegExp($pattern, "admin' %41ND 1=0");
        $this->assertRegExp($pattern, "admin' %61ND 1=0");
        $this->assertRegExp($pattern, "admin'  AND 1=1");
        $this->assertRegExp($pattern, "admin'%20AND 1=1");
        $this->assertRegExp($pattern, "admin'%20 AND 1=1");
    }
    /*
        public function testSqlInjectionRuleQuoteAndSql()
        {
            $pattern = '/\w*((\%27)|(\'))(?:((\%20)|\s+))?(or)/i';
    
            $this->assertRegExp($pattern, "admin' %6Fr 1=0");
            $this->assertRegExp($pattern, "admin' or 1>0");
            $this->assertRegExp($pattern, "admin' or 1<2");
            $this->assertRegExp($pattern, "admin' or 1 > 0");
            $this->assertRegExp($pattern, "admin' or 1 < 2");
            $this->assertRegExp($pattern, "admin' or 1=1");
            $this->assertRegExp($pattern, "admin' or 1=1--");
            $this->assertRegExp($pattern, "admin' or 1=1#");
            $this->assertRegExp($pattern, "admin' or 1 = 1--");
            $this->assertRegExp($pattern, "admin' or 1 = 1#");
            $this->assertRegExp($pattern, "admin' or 1 = 1 --");
            $this->assertRegExp($pattern, "admin' or 1 = 1 #");
            $this->assertRegExp($pattern, "admin') or '1'='1--");
            $this->assertRegExp($pattern, "admin') or ('1'='1--");
            $this->assertRegExp($pattern, "' HAVING 1=1 --");
            $this->assertRegExp($pattern, "admin' AND 1=0 UNION ALL SELECT");
            $this->assertRegExp($pattern, "admin' GROUP BY 'abc' HAVING 1=1--");
            $this->assertRegExp($pattern, "admin' OR '1'='1' /*");
    
            # Check non matches (just as important)
            $this->assertNotRegExp($pattern, "'gameofthrones' OR 'vikings'");
            $this->assertNotRegExp($pattern, "'gameofthrones' = the best");
            $this->assertNotRegExp($pattern, "'gameofthrones' AND -something else");
        }
    */
    public function testSqlInjectionRuleUnion()
    {
        $pattern = '/(union(([(\%20)(\%0)\s]+))(select|all select))/i';
        $this->assertRegExp($pattern, "' union select sum(id)");
        $this->assertRegExp($pattern, "12345) UNION SELECT");
        $this->assertRegExp($pattern, "-1 UNION SELECT 1, 2, 3");
    }

    public function testXssAttackRule()
    {
        $pattern = '/((\%3C)|<|(\x3c)|(\\\u003c)).*((\%[a-f0-9]+)|(0x[0-9]+)|(&\#[a-z0-9]+)|script|iframe|(on[a-z]+\s*((\%3D)|=)))+/i';

        $this->assertRegExp($pattern, '%3C%73%63%72%69%70%74%3Ealert(\'xss\')'); // <script>
        $this->assertRegExp($pattern, '<SCRIPT SRC=http://xss.rocks/xss.js></SCRIPT>');
        $this->assertRegExp($pattern, '<IMG SRC=JaVaScRiPt:alert(\'XSS\')>');
        $this->assertRegExp($pattern, 'javascript:/*--></title></style></textarea></script></xmp><svg/onload=\'+/"/+/onmouseover=1/+/[*/[]/+alert(1)//\'>');
        $this->assertRegExp($pattern, '<IMG SRC="javascript:alert(\'XSS\');">');
        $this->assertRegExp($pattern, '<IMG SRC=javascript:alert(\'XSS\')>');
        $this->assertRegExp($pattern, '<IMG SRC=JaVaScRiPt:alert(\'XSS\')>');
        $this->assertRegExp($pattern, '<IMG SRC=javascript:alert(&quot;XSS&quot;)>');
        $this->assertRegExp($pattern, '<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>');
        $this->assertRegExp($pattern, '<a onmouseover="alert(document.cookie)">xxs link</a>');
        $this->assertRegExp($pattern, '<IMG """><SCRIPT>alert("XSS")</SCRIPT>">');
        $this->assertRegExp($pattern, '<IMG SRC=javascript:alert(String.fromCharCode(88,83,83))>');
        $this->assertRegExp($pattern, '<IMG SRC=# onmouseover="alert(\'xxs\')">');
        $this->assertRegExp($pattern, '<IMG SRC= onmouseover="alert(\'xxs\')">');
        $this->assertRegExp($pattern, '<IMG SRC=/ onerror="alert(String.fromCharCode(88,83,83))"></img>');
        $this->assertRegExp($pattern, '<img src=x onerror="&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041">');
        $this->assertRegExp($pattern, '<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;
        &#39;&#88;&#83;&#83;&#39;&#41;>');
        $this->assertRegExp($pattern, '<IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&
        #0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041>');
        $this->assertRegExp($pattern, '<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>');
        $this->assertRegExp($pattern, '<IMG SRC="jav	ascript:alert(\'XSS\');">');
        $this->assertRegExp($pattern, '<IMG SRC="jav&#x09;ascript:alert(\'XSS\');">');
        $this->assertRegExp($pattern, '<IMG SRC="jav&#x0A;ascript:alert(\'XSS\');">');
        $this->assertRegExp($pattern, '<IMG SRC=" &#14;  javascript:alert(\'XSS\');">');
        $this->assertRegExp($pattern, '<SCRIPT/XSS SRC="http://xss.rocks/xss.js"></SCRIPT>');
        $this->assertRegExp($pattern, '<SCRIPT/SRC="http://xss.rocks/xss.js"></SCRIPT>');
        $this->assertRegExp($pattern, '<<SCRIPT>alert("XSS");//<</SCRIPT>');
        $this->assertRegExp($pattern, '<SCRIPT SRC=http://xss.rocks/xss.js?< B >');
        $this->assertRegExp($pattern, '<SCRIPT SRC=//xss.rocks/.j>');
        $this->assertRegExp($pattern, '<IMG SRC="javascript:alert(\'XSS\')"');
        $this->assertRegExp($pattern, '<iframe src=http://xss.rocks/somepage.html <');
        $this->assertRegExp($pattern, '</script><script>alert(\'XSS\');</script>');
        $this->assertRegExp($pattern, '<svg/onload=alert(\'XSS\')>');
        $this->assertRegExp($pattern, '<BODY ONLOAD=alert(\'XSS\')>');
        $this->assertRegExp($pattern, '<META HTTP-EQUIV="refresh" CONTENT="0; URL=http://;URL=javascript:alert(\'XSS\');">');
        $this->assertRegExp($pattern, '<SCRIPT a=">" \'\' SRC="httx://xss.rocks/xss.js"></SCRIPT>');
        $this->assertRegExp($pattern, '<A HREF="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">XSS</A>');
        $this->assertRegExp($pattern, '<A HREF="http://0x42.0x0000066.0x7.0x93/">XSS</A>');
        $this->assertRegExp($pattern, '<IMG SRC=\'vbscript:msgbox("XSS")\'>');

        $this->assertNotRegExp($pattern, '<img src="foo">');
        $this->assertNotRegExp($pattern, '<a href="https://www.example.com/page.html">some link</a>');
    }
}
