<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2021 Jamiel Sharief.
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
 * Created this test class to help develop and test rules in a more refined way.
 * Finding the blance between matches and false alarms is difficult.
 *
 * - dont use the ^ since this will skip urls
 * - nor start with /w+ maybe
 */
class IdsRuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Check the foundation of the SQL injection attack rules
     *
     * (-?(\d+\)?)) - check string with any number of digits (including optional -) e.g -1000
     * (\')|(\%27) - Chcek for single quote or hex equivilent
     * [^\n]* - 0 or more non new line chars
     *
     * @return void
     */
    public function testStarterRegex()
    {
        $pattern = '/(((\d+\)?))|(\')|(\%27))/'; // cant use the ^ since this will skip urls
        $this->assertMatchesRegularExpression($pattern, "password' union all select");
        $this->assertMatchesRegularExpression($pattern, '1 or 2 = 2; union all select');
    }
    public function testSqlInjectionWithHex()
    {
        $pattern = '/(((\d+\)?))|(\')|(\%27)).*((\%[a-f0-9]+)|(\%00)|(0x[a-f0-9]+))/i';
        $this->assertMatchesRegularExpression($pattern, "admin' %2F* inline comment ");
        $this->assertMatchesRegularExpression($pattern, "admin'%20%6Fr 1>0");
        $this->assertMatchesRegularExpression($pattern, "admin'%0#");
        $this->assertMatchesRegularExpression($pattern, "admin' %2F* inline comment ");
        $this->assertMatchesRegularExpression($pattern, "admin' /%2A inline comment ");
        $this->assertMatchesRegularExpression($pattern, "admin' %41ND 1=0");
        $this->assertMatchesRegularExpression($pattern, "admin' %61ND 1=0");
        $this->assertMatchesRegularExpression($pattern, "admin'%20%6Fr 1>0");
        $this->assertMatchesRegularExpression($pattern, "admin'%20 or 1>0");
        $this->assertMatchesRegularExpression($pattern, "admin'%20AND 1=1");
        $this->assertMatchesRegularExpression($pattern, "admin'%20 AND 1=1");
        $this->assertMatchesRegularExpression($pattern, "admin' %6Fr 1>0");
        $this->assertMatchesRegularExpression($pattern, '1%0#');
        $this->assertMatchesRegularExpression($pattern, "1000' ORDER BY 2;%00"); // null byte
        $this->assertMatchesRegularExpression($pattern, "1000' concat(0x3c62723e) "); // hexadecimal
    }
    
    public function testSqlInjectionRuleQuoteAndComment()
    {
        $pattern = '/(((\d+\)?))|(\')|(\%27)).*(\#|\-\-|\/\*)/i';
    
        $this->assertMatchesRegularExpression($pattern, "admin'#");
        $this->assertMatchesRegularExpression($pattern, "admin'  #");
        $this->assertMatchesRegularExpression($pattern, "admin' #");
        $this->assertMatchesRegularExpression($pattern, 'admin%27 #');
        $this->assertMatchesRegularExpression($pattern, "admin'--");
        $this->assertMatchesRegularExpression($pattern, "admin' --");
        $this->assertMatchesRegularExpression($pattern, "admin'  --");
        $this->assertMatchesRegularExpression($pattern, "admin' /* inline comment */");
        $this->assertMatchesRegularExpression($pattern, "x' AND email is NULL; --");
        
        $this->assertMatchesRegularExpression($pattern, '1--');
        $this->assertMatchesRegularExpression($pattern, '-1#');
        
        /**
         * ; is issue, need to find out else to implement
         * $this->assertMatchesRegularExpression($pattern, "admin';");
         */
    
        # Test Non Matches
        $this->assertDoesNotMatchRegularExpression($pattern, "'admin'");
        $this->assertDoesNotMatchRegularExpression($pattern, "admin'");
        $this->assertDoesNotMatchRegularExpression($pattern, "admin' ");
        $this->assertDoesNotMatchRegularExpression($pattern, "#'");
        $this->assertDoesNotMatchRegularExpression($pattern, "admin'  -");
        $this->assertDoesNotMatchRegularExpression($pattern, "<script>alert('hello');</script");
    }

    public function testSqlInjection()
    {
        $pattern = '/((-?(\d+\)?))|(\')|(\%27)).*\s+(or|and|having|group by|order)\s+.*(<|>|=|like)/i';

        $this->assertMatchesRegularExpression($pattern, "' HAVING 1=1 --");
        $this->assertMatchesRegularExpression($pattern, "admin' AND 1=0 UNION ALL SELECT");
        $this->assertMatchesRegularExpression($pattern, "admin' GROUP BY 'abc' HAVING 1=1--");
        $this->assertMatchesRegularExpression($pattern, "admin' OR '1'='1' /*");

        $this->assertMatchesRegularExpression($pattern, '1 HAVING 1=1 --');
        $this->assertMatchesRegularExpression($pattern, '2 AND 1=0 UNION ALL SELECT');
        $this->assertMatchesRegularExpression($pattern, "3 GROUP BY 'abc' HAVING 1=1--");
        $this->assertMatchesRegularExpression($pattern, "4 OR '1'='1' /*");
        $this->assertMatchesRegularExpression($pattern, '1 OR 2 = 2');
        $this->assertMatchesRegularExpression($pattern, "1) OR ('foo' = 'foo')");
        $this->assertMatchesRegularExpression($pattern, '1  OR  2  =  2');

        $this->assertMatchesRegularExpression($pattern, "admin' or 1>0");
        $this->assertMatchesRegularExpression($pattern, "admin'  or 1>0");
        $this->assertMatchesRegularExpression($pattern, "admin'  or 1>0");
    
        $this->assertMatchesRegularExpression($pattern, "admin' AND 1=0");
        $this->assertMatchesRegularExpression($pattern, "admin'  AND 1=1");

        $this->assertMatchesRegularExpression($pattern, "admin' OR name LIKE '%jon%");

        $this->assertMatchesRegularExpression($pattern, "1' or '1'='1");
        $this->assertMatchesRegularExpression($pattern, "1' or 2>1--");

        $this->assertDoesNotMatchRegularExpression($pattern, '500 having babies');
        $this->assertDoesNotMatchRegularExpression($pattern, "jim' and 'foo");
        $this->assertDoesNotMatchRegularExpression($pattern, '1 = 2');
        $this->assertDoesNotMatchRegularExpression($pattern, 'FOR = 4');
//        $this->assertNoTRegExp($pattern, "abc 1 having equals (=) --");
      
        # Keep here for reference
        $this->assertDoesNotMatchRegularExpression($pattern, "'gameofthrones' OR 'vikings'");
        $this->assertDoesNotMatchRegularExpression($pattern, "'gameofthrones' AND -something else");
        $this->assertDoesNotMatchRegularExpression($pattern, "<script>alert('hello');</script");
        $this->assertDoesNotMatchRegularExpression($pattern, "<script> alert('hello'); </script"); // operator
    }

    public function testSqlInjectionRuleUnion()
    {
        $pattern = '/((\')|(\d\)?)|(\%27))([(\%20)(\%0)\s]+)(union(([(\%20)(\%0)\s]+))(select|all select))/i';

        $this->assertMatchesRegularExpression($pattern, "' union select sum(id)");
        $this->assertMatchesRegularExpression($pattern, '12345) UNION SELECT');
        $this->assertMatchesRegularExpression($pattern, '1 UNION SELECT 1, 2, 3');
        $this->assertMatchesRegularExpression($pattern, '1 UNION %20SELECT 1, 2, 3');
        $this->assertMatchesRegularExpression($pattern, '1%20UNION SELECT 1, 2, 3');
    }

    public function testSqlInjectionRuleHarmful()
    {
        $pattern = '/(;|(\%3b))(\s+)?(DROP TABLE|INSERT INTO)/i';

        $this->assertMatchesRegularExpression($pattern, ';drop table FOO');
        $this->assertMatchesRegularExpression($pattern, '; drop table FOO');
        $this->assertMatchesRegularExpression($pattern, '%3bdrop table FOO');
        $this->assertMatchesRegularExpression($pattern, ';insert into foos');
        $this->assertDoesNotMatchRegularExpression($pattern, 'DROP TABLE FOO');
        $this->assertDoesNotMatchRegularExpression($pattern, 'INSERT INTO foos');
    }

    /**
     * Examples of XSS attacks taken from https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
     *
     * @return void
     */
    public function testXssAttackRule()
    {
        $pattern = '/((\%3C)|<|(\x3c)|(\\\u003c)).*((\%[a-f0-9]+)|(0x[0-9]+)|(&\#[a-z0-9]+)|script|iframe|(on[a-z]+\s*((\%3D)|=)))+/i';

        $this->assertMatchesRegularExpression($pattern, '%3C%73%63%72%69%70%74%3Ealert(\'xss\')'); // <script>
        $this->assertMatchesRegularExpression($pattern, '<SCRIPT SRC=http://xss.rocks/xss.js></SCRIPT>');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=JaVaScRiPt:alert(\'XSS\')>');
        $this->assertMatchesRegularExpression($pattern, 'javascript:/*--></title></style></textarea></script></xmp><svg/onload=\'+/"/+/onmouseover=1/+/[*/[]/+alert(1)//\'>');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC="javascript:alert(\'XSS\');">');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=javascript:alert(\'XSS\')>');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=JaVaScRiPt:alert(\'XSS\')>');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=javascript:alert(&quot;XSS&quot;)>');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>');
        $this->assertMatchesRegularExpression($pattern, '<a onmouseover="alert(document.cookie)">xxs link</a>');
        $this->assertMatchesRegularExpression($pattern, '<IMG """><SCRIPT>alert("XSS")</SCRIPT>">');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=javascript:alert(String.fromCharCode(88,83,83))>');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=# onmouseover="alert(\'xxs\')">');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC= onmouseover="alert(\'xxs\')">');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=/ onerror="alert(String.fromCharCode(88,83,83))"></img>');
        $this->assertMatchesRegularExpression($pattern, '<img src=x onerror="&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041">');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;
        &#39;&#88;&#83;&#83;&#39;&#41;>');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&
        #0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041>');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29>');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC="jav	ascript:alert(\'XSS\');">');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC="jav&#x09;ascript:alert(\'XSS\');">');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC="jav&#x0A;ascript:alert(\'XSS\');">');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=" &#14;  javascript:alert(\'XSS\');">');
        $this->assertMatchesRegularExpression($pattern, '<SCRIPT/XSS SRC="http://xss.rocks/xss.js"></SCRIPT>');
        $this->assertMatchesRegularExpression($pattern, '<SCRIPT/SRC="http://xss.rocks/xss.js"></SCRIPT>');
        $this->assertMatchesRegularExpression($pattern, '<<SCRIPT>alert("XSS");//<</SCRIPT>');
        $this->assertMatchesRegularExpression($pattern, '<SCRIPT SRC=http://xss.rocks/xss.js?< B >');
        $this->assertMatchesRegularExpression($pattern, '<SCRIPT SRC=//xss.rocks/.j>');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC="javascript:alert(\'XSS\')"');
        $this->assertMatchesRegularExpression($pattern, '<iframe src=http://xss.rocks/somepage.html <');
        $this->assertMatchesRegularExpression($pattern, '</script><script>alert(\'XSS\');</script>');
        $this->assertMatchesRegularExpression($pattern, '<svg/onload=alert(\'XSS\')>');
        $this->assertMatchesRegularExpression($pattern, '<BODY ONLOAD=alert(\'XSS\')>');
        $this->assertMatchesRegularExpression($pattern, '<META HTTP-EQUIV="refresh" CONTENT="0; URL=http://;URL=javascript:alert(\'XSS\');">');
        $this->assertMatchesRegularExpression($pattern, '<SCRIPT a=">" \'\' SRC="httx://xss.rocks/xss.js"></SCRIPT>');
        $this->assertMatchesRegularExpression($pattern, '<A HREF="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">XSS</A>');
        $this->assertMatchesRegularExpression($pattern, '<A HREF="http://0x42.0x0000066.0x7.0x93/">XSS</A>');
        $this->assertMatchesRegularExpression($pattern, '<IMG SRC=\'vbscript:msgbox("XSS")\'>');

        $this->assertDoesNotMatchRegularExpression($pattern, '<img src="foo">');
        $this->assertDoesNotMatchRegularExpression($pattern, '<a href="https://www.example.com/page.html">some link</a>');
    }
}
